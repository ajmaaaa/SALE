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

function fileMatches(href, files) {
    const base = String(href).replace(/^\.\//, '').split('/').pop();
    return files.some((file) => file.name.trim().toLowerCase() === base.toLowerCase());
}

// Removes <link rel="stylesheet" href="x.css"> and <script src="y.js"></script>
// tags that point to virtual files; their content is bundled below instead.
function dropReferenced(source, files) {
    let out = source.replace(/<link\b[^>]*\brel=["']?stylesheet["']?[^>]*href=["']([^"']+)["'][^>]*>/gi, (match, href) => fileMatches(href, files) ? '' : match);
    out = out.replace(/<link\b[^>]*href=["']([^"']+)["'][^>]*\brel=["']?stylesheet["']?[^>]*>/gi, (match, href) => fileMatches(href, files) ? '' : match);
    out = out.replace(/<script\b[^>]*src=["']([^"']+)["'][^>]*>\s*<\/script>/gi, (match, href) => fileMatches(href, files) ? '' : match);
    out = out.replace(/<script\b[^>]*src=["']([^"']+)["'][^>]*\/>/gi, (match, href) => fileMatches(href, files) ? '' : match);

    return out;
}

function bundleStyles(files) {
    return files.filter((file) => isName(file.name, '.css'))
        .map((file) => `<style data-file="${file.name}">\n${file.code ?? ''}\n</style>`);
}

function bundleScripts(files) {
    return files.filter((file) => isName(file.name, '.js'))
        .map((file) => `<script data-file="${file.name}">\n${file.code ?? ''}\n</script>`);
}

function injectBundles(source, styles, scripts) {
    let out = source;
    const styleBlock = styles.join('\n');
    if (styleBlock) {
        out = /<\/head>/i.test(out) ? out.replace(/<\/head>/i, (match) => `${styleBlock}\n${match}`) : out + `\n${styleBlock}`;
    }
    const scriptBlock = scripts.join('\n');
    if (scriptBlock) {
        out = /<\/body>/i.test(out) ? out.replace(/<\/body>/i, (match) => `${scriptBlock}\n${match}`) : out + `\n${scriptBlock}`;
    }

    return out;
}

export function buildPreviewDocument(code, files = []) {
    const list = Array.isArray(files) ? files : [];
    const source = String(code ?? '');
    const prepared = injectBundles(dropReferenced(source, list), bundleStyles(list), bundleScripts(list));
    if (!/^<!doctype\s+html|^<html/i.test(prepared.trim())) {
        return `<!DOCTYPE html>\n<html lang="id">\n<head>\n<meta charset="utf-8">\n${captureScript}</head>\n<body>\n${prepared}\n</body>\n</html>`;
    }
    if (/<head[\s>]/i.test(prepared)) return prepared.replace(/<head([^>]*)>/i, (match) => `${match}\n${captureScript}`);
    if (/<body[\s>]/i.test(prepared)) return prepared.replace(/<body([^>]*)>/i, (match) => `${match}\n${captureScript}`);

    return captureScript + prepared;
}

export function mainDocument(files = []) {
    const list = Array.isArray(files) ? files : [];
    return list.find((file) => isName(file.name, '.html')) ?? list[list.length - 1] ?? { name: 'index.html', code: '' };
}

let teardown = null;

// Renders `files` in the sandboxed iframe. Console and runtime error messages
// keep flowing to `onOutput` for as long as the preview lives; starting a new
// run detaches the previous listener and replaces the document.
export function runWeb({ code, files = [], frame, onOutput, signal }) {
    return new Promise((resolve, reject) => {
        if (!frame || typeof frame.srcdoc !== 'string') {
            reject(new Error('Panel pratinjau tidak tersedia. Muat ulang halaman.'));
            return;
        }
        const list = code !== undefined && !files.length
            ? [{ name: 'index.html', code }]
            : files;
        const htmlSource = mainDocument(list).code;
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