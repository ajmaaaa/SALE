import workerSource from './python.worker.js?raw';
import bstSuite from '../../app/Services/Ai/TestSuites/task_1.py?raw';

export function runPython({ files, code, assignmentId, testAssignment = false, runtimeUrl, onOutput, onReady, signal }) {
    return new Promise((resolve, reject) => {
        // Laravel and Vite use different origins in development. A blob worker
        // belongs to the page origin; runtime imports use the absolute runtimeUrl.
        const workerUrl = URL.createObjectURL(new Blob([workerSource], { type: 'text/javascript' }));
        let worker;
        try {
            worker = new Worker(workerUrl, { type: 'module' });
        } catch (error) {
            URL.revokeObjectURL(workerUrl);
            reject(error);
            return;
        }
        let finished = false;
        let outputSize = 0;
        let timer;
        const finish = (error, result) => {
            if (finished) return;
            finished = true;
            clearTimeout(timer);
            signal?.removeEventListener('abort', cancel);
            worker.terminate();
            URL.revokeObjectURL(workerUrl);
            if (error) reject(error); else resolve(result);
        };
        const cancel = () => finish(new Error('Eksekusi dihentikan.'));
        const deadline = (ms, message) => {
            clearTimeout(timer);
            timer = setTimeout(() => finish(new Error(message)), ms);
        };
        signal?.addEventListener('abort', cancel, { once: true });
        if (signal?.aborted) return cancel();
        deadline(60000, 'Python belum berhasil dimuat. Muat ulang halaman dan coba lagi.');
        worker.onerror = () => finish(new Error('Runtime Python gagal dimuat. Coba muat ulang halaman.'));
        worker.onmessage = ({ data }) => {
            if (data.type === 'ready') {
                onReady(data.version);
                deadline(10000, 'Eksekusi dihentikan setelah 10 detik. Periksa perulangan tanpa akhir.');
            } else if (data.type === 'output') {
                outputSize += data.text.length;
                if (outputSize > 64000) return finish(new Error('Output melebihi batas 64.000 karakter.'));
                onOutput(data.stream, data.text);
            } else if (data.type === 'done') finish(null, data.exitCode);
            else if (data.type === 'error') finish(new Error(data.message));
        };
        worker.postMessage({
            files: Array.isArray(files) && files.length ? files : [{ name: 'main.py', code: code ?? '' }],
            suite: testAssignment && String(assignmentId) === '1' ? bstSuite : '',
            runtimeUrl,
        });
    });
}
