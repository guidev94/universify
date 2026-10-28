import { getItem, maxStack } from './items.js';

function emptyArray(size) {
  return new Array(size).fill(null);
}

const BACKPACK_ROW = 9;
const MIN_BACKPACK_ROWS = 3;
const MAX_BACKPACK_ROWS = 512;

export function makeSlot(id, count = 1) {
  return { id, count };
}

export function cloneSlots(slots) {
  return slots.map((slot) => (slot ? { ...slot } : null));
}

function isPouchItem(item) {
  return item.category === 'semente' || item.category === 'alimento';
}

export function addToArrays(id, count, arrays) {
  const item = getItem(id);
  const max = maxStack(id);
  let remaining = count;
  for (const arr of arrays) {
    for (let i = 0; i < arr.length && remaining > 0; i++) {
      const slot = arr[i];
      if (slot && slot.id === id && slot.count < max) {
        const space = max - slot.count;
        const add = Math.min(space, remaining);
        slot.count += add;
        remaining -= add;
      }
    }
  }
  for (const arr of arrays) {
    for (let i = 0; i < arr.length && remaining > 0; i++) {
      if (!arr[i]) {
        const add = Math.min(max, remaining);
        arr[i] = { id, count: add };
        remaining -= add;
      }
    }
  }
  return remaining;
}

export class Inventory {
  constructor(data = null) {
    this.hotbar = emptyArray(9);
    this.backpack = emptyArray(27);
    this.pouch = emptyArray(9);
    this.selected = 0;
    if (data) this.load(data);
  }

  load(data) {
    if (Array.isArray(data.hotbar)) this.hotbar = cloneSlots(data.hotbar).slice(0, 9);
    if (Array.isArray(data.backpack)) this.backpack = cloneSlots(data.backpack).slice(0, MAX_BACKPACK_ROWS * BACKPACK_ROW);
    if (Array.isArray(data.pouch)) this.pouch = cloneSlots(data.pouch).slice(0, 9);
    while (this.hotbar.length < 9) this.hotbar.push(null);
    while (this.backpack.length < MIN_BACKPACK_ROWS * BACKPACK_ROW) this.backpack.push(null);
    while (this.pouch.length < 9) this.pouch.push(null);
    this.selected = Math.max(0, Math.min(8, Number(data.selected) || 0));
  }

  expandBackpackRow() {
    if (this.backpack.length >= MAX_BACKPACK_ROWS * BACKPACK_ROW) return null;
    const row = emptyArray(BACKPACK_ROW);
    this.backpack.push(...row);
    return row;
  }

  expandBackpack(rows = 1) {
    let expanded = false;
    for (let i = 0; i < rows; i++) {
      if (!this.expandBackpackRow()) break;
      expanded = true;
    }
    return expanded;
  }

  serialize() {
    return {
      hotbar: cloneSlots(this.hotbar),
      backpack: cloneSlots(this.backpack),
      pouch: cloneSlots(this.pouch),
      selected: this.selected
    };
  }

  arrays() {
    return [this.hotbar, this.backpack, this.pouch];
  }

  pouchArrays() {
    return [this.pouch, this.hotbar, this.backpack];
  }

  addItem(id, count = 1) {
    const item = getItem(id);
    const arrays = isPouchItem(item) ? this.pouchArrays() : [this.hotbar, this.backpack];
    let left = addToArrays(id, count, arrays);
    while (left > 0 && this.expandBackpack()) {
      left = addToArrays(id, left, arrays);
    }
    return left;
  }

  addMany(items) {
    const leftovers = [];
    for (const item of items) {
      const left = this.addItem(item.id, item.count);
      if (left > 0) leftovers.push({ id: item.id, count: left });
    }
    return leftovers;
  }

  countItem(id) {
    let total = 0;
    for (const arr of this.arrays()) {
      for (const slot of arr) {
        if (slot && slot.id === id) total += slot.count;
      }
    }
    return total;
  }

  hasItem(id, count = 1) {
    return this.countItem(id) >= count;
  }

  removeItem(id, count = 1) {
    let remaining = count;
    for (const arr of this.arrays()) {
      for (let i = 0; i < arr.length && remaining > 0; i++) {
        const slot = arr[i];
        if (slot && slot.id === id) {
          const take = Math.min(slot.count, remaining);
          slot.count -= take;
          remaining -= take;
          if (slot.count <= 0) arr[i] = null;
        }
      }
    }
    return count - remaining;
  }

  canCraft(ingredients) {
    return ingredients.every((ing) => this.countItem(ing.id) >= ing.count);
  }

  consume(ingredients) {
    for (const ing of ingredients) this.removeItem(ing.id, ing.count);
  }

  selectedSlot() {
    return this.hotbar[this.selected];
  }

  select(index) {
    this.selected = Math.max(0, Math.min(8, index));
  }

  removeOneSelected() {
    const slot = this.hotbar[this.selected];
    if (!slot) return null;
    if (!slot.infinite) {
      slot.count -= 1;
      if (slot.count <= 0) this.hotbar[this.selected] = null;
    }
    return slot.id;
  }

  findSlot(id) {
    for (const arr of this.arrays()) {
      for (let i = 0; i < arr.length; i++) {
        if (arr[i] && arr[i].id === id) return { arr, index: i, slot: arr[i] };
      }
    }
    return null;
  }

  canAdd(id, count = 1) {
    const item = getItem(id);
    const max = maxStack(id);
    let remaining = count;
    const arrays = isPouchItem(item) ? this.pouchArrays() : [this.hotbar, this.backpack];
    for (const arr of arrays) {
      for (const slot of arr) {
        if (slot && slot.id === id) remaining -= Math.max(0, max - slot.count);
        if (remaining <= 0) return true;
      }
    }
    for (const arr of arrays) {
      for (const slot of arr) {
        if (!slot) remaining -= max;
        if (remaining <= 0) return true;
      }
    }
    return remaining <= 0 || this.backpack.length < MAX_BACKPACK_ROWS * BACKPACK_ROW;
  }

  canAddMany(items) {
    const counts = new Map();
    for (const item of items) counts.set(item.id, (counts.get(item.id) || 0) + item.count);
    for (const [id, count] of counts.entries()) {
      if (!this.canAdd(id, count)) return false;
    }
    return true;
  }

  giveStarterKit() {
    this.addItem(1, 8);
    this.addItem(9, 4);
    this.addItem(13, 8);
    this.addItem(22, 1);
    this.addItem('wood_pickaxe', 1);
  }

  giveRareKit() {
    const kit = [
      ['aurora_pickaxe', 1],
      ['cloud_boots', 1],
      ['lumi_charm', 1],
      ['heart_lumi', 1],
      ['prism_seed', 4],
      ['rainbow_sapling', 3],
      ['star_soup', 3],
      ['moon_berry', 4],
      ['bubble_wand', 1],
      ['star_sling', 1],
      ['petal_blade', 1],
      ['aurora_gem', 8],
      ['cloud_feather', 5],
      ['star_mushroom', 5]
    ];
    for (const [id, count] of kit) this.addItem(id, count);
  }

  clear() {
    this.hotbar = emptyArray(9);
    this.backpack = emptyArray(MIN_BACKPACK_ROWS * BACKPACK_ROW);
    this.pouch = emptyArray(9);
    this.selected = 0;
  }
}

export function swapOrMerge(arrA, iA, arrB, iB) {
  const a = arrA[iA];
  const b = arrB[iB];
  if (a && b && a.id === b.id) {
    const max = maxStack(a.id);
    const total = a.count + b.count;
    if (total <= max) {
      arrB[iB] = { id: a.id, count: total };
      arrA[iA] = null;
      return;
    }
    arrB[iB] = { id: a.id, count: max };
    arrA[iA] = { id: a.id, count: total - max };
    return;
  }
  arrA[iA] = b;
  arrB[iB] = a;
}

export function quickMove(sourceArr, sourceIndex, destArrays, grow = null) {
  const slot = sourceArr[sourceIndex];
  if (!slot) return false;
  const max = maxStack(slot.id);
  const initial = slot.count;
  let remaining = slot.count;
  for (const arr of destArrays) {
    for (let i = 0; i < arr.length && remaining > 0; i++) {
      const dest = arr[i];
      if (dest && dest.id === slot.id && dest.count < max) {
        const add = Math.min(max - dest.count, remaining);
        dest.count += add;
        remaining -= add;
      }
    }
  }
  for (const arr of destArrays) {
    for (let i = 0; i < arr.length && remaining > 0; i++) {
      if (!arr[i]) {
        const add = Math.min(max, remaining);
        arr[i] = { id: slot.id, count: add };
        remaining -= add;
      }
    }
  }
  while (remaining > 0 && grow) {
    const row = grow();
    if (!row) break;
    for (let i = 0; i < row.length && remaining > 0; i++) {
      const add = Math.min(max, remaining);
      row[i] = { id: slot.id, count: add };
      remaining -= add;
    }
  }
  if (remaining <= 0) sourceArr[sourceIndex] = null;
  else slot.count = remaining;
  return remaining < initial;
}

export function splitHalf(slot) {
  if (!slot || slot.count <= 1) return null;
  const half = Math.ceil(slot.count / 2);
  slot.count -= half;
  return { id: slot.id, count: half };
}
