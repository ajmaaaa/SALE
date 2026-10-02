// A fresh worker per run prevents Python state leaking between exercises. Only
// .py files execute; support files run as modules, then the main file
// (main.py or the last .py tab). Non-.py files are treated as raw data.
self.onmessage = async ({ data: { files, code, suite, runtimeUrl, activeFile } }) => {
    const send = self.postMessage.bind(self);
    let size = 0;
    const output = (stream, text) => {
        size += text.length;
        if (size > 64000) throw new Error('Output melebihi batas 64.000 karakter.');
        send({ type: 'output', stream, text });
    };
    try {
        const { loadPyodide } = await import(/* @vite-ignore */ `${runtimeUrl}pyodide.mjs`);
        const python = await loadPyodide({ indexURL: runtimeUrl, jsglobals: {},
            stdout: (text) => output('stdout', text), stderr: (text) => output('stderr', text),
            stdin: () => null,
        });
        send({ type: 'ready', version: python.runPython('import sys; sys.version.split()[0]') });
        const list = Array.isArray(files) && files.length ? files : [{ name: activeFile || 'untitled', code: code ?? '' }];
        python.globals.set('student_files_json', JSON.stringify(list));
        python.globals.set('test_source', suite ?? '');
        python.globals.set('active_file_name', activeFile || '');
        // Separate compilation keeps student syntax errors and line numbers readable.
        const exitCode = python.runPython(`
import json, unittest, traceback, types, io, sys
student_files = json.loads(student_files_json)
py_files = [item for item in student_files if not item['name'].lower().endswith(('.html', '.htm', '.css', '.js'))]
if not py_files:
    raise RuntimeError('Tidak ada berkas Python untuk dijalankan.')
names = [item['name'] for item in py_files]
main_index = -1
if active_file_name and active_file_name in names:
    main_index = names.index(active_file_name)
elif 'main.py' in names:
    main_index = names.index('main.py')
elif 'untitled' in names:
    main_index = names.index('untitled')
elif 'untitled.py' in names:
    main_index = names.index('untitled.py')
else:
    main_index = 0
main_item = py_files[main_index]
namespace = {'__name__': '__main__'}
exit_code = 0
try:
    for index, item in enumerate(py_files):
        if index != main_index:
            mod_name = item['name'][:-3] if item['name'].endswith('.py') else item['name']
            support = types.ModuleType(mod_name)
            exec(compile(item['code'], item['name'], 'exec'), support.__dict__)
            sys.modules[mod_name] = support
    exec(compile(main_item['code'], main_item['name'], 'exec'), namespace)
    if test_source:
        namespace['__name__'] = '__practice__'
        exec(compile(test_source, 'practice_tests.py', 'exec'), namespace)
        module = types.ModuleType('practice_tests')
        module.__dict__.update(namespace)
        result = unittest.TextTestRunner(verbosity=2).run(unittest.defaultTestLoader.loadTestsFromModule(module))
        exit_code = 0 if result.wasSuccessful() else 1
except BaseException:
    traceback.print_exc()
    exit_code = 1
exit_code
`);
        send({ type: 'done', exitCode });
    } catch (error) {
        send({ type: 'error', message: String(error.message || error) });
    }
};