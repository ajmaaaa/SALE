// Console capture runs inside the preview before any student script. Messages are
// forwarded to the parent page; without allow-same-origin the preview is an opaque
// origin that cannot read cookies, local storage, or parent state.
const captureScript = `<script>
(function () {
    try { window.parent.postMessage({ $source: 'sale-web-preview', type: 'ready', text: '' }, '*'); } catch (e) {}
    var serialize = function (value) {
        if (typeof value === 'string') return value;
        if (value === null) return 'null';
        if (value === undefined) return 'undefined';
        if (value instanceof Error) return value.stack || value.message;
        if (typeof value === 'function') return value.toString();
        try {
            var seen = new WeakSet();
            return JSON.stringify(value, function (key, val) {
                if (typeof val === 'function') return '[Function ' + (val.name || 'anonymous') + ']';
                if (typeof val === 'symbol') return String(val);
                if (typeof val === 'bigint') return String(val) + 'n';
                if (val instanceof Error) return val.message;
                if (typeof val === 'object' && val !== null) {
                    if (seen.has(val)) return '[Circular]';
                    seen.add(val);
                }
                return val;
            }) || String(value);
        } catch (e) {
            return String(value);
        }
    };
    var send = function (type, text) {
        try { window.parent.postMessage({ $source: 'sale-web-preview', type: type, text: String(text) }, '*'); } catch (e) {}
    };
    ['log', 'info', 'warn', 'debug', 'error'].forEach(function (method) {
        var original = console[method] || function () {};
        console[method] = function () {
            var parts = [];
            for (var i = 0; i < arguments.length; i++) parts.push(typeof arguments[i] === 'string' ? arguments[i] : serialize(arguments[i]));
            send(method === 'log' || method === 'debug' ? 'log' : method, parts.join(' '));
            try { original.apply(console, arguments); } catch (e) {}
        };
    });
    window.addEventListener('error', function (event) {
        var location = [];
        if (event.filename) {
            location.push(event.filename.split('/').pop());
            if (event.lineno != null) location.push(':' + event.lineno);
        }
        send('error', (event.message || 'Runtime error') + (location.length ? ' \u00b7 ' + location.join('') : ''));
    }, true);
    window.addEventListener('unhandledrejection', function (event) {
        send('error', 'Promise tidak tertangani: ' + serialize(event.reason));
    });
})();
<\/script>`;

function isName(name, ext) {
    return typeof name === 'string' && name.trim().toLowerCase().endsWith(ext);
}

function looksLikeCss(code) {
    if (!code || typeof code !== 'string') return false;
    const trimmed = code.trim();
    if (!trimmed) return false;
    if (/^<!doctype\s+html|^<html|^<body|^<div|^<p[\s>]/i.test(trimmed)) return false;
    if (/^import\s+|^from\s+|^def\s+|^class\s+\w+:|^print\(/m.test(trimmed)) return false;
    return /[.#a-zA-Z0-9_\-\*\[\]:]+\s*\{[^}]*\}/m.test(trimmed) || /@import|@media|@keyframes|@font-face/i.test(trimmed);
}

function looksLikeJs(code) {
    if (!code || typeof code !== 'string') return false;
    const trimmed = code.trim();
    if (!trimmed) return false;
    if (/^<!doctype\s+html|^<html|^<body|^<div|^<p[\s>]/i.test(trimmed)) return false;
    if (/^import\s+from|^def\s+|^class\s+\w+:|^print\(/m.test(trimmed)) return false;
    return /\b(console\.log|document\.|window\.|const\s+|let\s+|var\s+|function\b|addEventListener|querySelector)\b/m.test(trimmed);
}

function findVirtualFile(rawPath, files) {
    if (!rawPath || typeof rawPath !== 'string') return null;
    const clean = rawPath.trim().replace(/^['"]|['"]$/g, '');
    const filename = clean.replace(/^\.?\//, '').split('?')[0].split('#')[0].split('/').pop().toLowerCase();
    const baseWithoutExt = filename.replace(/\.[a-z0-9]+$/i, '');

    // 1. Exact match filename
    let match = files.find((file) => file.name.trim().toLowerCase() === filename);
    if (match) return match;

    // 2. Match with .css added if omitted
    if (!filename.includes('.')) {
        match = files.find((file) => file.name.trim().toLowerCase() === `${filename}.css`);
        if (match) return match;
    }

    // 3. Match by base name (e.g. "style" matches "style.css", "style.py", "style.txt")
    match = files.find((file) => {
        const fileBase = file.name.trim().toLowerCase().replace(/\.[a-z0-9]+$/i, '');
        return fileBase === baseWithoutExt;
    });
    if (match) return match;

    // 4. If looking for a CSS file (e.g. style.css) and there is any file containing CSS code
    if (filename.endsWith('.css')) {
        match = files.find((file) => isName(file.name, '.css') || looksLikeCss(file.code));
        if (match) return match;
    }

    return null;
}

function resolveCssImports(cssCode, files) {
    return String(cssCode ?? '').replace(/@import\s+(?:url\(['"]?([^'")]+)['"]?\)|['"]([^'"]+)['"]);?/gi, (match, url1, url2) => {
        const target = (url1 || url2 || '').trim();
        const file = findVirtualFile(target, files);
        if (file && (isName(file.name, '.css') || looksLikeCss(file.code))) {
            return `/* @import ${target} (${file.name}) */\n${file.code ?? ''}\n`;
        }
        return match;
    });
}

// Seamlessly resolves <link rel="stylesheet" href="..."> and <script src="...">
// pointing to virtual workspace files (e.g. "style.css", "./style.css", "/style.css").
function linkVirtualAssets(source, files) {
    const referencedSet = new Set();

    // 1. Replace <link ... href="..."> in-place if it targets any virtual CSS file
    let out = String(source ?? '').replace(/<link\b([^>]*?)>/gi, (match, attrs) => {
        const hrefMatch = attrs.match(/\bhref\s*=\s*(?:["']([^"']*)["']|([^\s>]+))/i);
        const href = hrefMatch ? (hrefMatch[1] || hrefMatch[2] || '') : '';
        if (!href) return match;

        const isStylesheet = /\brel\s*=\s*["']?stylesheet["']?/i.test(attrs) || !/\brel\b/i.test(attrs);
        const file = findVirtualFile(href, files);
        if (file && (isStylesheet || isName(file.name, '.css') || looksLikeCss(file.code))) {
            referencedSet.add(file.name.toLowerCase());
            return `<style data-file="${file.name}">\n${resolveCssImports(file.code ?? '', files)}\n</style>`;
        }
        return match;
    });

    // 2. Replace <script ... src="..."></script> in-place if it targets any virtual JS file
    out = out.replace(/<script\b([^>]*?)(?:>(?:[\s\S]*?<\/script>)?|\/>)/gi, (match, attrs) => {
        const srcMatch = attrs.match(/\bsrc\s*=\s*(?:["']([^"']*)["']|([^\s>]+))/i);
        const src = srcMatch ? (srcMatch[1] || srcMatch[2] || '') : '';
        if (!src) return match;

        const file = findVirtualFile(src, files);
        if (file && (isName(file.name, '.js') || looksLikeJs(file.code))) {
            referencedSet.add(file.name.toLowerCase());
            return `<script data-file="${file.name}">\n${file.code ?? ''}\n</script>`;
        }
        return match;
    });

    // 3. Any CSS file in workspace that was not explicitly linked is auto-injected
    const unreferencedStyles = files
        .filter((file) => (isName(file.name, '.css') || looksLikeCss(file.code)) && !referencedSet.has(file.name.toLowerCase()))
        .map((file) => `<style data-file="${file.name}" data-auto-injected="true">\n${resolveCssImports(file.code ?? '', files)}\n</style>`)
        .join('\n');

    if (unreferencedStyles) {
        out = /<\/head>/i.test(out)
            ? out.replace(/<\/head>/i, (match) => `${unreferencedStyles}\n${match}`)
            : `${unreferencedStyles}\n${out}`;
    }

    // 4. Any JS file in workspace that was not explicitly linked is auto-injected
    const unreferencedScripts = files
        .filter((file) => (isName(file.name, '.js') || looksLikeJs(file.code)) && !referencedSet.has(file.name.toLowerCase()))
        .map((file) => `<script data-file="${file.name}" data-auto-injected="true">\n${file.code ?? ''}\n</script>`)
        .join('\n');

    if (unreferencedScripts) {
        out = /<\/body>/i.test(out)
            ? out.replace(/<\/body>/i, (match) => `${unreferencedScripts}\n${match}`)
            : `${out}\n${unreferencedScripts}`;
    }

    return out;
}

export function buildPreviewDocument(code, files = []) {
    const list = Array.isArray(files) ? files : [];
    const source = String(code ?? '');
    const prepared = linkVirtualAssets(source, list);
    if (!/^<!doctype\s+html|^<html/i.test(prepared.trim())) {
        return `<!DOCTYPE html>\n<html lang="id">\n<head>\n<meta charset="utf-8">\n${captureScript}</head>\n<body>\n${prepared}\n</body>\n</html>`;
    }
    if (/<head[\s>]/i.test(prepared)) return prepared.replace(/<head([^>]*)>/i, (match) => `${match}\n${captureScript}`);
    if (/<body[\s>]/i.test(prepared)) return prepared.replace(/<body([^>]*)>/i, (match) => `${match}\n${captureScript}`);

    return captureScript + prepared;
}

export function mainDocument(files = [], activeName = '') {
    const list = Array.isArray(files) ? files : [];
    if (activeName) {
        const activeMatch = list.find((f) => f.name.toLowerCase() === activeName.toLowerCase());
        if (activeMatch && (isName(activeMatch.name, '.html') || isName(activeMatch.name, '.htm') || /^<!doctype\s+html|^<html/i.test(activeMatch.code?.trim() || ''))) {
            return activeMatch;
        }
    }
    return list.find((file) => file.name.toLowerCase() === 'index.html')
        ?? list.find((file) => isName(file.name, '.html') || isName(file.name, '.htm'))
        ?? list.find((file) => /^<!doctype\s+html|^<html/i.test(file.code?.trim() || ''))
        ?? list[list.length - 1]
        ?? { name: 'index.html', code: '' };
}

let teardown = null;

// Renders `files` in the sandboxed iframe. Console and runtime error messages
// keep flowing to `onOutput` for as long as the preview lives; starting a new
// run detaches the previous listener and replaces the document.
export function runWeb({ code, files = [], activeName = '', frame, onOutput, signal }) {
    return new Promise((resolve, reject) => {
        if (!frame || typeof frame.srcdoc !== 'string') {
            reject(new Error('Panel pratinjau tidak tersedia. Muat ulang halaman.'));
            return;
        }
        const list = code !== undefined && !files.length
            ? [{ name: 'index.html', code }]
            : files;
        const htmlSource = mainDocument(list, activeName).code;
        teardown?.();
        let finished = false;
        let timeoutId = null;
        const finish = () => {
            if (finished) return;
            finished = true;
            signal?.removeEventListener('abort', finish);
            if (timeoutId !== null) window.clearTimeout(timeoutId);
            timeoutId = null;
        };
        const onMessage = (event) => {
            if (event.source !== frame.contentWindow) return;
            const data = event.data;
            if (!data || data.$source !== 'sale-web-preview') return;
            if (data.type === 'ready' && !finished) {
                finish();
                resolve();
                return;
            }
            onOutput(data.type, data.text);
        };
        teardown = () => {
            if (timeoutId !== null) window.clearTimeout(timeoutId);
            timeoutId = null;
            finish();
            window.removeEventListener('message', onMessage);
        };
        signal?.addEventListener('abort', finish, { once: true });
        if (signal?.aborted) return teardown();
        window.addEventListener('message', onMessage);
        frame.srcdoc = buildPreviewDocument(htmlSource, list);
        // A blocking script prevents the ready message; resolve anyway so the
        // workbench returns to idle without waiting forever.
        timeoutId = window.setTimeout(() => {
            timeoutId = null;
            if (!finished) {
                finished = true;
                resolve();
            }
        }, 8000);
    });
}