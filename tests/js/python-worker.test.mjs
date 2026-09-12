import { test } from 'node:test';
import assert from 'node:assert/strict';
import { Worker } from 'node:worker_threads';
import { readFile } from 'node:fs/promises';
const suite = await readFile(new URL('../../app/Services/Ai/TestSuites/task_1.py', import.meta.url), 'utf8');
const workerUrl = new URL('../../resources/js/python.worker.js', import.meta.url).href;
const runtimeUrl = new URL('../../public/vendor/pyodide/', import.meta.url).pathname;
const correct = `class Node:
    def __init__(self, key):
        self.value, self.left, self.right = key, None, None
class BinaryTree:
    def insert(self, root, key):
        if root is None: return Node(key)
        if key < root.value: root.left = self.insert(root.left, key)
        else: root.right = self.insert(root.right, key)
        return root
print('student output')`;
function run(code, tests = suite, extraFiles = []) {
    const files = [{ name: 'main.py', code }, ...extraFiles];
    return new Promise((resolve, reject) => {
        const worker = new Worker(`const { parentPort } = require('node:worker_threads');
            globalThis.self = { postMessage: (data) => parentPort.postMessage(data) };
            import(${JSON.stringify(workerUrl)}).then(() => parentPort.on('message', data => self.onmessage({data})));`, { eval: true });
        const messages = [];
        const timeout = setTimeout(() => { worker.terminate(); reject(new Error('timeout')); }, 20000);
        worker.on('error', reject);
        worker.on('message', message => {
            messages.push(message);
            if (['done', 'error'].includes(message.type)) {
                clearTimeout(timeout); worker.terminate(); resolve(messages);
            }
        });
        worker.postMessage({ files, suite: tests, runtimeUrl });
    });
}
test('real Python passes BST tests and preserves stdout and stderr', async () => {
    const result = await run(correct);
    assert.equal(result.at(-1).type, 'done', JSON.stringify(result));
    assert.equal(result.at(-1).exitCode, 0);
    assert.ok(result.some(x => x.stream === 'stdout' && x.text.includes('student output')));
    assert.ok(result.some(x => x.stream === 'stderr' && x.text.includes('Ran 4 tests')));
});
test('incorrect BST fails actual tests', async () => {
    const result = await run(correct.replace('root.left = self.insert(root.left, key)', 'pass'));
    assert.equal(result.at(-1).exitCode, 1);
});
test('syntax errors identify main.py', async () => {
    const result = await run('def broken(');
    assert.equal(result.at(-1).exitCode, 1);
    assert.ok(result.some(x => x.text?.includes('main.py')));
});
test('assignments without a suite execute ordinary Python', async () => {
    const result = await run('print(2 + 3)', '');
    assert.equal(result.at(-1).exitCode, 0);
    assert.ok(result.some(x => x.text === '5'));
});
test('support files run before main.py in the same namespace', async () => {
    const result = await run('from helpers import greet\nprint(greet("SALE"))', '', [
        { name: 'helpers.py', code: 'def greet(name):\n    return "Halo " + name' },
    ]);
    assert.equal(result.at(-1).exitCode, 0);
    assert.ok(result.some(x => x.text === 'Halo SALE'));
});
