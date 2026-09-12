import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
let source = await readFile(new URL('../../resources/js/python-runner.js', import.meta.url), 'utf8');
source = source.replace(/import workerSource[^;]+;/, "const workerSource = '';").replace(/import bstSuite[^;]+;/, "const bstSuite = ''; ");
const { runPython } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
class WorkerStub {
    static latest;
    constructor(url) { this.url = url; WorkerStub.latest = this; }
    postMessage() {}
    terminate() { this.terminated = true; }
    emit(data) { this.onmessage({ data }); }
}
function start(signal) {
    globalThis.Worker = WorkerStub;
    return runPython({ code: 'pass', assignmentId: 1, runtimeUrl: '/', onOutput() {}, onReady() {}, signal });
}
test('stop terminates worker and rejects execution', async () => {
    const control = new AbortController();
    const result = start(control.signal);
    control.abort();
    await assert.rejects(result, /dihentikan/);
    assert.ok(WorkerStub.latest.terminated);
});
test('execution deadline starts after runtime is ready', async t => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const result = start();
    WorkerStub.latest.emit({ type: 'ready', version: '3.14' });
    t.mock.timers.tick(10000);
    await assert.rejects(result, /10 detik/);
    assert.ok(WorkerStub.latest.terminated);
});
test('runtime loading has its own deadline', async t => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const result = start();
    t.mock.timers.tick(60000);
    await assert.rejects(result, /belum berhasil dimuat/);
    assert.ok(WorkerStub.latest.terminated);
});
test('output flood terminates worker', async () => {
    const result = start();
    WorkerStub.latest.emit({ type: 'output', stream: 'stdout', text: 'x'.repeat(64001) });
    await assert.rejects(result, /64.000/);
    assert.ok(WorkerStub.latest.terminated);
});

test('worker starts from a page-origin blob and releases it on completion', async () => {
    const result = start();
    const url = WorkerStub.latest.url;
    assert.ok(url.startsWith('blob:'));
    assert.equal((await fetch(url)).status, 200);
    WorkerStub.latest.emit({ type: 'done', exitCode: 0 });
    assert.equal(await result, 0);
    await assert.rejects(fetch(url));
});
