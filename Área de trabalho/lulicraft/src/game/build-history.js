const MAX_HISTORY = 120;

function cloneChest(items) {
  if (items === undefined) return undefined;
  if (!Array.isArray(items)) return null;
  return items.map((slot) => (slot ? { ...slot } : null));
}

function cloneLoot(items) {
  if (!Array.isArray(items)) return [];
  return items.filter(Boolean).map((item) => ({ ...item }));
}

export function makeChange({ x, y, z, before, after, chestBefore, chestAfter, loot }) {
  return {
    x: Math.floor(x),
    y: Math.floor(y),
    z: Math.floor(z),
    before,
    after,
    chestBefore: cloneChest(chestBefore),
    chestAfter: cloneChest(chestAfter),
    loot: cloneLoot(loot)
  };
}

export class BuildHistory {
  constructor(limit = MAX_HISTORY) {
    this.limit = Math.max(1, Number(limit) || MAX_HISTORY);
    this.undoStack = [];
    this.redoStack = [];
  }

  record(label, changes) {
    const list = (Array.isArray(changes) ? changes : [changes]).filter(Boolean);
    if (!list.length) return false;
    this.undoStack.push({ label: String(label || 'Construção'), changes: list.map((change) => makeChange(change)) });
    if (this.undoStack.length > this.limit) this.undoStack.shift();
    this.redoStack = [];
    return true;
  }

  peekUndo() {
    return this.undoStack[this.undoStack.length - 1] || null;
  }

  peekRedo() {
    return this.redoStack[this.redoStack.length - 1] || null;
  }

  undo() {
    const entry = this.undoStack.pop();
    if (!entry) return null;
    this.redoStack.push(entry);
    return entry;
  }

  redo() {
    const entry = this.redoStack.pop();
    if (!entry) return null;
    this.undoStack.push(entry);
    return entry;
  }

  clear() {
    this.undoStack = [];
    this.redoStack = [];
  }

  get canUndo() {
    return this.undoStack.length > 0;
  }

  get canRedo() {
    return this.redoStack.length > 0;
  }
}

export function changeBefore(change) {
  return {
    x: change.x,
    y: change.y,
    z: change.z,
    block: change.before,
    chest: change.chestBefore
  };
}

export function changeAfter(change) {
  return {
    x: change.x,
    y: change.y,
    z: change.z,
    block: change.after,
    chest: change.chestAfter
  };
}
