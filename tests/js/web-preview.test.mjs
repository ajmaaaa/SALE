import { test } from 'node:test';
import assert from 'node:assert/strict';
import { buildPreviewDocument, runWeb } from '../../resources/js/web-preview.js';

test('fragment code is wrapped in a document with the capture script', () => {
    const out = buildPreviewDocument('<h1>Halo</h1>');
    assert.match(out, /^<!DOCTYPE html>/);
    assert.match(out, /sale-web-preview/);
    assert.match(out, /<h1>Halo<\/h1>/);
});

test('full documents keep their doctype and head gets the capture script', () => {
    const source = `<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <title>X</title>\n</head>\n<body>\n  <p>Konten</p>\n</body>\n</html>`;
    const out = buildPreviewDocument(source);
    assert.ok(out.startsWith('<!DOCTYPE html>'));
    assert.ok(out.indexOf('sale-web-preview') < out.indexOf('<p>Konten</p>'));
    assert.match(out, /<p>Konten<\/p>/);
});

test('documents without a head still get the script before body content', () => {
    const source = `<!DOCTYPE html>\n<html><body bgcolor="white"><h2>Tanpa head</h2></body></html>`;
    const out = buildPreviewDocument(source);
    assert.ok(out.indexOf('sale-web-preview') < out.indexOf('<h2>Tanpa head</h2>'));
});

test('empty input still produces a renderable document', () => {
    const out = buildPreviewDocument('');
    assert.match(out, /^<!DOCTYPE html>/);
    assert.match(out, /<body>\s*<\/body>/);
});

test('referenced css and js files are inlined and the tags removed', () => {
    const files = [
        { name: 'index.html', code: '<!DOCTYPE html>\n<html>\n<head>\n<link rel="stylesheet" href="style.css">\n</head>\n<body>\n<script src="app.js"></script>\n</body>\n</html>' },
        { name: 'style.css', code: 'body { color: red; }' },
        { name: 'app.js', code: 'console.log("ok")' },
    ];
    const out = buildPreviewDocument(files[0].code, files);
    assert.doesNotMatch(out, /href="style\.css"/);
    assert.doesNotMatch(out, /src="app\.js"/);
    assert.match(out, /body \{ color: red; \}/);
    assert.match(out, /console\.log\("ok"\)/);
});

test('unreferenced css and js files are bundled into the document', () => {
    const files = [
        { name: 'index.html', code: '<!DOCTYPE html>\n<html>\n<head></head>\n<body>\n<h1>Halo</h1>\n</body>\n</html>' },
        { name: 'theme.css', code: 'h1 { color: blue; }' },
        { name: 'main.js', code: 'console.log("hai")' },
    ];
    const out = buildPreviewDocument(files[0].code, files);
    assert.match(out, /h1 \{ color: blue; \}/);
    assert.match(out, /console\.log\("hai"\)/);
});

test('capture script still runs before other scripts', () => {
    const files = [
        { name: 'index.html', code: '<html><body><script>console.log("x")</script></body></html>' },
        { name: 'app.js', code: 'alert(1)' },
    ];
    const out = buildPreviewDocument(files[0].code, files);
    assert.ok(out.indexOf('sale-web-preview') < out.indexOf('console.log("x")'));
});

function withWindowStub() {
    const listeners = new Map();
    const window = {
        addEventListener(type, handler) {
            const set = listeners.get(type) ?? new Set();
            set.add(handler);
            listeners.set(type, set);
        },
        removeEventListener(type, handler) {
            listeners.get(type)?.delete(handler);
        },
        setTimeout,
        clearTimeout,
        dispatch(type, event) {
            listeners.get(type)?.forEach((handler) => handler(event));
        },
    };
    globalThis.window = window;
    return window;
}

test('runWeb renders the document and resolves on the ready message', async () => {
    const window = withWindowStub();
    const frame = { srcdoc: '', contentWindow: { tag: 'frame' } };
    let settled = false;
    const run = runWeb({ code: '<p>Halo</p>', frame, onOutput() {}, signal: new AbortController().signal });
    const ready = run.then(() => { settled = true; });
    assert.match(frame.srcdoc, /sale-web-preview/);
    window.dispatch('message', { source: frame.contentWindow, data: { $source: 'sale-web-preview', type: 'ready', text: '' } });
    await ready;
    assert.equal(settled, true);
});

test('runWeb forwards console messages from the preview frame', async () => {
    const window = withWindowStub();
    const frame = { srcdoc: '', contentWindow: { tag: 'frame' } };
    const received = [];
    const run = runWeb({ code: '<script>console.log("hai")</script>', frame, onOutput: (type, text) => received.push([type, text]), signal: new AbortController().signal });
    window.dispatch('message', { source: frame.contentWindow, data: { $source: 'sale-web-preview', type: 'log', text: 'hai' } });
    window.dispatch('message', { source: { tag: 'other' }, data: { $source: 'sale-web-preview', type: 'log', text: 'abaikan' } });
    window.dispatch('message', { source: frame.contentWindow, data: { $source: 'sale-web-preview', type: 'ready', text: '' } });
    await run;
    assert.deepEqual(received, [['log', 'hai']]);
});