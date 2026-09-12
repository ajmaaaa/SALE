import { mkdir, copyFile } from 'node:fs/promises';
const target = new URL('../public/vendor/pyodide/', import.meta.url);
await mkdir(target, { recursive: true });
for (const file of ['pyodide.mjs', 'pyodide.asm.mjs', 'pyodide.asm.wasm', 'python_stdlib.zip', 'pyodide-lock.json']) {
    await copyFile(new URL(`../node_modules/pyodide/${file}`, import.meta.url), new URL(file, target));
}
