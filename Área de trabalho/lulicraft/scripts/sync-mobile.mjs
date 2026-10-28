import { cp, mkdir, readdir, rm, stat } from 'node:fs/promises';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const target = resolve(root, 'mobile/assets/web');
const entries = ['index.html', 'logo.png', 'logo.svg', 'logo-mark.svg', 'assets', 'src', 'COPYRIGHT'];

await rm(target, { recursive: true, force: true });
await mkdir(target, { recursive: true });

for (const entry of entries) {
  await cp(resolve(root, entry), resolve(target, entry), {
    recursive: true,
    filter: (source) => !source.includes('.DS_Store')
  });
}

async function sizeOf(path) {
  const info = await stat(path);
  if (!info.isDirectory()) return info.size;
  const children = await readdir(path);
  let total = 0;
  for (const child of children) total += await sizeOf(join(path, child));
  return total;
}

const total = await sizeOf(target);
console.log(
  `Assets web sincronizados em mobile/assets/web (${(total / 1024 / 1024).toFixed(2)} MB).`
);
