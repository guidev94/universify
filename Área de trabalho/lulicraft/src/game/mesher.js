import * as THREE from '../vendor/three.module.js';
import { B, TILE, getBlock, isOpaqueBlock } from './blocks.js';
import { CHUNK_SIZE, WORLD_HEIGHT } from './world.js';

const FACES = [
  { dir: [1, 0, 0], verts: [[1, 0, 0], [1, 1, 0], [1, 1, 1], [1, 0, 1]], uvs: [[0, 0], [0, 1], [1, 1], [1, 0]], shade: 0.8 },
  { dir: [-1, 0, 0], verts: [[0, 0, 0], [0, 0, 1], [0, 1, 1], [0, 1, 0]], uvs: [[1, 0], [0, 0], [0, 1], [1, 1]], shade: 0.72 },
  { dir: [0, 1, 0], verts: [[0, 1, 1], [1, 1, 1], [1, 1, 0], [0, 1, 0]], uvs: [[0, 1], [1, 1], [1, 0], [0, 0]], shade: 1 },
  { dir: [0, -1, 0], verts: [[0, 0, 0], [1, 0, 0], [1, 0, 1], [0, 0, 1]], uvs: [[0, 0], [1, 0], [1, 1], [0, 1]], shade: 0.55 },
  { dir: [0, 0, 1], verts: [[1, 0, 1], [1, 1, 1], [0, 1, 1], [0, 0, 1]], uvs: [[1, 0], [1, 1], [0, 1], [0, 0]], shade: 0.88 },
  { dir: [0, 0, -1], verts: [[0, 0, 0], [0, 1, 0], [1, 1, 0], [1, 0, 0]], uvs: [[0, 0], [0, 1], [1, 1], [1, 0]], shade: 0.66 }
];

function createBuilder() {
  return { pos: [], normal: [], uv: [], color: [], index: [] };
}

function sample(chunk, world, x, y, z) {
  if (x >= 0 && x < CHUNK_SIZE && y >= 0 && y < WORLD_HEIGHT && z >= 0 && z < CHUNK_SIZE) {
    return chunk.blocks[x + z * CHUNK_SIZE + y * CHUNK_SIZE * CHUNK_SIZE];
  }
  return world.getBlock(chunk.cx * CHUNK_SIZE + x, y, chunk.cz * CHUNK_SIZE + z);
}

function isOccluder(id) {
  return isOpaqueBlock(id);
}

function tileFor(block, faceIndex) {
  const tiles = block.tiles;
  if (typeof tiles === 'string') return TILE[tiles];
  if (faceIndex === 2) return TILE[tiles.top];
  if (faceIndex === 3) return TILE[tiles.bottom || tiles.top];
  return TILE[tiles.side || tiles.top];
}

function addFace(builder, verts, normal, uvs, tile, shade, aoList) {
  const base = builder.pos.length / 3;
  const tx = tile % 8;
  const ty = Math.floor(tile / 8);
  for (let i = 0; i < 4; i++) {
    const v = verts[i];
    builder.pos.push(v[0], v[1], v[2]);
    builder.normal.push(normal[0], normal[1], normal[2]);
    const u = (tx * 16 + 0.5 + uvs[i][0] * 15) / 128;
    const vv = 1 - (ty * 16 + 0.5 + (1 - uvs[i][1]) * 15) / 128;
    builder.uv.push(u, vv);
    const light = shade * (aoList ? aoList[i] : 1);
    builder.color.push(light, light, light);
  }
  builder.index.push(base, base + 1, base + 2, base, base + 2, base + 3);
}

function computeAO(chunk, world, x, y, z, face, vertex) {
  const dir = face.dir;
  const axis = dir[0] !== 0 ? 0 : dir[1] !== 0 ? 1 : 2;
  const tangents = [0, 1, 2].filter((a) => a !== axis);
  const s1 = vertex[tangents[0]] === 1 ? 1 : -1;
  const s2 = vertex[tangents[1]] === 1 ? 1 : -1;
  const p1 = [x + dir[0], y + dir[1], z + dir[2]];
  const p2 = [...p1];
  const p3 = [...p1];
  p1[tangents[0]] += s1;
  p2[tangents[1]] += s2;
  p3[tangents[0]] += s1;
  p3[tangents[1]] += s2;
  const o1 = isOccluder(sample(chunk, world, p1[0], p1[1], p1[2])) ? 1 : 0;
  const o2 = isOccluder(sample(chunk, world, p2[0], p2[1], p2[2])) ? 1 : 0;
  const o3 = isOccluder(sample(chunk, world, p3[0], p3[1], p3[2])) ? 1 : 0;
  if (o1 && o2) return 0.48;
  return Math.max(0.5, 1 - (o1 + o2 + o3) * 0.16);
}

function addCross(builder, x, y, z, tile) {
  const quads = [
    [[0.15, 0, 0.15], [0.85, 0, 0.85], [0.85, 1, 0.85], [0.15, 1, 0.15]],
    [[0.85, 0, 0.15], [0.15, 0, 0.85], [0.15, 1, 0.85], [0.85, 1, 0.15]]
  ];
  const uvs = [[0, 0], [1, 0], [1, 1], [0, 1]];
  for (const quad of quads) {
    const base = builder.pos.length / 3;
    const tx = tile % 8;
    const ty = Math.floor(tile / 8);
    for (let i = 0; i < 4; i++) {
      const v = quad[i];
      builder.pos.push(x + v[0], y + v[1], z + v[2]);
      builder.normal.push(0, 1, 0);
      const u = (tx * 16 + 0.5 + uvs[i][0] * 15) / 128;
      const vv = 1 - (ty * 16 + 0.5 + (1 - uvs[i][1]) * 15) / 128;
      builder.uv.push(u, vv);
      builder.color.push(1, 1, 1);
    }
    builder.index.push(base, base + 1, base + 2, base, base + 2, base + 3);
  }
}

function toGeometry(builder) {
  if (builder.index.length === 0) return null;
  const geometry = new THREE.BufferGeometry();
  geometry.setAttribute('position', new THREE.Float32BufferAttribute(builder.pos, 3));
  geometry.setAttribute('normal', new THREE.Float32BufferAttribute(builder.normal, 3));
  geometry.setAttribute('uv', new THREE.Float32BufferAttribute(builder.uv, 2));
  geometry.setAttribute('color', new THREE.Float32BufferAttribute(builder.color, 3));
  geometry.setIndex(builder.index);
  geometry.computeBoundingSphere();
  return geometry;
}

export function buildChunkGeometries(world, chunk) {
  const solid = createBuilder();
  const glow = createBuilder();
  const plants = createBuilder();
  const water = createBuilder();

  for (let y = 0; y < WORLD_HEIGHT; y++) {
    for (let z = 0; z < CHUNK_SIZE; z++) {
      for (let x = 0; x < CHUNK_SIZE; x++) {
        const id = chunk.blocks[x + z * CHUNK_SIZE + y * CHUNK_SIZE * CHUNK_SIZE];
        if (id === B.AIR) continue;
        const block = getBlock(id);

        if (block.plant) {
          addCross(plants, x, y, z, tileFor(block, 2));
          continue;
        }

        const builder = block.liquid ? water : block.glow ? glow : solid;

        for (let f = 0; f < FACES.length; f++) {
          const face = FACES[f];
          const nx = x + face.dir[0];
          const ny = y + face.dir[1];
          const nz = z + face.dir[2];
          const neighbor = sample(chunk, world, nx, ny, nz);
          if (block.liquid) {
            if (neighbor === B.WATER || isOpaqueBlock(neighbor)) continue;
          } else if (isOpaqueBlock(neighbor)) {
            continue;
          }
          const ao = block.glow || block.liquid ? null : face.verts.map((v) => computeAO(chunk, world, x, y, z, face, v));
          addFace(builder, face.verts.map((v) => [x + v[0], y + v[1], z + v[2]]), face.dir, face.uvs, tileFor(block, f), face.shade, ao);
        }
      }
    }
  }

  return {
    solid: toGeometry(solid),
    glow: toGeometry(glow),
    plants: toGeometry(plants),
    water: toGeometry(water)
  };
}

export function createMaterials(atlasTexture) {
  return {
    solid: new THREE.MeshLambertMaterial({ map: atlasTexture, vertexColors: true }),
    glow: new THREE.MeshBasicMaterial({ map: atlasTexture, vertexColors: true }),
    plants: new THREE.MeshLambertMaterial({ map: atlasTexture, vertexColors: true, alphaTest: 0.5, side: THREE.DoubleSide }),
    water: new THREE.MeshLambertMaterial({
      map: atlasTexture,
      vertexColors: true,
      transparent: true,
      opacity: 0.72,
      depthWrite: false,
      side: THREE.DoubleSide
    })
  };
}
