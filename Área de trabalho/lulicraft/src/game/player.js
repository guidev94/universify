import * as THREE from '../vendor/three.module.js';

const PLAYER_WIDTH = 0.62;
const PLAYER_HEIGHT = 1.72;
const EYE_HEIGHT = 1.62;

export class Player {
  constructor(camera, world, state = {}) {
    this.camera = camera;
    this.world = world;
    this.position = new THREE.Vector3(state.x || 0, state.y || 24, state.z || 0);
    this.velocity = new THREE.Vector3();
    this.yaw = state.yaw || 0;
    this.pitch = state.pitch || 0;
    this.fly = !!state.fly;
    this.onGround = false;
    this.doubleJumpAvailable = false;
    this.water = false;
    this.walkSpeed = 4.4;
    this.sprintSpeed = 6.9;
    this.flySpeed = 9.5;
    this.gravity = 26;
    this.jumpSpeed = 8.4;
    this.hasBoots = false;
    this.hasCharm = false;
    this.selectedId = null;
    this.reach = 6;
    this.buffs = {};
    this.stepTimer = 0;
    this.lastJumpAt = 0;
    this.callbacks = {};
  }

  setCallbacks(callbacks) {
    this.callbacks = callbacks || {};
  }

  refreshAbilities(inventory) {
    this.hasBoots = inventory.hasItem('cloud_boots');
    this.hasCharm = inventory.hasItem('heart_lumi') || inventory.hasItem('lumi_charm');
    const selected = inventory.selectedSlot();
    this.selectedId = selected ? selected.id : null;
    this.reach = 6;
    if (this.selectedId === 'crystal_pickaxe') this.reach = 8;
    if (this.selectedId === 'aurora_pickaxe') this.reach = 7.5;
  }

  applyBuff(name, seconds) {
    this.buffs[name] = Math.max(this.buffs[name] || 0, Date.now() + seconds * 1000);
  }

  buffActive(name) {
    return (this.buffs[name] || 0) > Date.now();
  }

  buffRemaining(name) {
    return Math.max(0, Math.ceil(((this.buffs[name] || 0) - Date.now()) / 1000));
  }

  isSolidAt(x, y, z) {
    return this.world.isSolid(Math.floor(x), Math.floor(y), Math.floor(z));
  }

  collidesAt(x, y, z) {
    const half = PLAYER_WIDTH / 2;
    const minX = Math.floor(x - half);
    const maxX = Math.floor(x + half - 0.001);
    const minY = Math.floor(y);
    const maxY = Math.floor(y + PLAYER_HEIGHT - 0.001);
    const minZ = Math.floor(z - half);
    const maxZ = Math.floor(z + half - 0.001);
    for (let by = minY; by <= maxY; by++) {
      for (let bz = minZ; bz <= maxZ; bz++) {
        for (let bx = minX; bx <= maxX; bx++) {
          if (this.world.isSolid(bx, by, bz)) return true;
        }
      }
    }
    return false;
  }

  update(dt, input) {
    dt = Math.min(dt, 0.05);
    const speedBuff = this.buffActive('speed') ? 1.35 : 1;
    const jumpBuff = this.buffActive('jump') ? 1.18 : 1;
    this.water = this.world.isWater(Math.floor(this.position.x), Math.floor(this.position.y + 0.6), Math.floor(this.position.z));

    const forward = (input.forward ? 1 : 0) - (input.back ? 1 : 0);
    const strafe = (input.right ? 1 : 0) - (input.left ? 1 : 0);
    const sin = Math.sin(this.yaw);
    const cos = Math.cos(this.yaw);
    let dx = -sin * forward + cos * strafe;
    let dz = -cos * forward - sin * strafe;
    const length = Math.hypot(dx, dz);
    if (length > 1) {
      dx /= length;
      dz /= length;
    }

    let speed;
    if (this.fly) speed = this.flySpeed * speedBuff;
    else if (this.water) speed = 3.4 * speedBuff;
    else speed = (input.sprint ? this.sprintSpeed : this.walkSpeed) * speedBuff;

    const accel = this.fly ? 10 : this.onGround ? 16 : 8;
    const blend = Math.min(1, accel * dt);
    this.velocity.x += (dx * speed - this.velocity.x) * blend;
    this.velocity.z += (dz * speed - this.velocity.z) * blend;

    if (this.fly) {
      let vertical = 0;
      if (input.jump) vertical = this.flySpeed * speedBuff;
      else if (input.sprint) vertical = -this.flySpeed * speedBuff;
      this.velocity.y += (vertical - this.velocity.y) * Math.min(1, 12 * dt);
      this.onGround = false;
    } else {
      if (this.water) {
        this.velocity.y -= this.gravity * 0.28 * dt;
        if (input.jump) this.velocity.y = 4.4;
        this.velocity.y = Math.max(this.velocity.y, -3.2);
      } else {
        this.velocity.y -= this.gravity * dt;
      }
      if (input.jumpPressed) {
        if (this.water) {
          this.velocity.y = 4.8;
          this.callbacks.jump?.();
        } else if (this.onGround) {
          this.velocity.y = this.jumpSpeed * jumpBuff;
          this.onGround = false;
          this.doubleJumpAvailable = this.hasBoots;
          this.callbacks.jump?.();
        } else if (this.hasBoots && this.doubleJumpAvailable) {
          this.velocity.y = this.jumpSpeed * 0.92 * jumpBuff;
          this.doubleJumpAvailable = false;
          this.callbacks.doubleJump?.();
        }
      }
    }

    this.moveAxis('x', this.velocity.x * dt);
    this.moveAxis('z', this.velocity.z * dt);
    this.moveAxis('y', this.velocity.y * dt);

    if (!this.fly) {
      const grounded = this.collidesAt(this.position.x, this.position.y - 0.04, this.position.z);
      if (grounded && this.velocity.y <= 0) {
        this.onGround = true;
        this.doubleJumpAvailable = this.hasBoots;
      } else if (!grounded) {
        this.onGround = false;
      }
    }

    if (this.position.y < -8) {
      const spawn = this.world.findSpawn();
      this.position.set(spawn.x, spawn.y, spawn.z);
      this.velocity.set(0, 0, 0);
      this.callbacks.respawn?.();
    }

    if (this.onGround && length > 0.1) {
      this.stepTimer += dt;
      if (this.stepTimer > 0.34) {
        this.stepTimer = 0;
        this.callbacks.step?.();
      }
    } else {
      this.stepTimer = 0;
    }

    const bob = this.onGround && length > 0.1 ? Math.sin(this.stepTimer * 18) * 0.028 : 0;
    this.camera.position.set(this.position.x, this.position.y + EYE_HEIGHT + bob, this.position.z);
    this.camera.rotation.set(this.pitch, this.yaw, Math.sin(this.stepTimer * 9) * 0.006, 'YXZ');
  }

  moveAxis(axis, amount) {
    if (Math.abs(amount) < 1e-6) return;
    const old = this.position[axis];
    this.position[axis] = old + amount;
    if (!this.collidesAt(this.position.x, this.position.y, this.position.z)) return;

    if (axis !== 'y' && this.onGround) {
      const stepY = this.position.y + 1.04;
      const oldY = this.position.y;
      this.position.y = stepY;
      if (!this.collidesAt(this.position.x, this.position.y, this.position.z)) {
        return;
      }
      this.position.y = oldY;
    }

    this.position[axis] = old;
    if (axis === 'y') {
      if (amount < 0) this.onGround = true;
      this.velocity.y = 0;
    } else {
      this.velocity[axis] = 0;
    }
  }

  direction() {
    const cos = Math.cos(this.pitch);
    return new THREE.Vector3(-Math.sin(this.yaw) * cos, Math.sin(this.pitch), -Math.cos(this.yaw) * cos);
  }

  eyePosition() {
    return new THREE.Vector3(this.position.x, this.position.y + EYE_HEIGHT, this.position.z);
  }

  raycast(maxDist = this.reach) {
    const origin = this.eyePosition();
    const dir = this.direction();
    let x = Math.floor(origin.x);
    let y = Math.floor(origin.y);
    let z = Math.floor(origin.z);
    const stepX = dir.x > 0 ? 1 : -1;
    const stepY = dir.y > 0 ? 1 : -1;
    const stepZ = dir.z > 0 ? 1 : -1;
    const tDeltaX = dir.x !== 0 ? Math.abs(1 / dir.x) : Infinity;
    const tDeltaY = dir.y !== 0 ? Math.abs(1 / dir.y) : Infinity;
    const tDeltaZ = dir.z !== 0 ? Math.abs(1 / dir.z) : Infinity;
    let tMaxX = dir.x !== 0 ? (stepX > 0 ? x + 1 - origin.x : origin.x - x) / Math.abs(dir.x) : Infinity;
    let tMaxY = dir.y !== 0 ? (stepY > 0 ? y + 1 - origin.y : origin.y - y) / Math.abs(dir.y) : Infinity;
    let tMaxZ = dir.z !== 0 ? (stepZ > 0 ? z + 1 - origin.z : origin.z - z) / Math.abs(dir.z) : Infinity;
    let normal = [0, 0, 0];
    let distance = 0;
    for (let i = 0; i < 128; i++) {
      const block = this.world.getBlock(x, y, z);
      if (block !== 0 && block !== 17) {
        return { x, y, z, block, normal, distance };
      }
      if (tMaxX < tMaxY && tMaxX < tMaxZ) {
        x += stepX;
        distance = tMaxX;
        tMaxX += tDeltaX;
        normal = [-stepX, 0, 0];
      } else if (tMaxY < tMaxZ) {
        y += stepY;
        distance = tMaxY;
        tMaxY += tDeltaY;
        normal = [0, -stepY, 0];
      } else {
        z += stepZ;
        distance = tMaxZ;
        tMaxZ += tDeltaZ;
        normal = [0, 0, -stepZ];
      }
      if (distance > maxDist) break;
    }
    return null;
  }

  setPosition(x, y, z) {
    this.position.set(x, y, z);
    this.velocity.set(0, 0, 0);
  }

  serialize() {
    return {
      x: this.position.x,
      y: this.position.y,
      z: this.position.z,
      yaw: this.yaw,
      pitch: this.pitch,
      fly: this.fly
    };
  }
}
