import './submission-preview';
import './grade-import';
import { runWeb } from './web-preview';

const sidebar = document.querySelector('[data-sidebar]');
const sidebarBackdrop = document.querySelector('[data-sidebar-backdrop]');
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');

const setSidebar = (open) => {
    if (!sidebar || !sidebarBackdrop || !sidebarToggle) return;

    sidebar.dataset.open = String(open);
    sidebarBackdrop.dataset.open = String(open);
    sidebarToggle.setAttribute('aria-expanded', String(open));
    document.body.classList.toggle('overflow-hidden', open);
};

sidebarToggle?.addEventListener('click', () => setSidebar(sidebar?.dataset.open !== 'true'));
sidebarBackdrop?.addEventListener('click', () => setSidebar(false));

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setSidebar(false);
});

if (sidebar) {
    sidebar.addEventListener('wheel', (e) => {
        const nav = sidebar.querySelector('nav');
        if (!nav) {
            e.preventDefault();
            return;
        }
        const isScrollable = nav.scrollHeight > nav.clientHeight;
        if (!isScrollable) {
            e.preventDefault();
            return;
        }
        const atTop = nav.scrollTop <= 0 && e.deltaY < 0;
        const atBottom = nav.scrollTop + nav.clientHeight >= nav.scrollHeight - 1 && e.deltaY > 0;
        if (atTop || atBottom) {
            e.preventDefault();
        }
    }, { passive: false });

    sidebar.addEventListener('touchmove', (e) => {
        const nav = sidebar.querySelector('nav');
        if (!nav || nav.scrollHeight <= nav.clientHeight) {
            e.preventDefault();
        }
    }, { passive: false });
}

const editorMount = document.querySelector('[data-code-editor]');
const editorSource = document.querySelector('[data-code-files-json]');

if (editorMount && editorSource) {
    const isWeb = editorMount.dataset.codeLanguage === 'web';
    const maxFiles = Number(editorMount.dataset.maxFiles) || 5;
    const maxFileChars = Number(editorMount.dataset.maxFileChars) || 8000;
    const maxTotalChars = Number(editorMount.dataset.maxTotalChars) || 20000;
    const ALLOWED = ['html', 'htm', 'css', 'js', 'py'];
    const DEFAULT_EXT = isWeb ? 'html' : 'py';
    let defaultFiles = [];
    try { defaultFiles = JSON.parse(editorSource.value || '[]'); } catch { /* keep template. */ }
    if (!Array.isArray(defaultFiles) || !defaultFiles.length) defaultFiles = [{ name: `main.${DEFAULT_EXT}`, code: '' }];

    // Horizontal Workbench Resizers (Soal <-> Editor <-> Lumina AI) - Synchronous execution
    const workbenchContainer = document.querySelector('#workbench-container');
    const panelQuestion = document.querySelector('#panel-question');
    const panelAi = document.querySelector('#panel-ai');
    const leftResizer = document.querySelector('[data-resizer="left"]');
    const rightResizer = document.querySelector('[data-resizer="right"]');

    if (workbenchContainer && panelQuestion && panelAi) {
        const syncLeft = (width) => {
            const w = Math.max(220, Math.min(600, width));
            document.documentElement.style.setProperty('--workbench-left-width', `${w}px`);
            panelQuestion.style.width = `${w}px`;
            return w;
        };
        const syncRight = (width) => {
            const w = Math.max(250, Math.min(600, width));
            document.documentElement.style.setProperty('--workbench-right-width', `${w}px`);
            panelAi.style.width = `${w}px`;
            return w;
        };

        const savedLeft = localStorage.getItem('sale.workbench.leftWidth');
        if (savedLeft && window.innerWidth >= 1280) {
            const w = parseInt(savedLeft, 10);
            if (!isNaN(w)) syncLeft(w);
        }
        const savedRight = localStorage.getItem('sale.workbench.rightWidth');
        if (savedRight && window.innerWidth >= 1280) {
            const w = parseInt(savedRight, 10);
            if (!isNaN(w)) syncRight(w);
        }

        if (leftResizer) {
            let isDraggingLeft = false;

            const onLeftPointerMove = (e) => {
                if (!isDraggingLeft) return;
                const rect = workbenchContainer.getBoundingClientRect();
                const maxW = Math.min(600, rect.width - 550);
                const newWidth = Math.max(220, Math.min(maxW, e.clientX - rect.left));
                syncLeft(newWidth);
            };

            const onLeftPointerUp = () => {
                if (!isDraggingLeft) return;
                isDraggingLeft = false;
                document.body.classList.remove('workbench-resizing', 'workbench-resizing-col');
                window.removeEventListener('pointermove', onLeftPointerMove);
                window.removeEventListener('pointerup', onLeftPointerUp);
                const finalW = parseInt(panelQuestion.style.width, 10);
                if (!isNaN(finalW)) {
                    localStorage.setItem('sale.workbench.leftWidth', finalW);
                }
            };

            leftResizer.addEventListener('pointerdown', (e) => {
                e.preventDefault();
                isDraggingLeft = true;
                document.body.classList.add('workbench-resizing', 'workbench-resizing-col');
                window.addEventListener('pointermove', onLeftPointerMove);
                window.addEventListener('pointerup', onLeftPointerUp);
            });
        }

        if (rightResizer) {
            let isDraggingRight = false;

            const onRightPointerMove = (e) => {
                if (!isDraggingRight) return;
                const rect = workbenchContainer.getBoundingClientRect();
                const maxW = Math.min(600, rect.width - 550);
                const newWidth = Math.max(250, Math.min(maxW, rect.right - e.clientX));
                syncRight(newWidth);
            };

            const onRightPointerUp = () => {
                if (!isDraggingRight) return;
                isDraggingRight = false;
                document.body.classList.remove('workbench-resizing', 'workbench-resizing-col');
                window.removeEventListener('pointermove', onRightPointerMove);
                window.removeEventListener('pointerup', onRightPointerUp);
                const finalW = parseInt(panelAi.style.width, 10);
                if (!isNaN(finalW)) {
                    localStorage.setItem('sale.workbench.rightWidth', finalW);
                }
            };

            rightResizer.addEventListener('pointerdown', (e) => {
                e.preventDefault();
                isDraggingRight = true;
                document.body.classList.add('workbench-resizing', 'workbench-resizing-col');
                window.addEventListener('pointermove', onRightPointerMove);
                window.addEventListener('pointerup', onRightPointerUp);
            });
        }
    }

    Promise.all([
        import('codemirror'),
        import('@codemirror/lang-python'),
        import('@codemirror/lang-html'),
        import('@codemirror/lang-css'),
        import('@codemirror/lang-javascript'),
        import('@codemirror/theme-one-dark'),
        import('@codemirror/state'),
    ]).then(([cm, langPy, langHtml, langCss, langJs, { oneDark }, { Compartment }]) => {
        const { basicSetup, EditorView } = cm;
        const { python } = langPy;
        const { html } = langHtml;
        const { css } = langCss;
        const { javascript } = langJs;
        const modeFor = (name) => {
            const ext = String(name).split('.').pop().toLowerCase();
            if (ext === 'css') return css();
            if (ext === 'js') return javascript();
            if (ext === 'py') return python();
            return isWeb ? html() : python();
        };

        let files = defaultFiles.map((file) => ({ name: String(file?.name || `main.${DEFAULT_EXT}`), code: String(file?.code ?? '') }));
        const draftKey = `sale.code.assignment.${editorMount.dataset.assignmentId}.${editorMount.dataset.codeLanguage || 'python'}`;
        try {
            const raw = localStorage.getItem(draftKey);
            if (raw) {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed) && parsed.length) {
                    files = parsed.map((file) => ({ name: String(file?.name || `main.${DEFAULT_EXT}`), code: String(file?.code ?? '') }));
                }
            }
        } catch { /* Storage is optional. */ }

        let active = Math.max(0, files.findIndex((file) => isWeb ? /\.htm?l$/i.test(file.name) : file.name === 'main.py'));

        let context = null;
        const mention = document.querySelector('[data-mention-code]');
        const saveStatus = document.querySelector('[data-code-save-status]');
        const fileNameEl = document.querySelector('[data-code-filename]');
        const fileTabs = document.querySelector('[data-file-tabs]');
        const counterEl = document.querySelector('[data-chars-count]');
        const fileLanguageBadge = document.querySelector('[data-file-language-badge]');
        document.querySelector('[data-code-submit] button')?.removeAttribute('disabled');

        const flush = () => { if (files[active]) files[active].code = editor.state.doc.toString(); };
        const updateCounter = () => {
            const current = files[active]?.code.length ?? 0;
            const total = files.reduce((sum, file) => sum + file.code.length, 0);
            if (counterEl) counterEl.textContent = `${current.toLocaleString('id-ID')}/${maxFileChars.toLocaleString('id-ID')} char · total ${total.toLocaleString('id-ID')}/${maxTotalChars.toLocaleString('id-ID')}`;
        };
        const setStatus = (text, temporary = false) => {
            if (!saveStatus) return;
            saveStatus.textContent = text;
            if (temporary) setTimeout(() => { saveStatus.textContent = 'Draf tersimpan di browser ini'; }, 2600);
        };
        const persist = () => {
            flush();
            try {
                localStorage.setItem(draftKey, JSON.stringify(files));
                saveStatus.textContent = 'Draf tersimpan di browser ini';
            } catch { saveStatus.textContent = 'Draf belum tersimpan; penyimpanan browser tidak tersedia'; }
            updateCounter();
        };
        const validate = () => {
            if (files.length > maxFiles) return `Maksimal ${maxFiles} berkas per tugas.`;
            if (files.some((file) => file.code.length > maxFileChars)) return `Satu berkas melebihi batas ${maxFileChars.toLocaleString('id-ID')} karakter.`;
            if (files.reduce((sum, file) => sum + file.code.length, 0) > maxTotalChars) return `Total kode melebihi batas ${maxTotalChars.toLocaleString('id-ID')} karakter.`;
            const badExt = files.some((file) => {
                const ext = String(file.name).split('.').pop().toLowerCase();
                return ext !== '' && !ALLOWED.includes(ext);
            });
            if (badExt) return `Ekstensi berkas tidak diizinkan. Gunakan .html, .css, .js, atau .py.`;
            return null;
        };

        const compartment = new Compartment();
        const editor = new EditorView({
            doc: files[active].code,
            extensions: [
                basicSetup,
                compartment.of(modeFor(files[active].name)),
                oneDark,
                EditorView.lineWrapping,
                EditorView.updateListener.of((update) => {
                    if (mention) mention.disabled = update.state.selection.main.empty;
                    if (update.docChanged) {
                        files[active].code = update.state.doc.toString();
                        try {
                            localStorage.setItem(draftKey, JSON.stringify(files));
                            saveStatus.textContent = 'Draf tersimpan di browser ini';
                        } catch { saveStatus.textContent = 'Draf belum tersimpan; penyimpanan browser tidak tersedia'; }
updateCounter();
        refreshLanguageBadge();
                    }
                }),
            ],
            parent: editorMount,
        });
        updateCounter();

        window.setWorkbenchCode = (newCode) => {
            if (editor && files && files[active]) {
                editor.dispatch({
                    changes: { from: 0, to: editor.state.doc.length, insert: newCode }
                });
                files[active].code = newCode;
                updateCounter();
            }
        };

        const refreshLanguageBadge = () => {
            const key = String(files[active].name).split('.').pop().toLowerCase();
            const meta = FILE_ICONS[key];
            if (fileLanguageBadge) {
                fileLanguageBadge.innerHTML = buildIcon(files[active].name, 18);
                fileLanguageBadge.title = meta?.label || key || 'File';
                fileLanguageBadge.className = 'flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-white/70';
                fileLanguageBadge.dataset.fileLanguage = key;
            }
        };

        const load = (index) => {
            active = index;
            editor.dispatch({
                changes: { from: 0, to: editor.state.doc.length, insert: files[active].code },
                effects: compartment.reconfigure(modeFor(files[active].name)),
            });
            if (fileNameEl) fileNameEl.textContent = files[active].name;
            refreshLanguageBadge();
            renderTabs();
        };
        const select = (index) => {
            if (index === active) { flush(); return; }
            flush();
            load(index);
        };

        const FILE_ICONS = {
            py: {
                label: 'Python',
                icon: '<svg width="@SIZE@" height="@SIZE@" viewBox="0 8 128 108" aria-hidden="true"><path fill="#3776AB" d="M63.391 1.988c-4.222.02-8.252.379-11.8 1.007-10.45 1.846-12.346 5.71-12.346 12.837v9.411h24.693v3.137H29.977c-7.176 0-13.46 4.313-15.426 12.521-2.268 9.405-2.368 15.275 0 25.096 1.755 7.311 5.947 12.519 13.124 12.519h8.491V67.234c0-8.151 7.051-15.34 15.426-15.34h24.665c6.866 0 12.346-5.654 12.346-12.548V15.833c0-6.693-5.646-11.72-12.346-12.837-4.244-.706-8.645-1.027-12.866-1.008zM50.037 9.557c2.55 0 4.634 2.117 4.634 4.721 0 2.593-2.083 4.69-4.634 4.69-2.56 0-4.633-2.097-4.633-4.69-.001-2.604 2.073-4.721 4.633-4.721z" transform="translate(0 10.26)"/><path fill="#FFD43B" d="M91.682 28.38v10.966c0 8.5-7.208 15.655-15.426 15.655H51.591c-6.756 0-12.346 5.783-12.346 12.549v23.515c0 6.691 5.818 10.628 12.346 12.547 7.816 2.297 15.312 2.713 24.665 0 6.216-1.801 12.346-5.423 12.346-12.547v-9.412H63.938v-3.138h37.012c7.176 0 9.852-5.005 12.348-12.519 2.578-7.735 2.467-15.174 0-25.096-1.774-7.145-5.161-12.521-12.348-12.521h-9.268zM77.809 87.927c2.561 0 4.634 2.097 4.634 4.692 0 2.602-2.074 4.719-4.634 4.719-2.55 0-4.633-2.117-4.633-4.719 0-2.595 2.083-4.692 4.633-4.692z" transform="translate(0 10.26)"/></svg>',
            },
            html: {
                label: 'HTML',
                icon: '<svg width="@SIZE@" height="@SIZE@" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" fill="#E34F26" d="M1.5 0h21l-1.91 21.563L11.977 24l-8.564-2.438L1.5 0zm7.031 9.75l-.232-2.718 10.059.003.23-2.622L5.412 4.41l.698 8.01h9.126l-.326 3.426-2.91.804-2.955-.81-.188-2.11H6.248l.33 4.171L12 19.351l5.379-1.443.744-8.157H8.531z"/></svg>',
            },
            htm: {
                label: 'HTML',
                icon: '<svg width="@SIZE@" height="@SIZE@" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" fill="#E34F26" d="M1.5 0h21l-1.91 21.563L11.977 24l-8.564-2.438L1.5 0zm7.031 9.75l-.232-2.718 10.059.003.23-2.622L5.412 4.41l.698 8.01h9.126l-.326 3.426-2.91.804-2.955-.81-.188-2.11H6.248l.33 4.171L12 19.351l5.379-1.443.744-8.157H8.531z"/></svg>',
            },
            css: {
                label: 'CSS',
                icon: '<svg width="@SIZE@" height="@SIZE@" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" fill="#1572B6" d="M1.5 0h21l-1.91 21.563L11.977 24l-8.564-2.438L1.5 0zm17.09 4.413L5.41 4.41l.213 2.622 10.125.002-.255 2.716h-6.64l.24 2.573h6.182l-.366 3.523-2.91.804-2.956-.81-.188-2.11h-2.61l.29 3.855L12 19.288l5.373-1.53L18.59 4.414z"/></svg>',
            },
            js: {
                label: 'JavaScript',
                icon: '<svg width="@SIZE@" height="@SIZE@" viewBox="0 0 128 128" aria-hidden="true"><path fill="#F0DB4F" d="M1.408 1.408h125.184v125.185H1.408z"/><path fill="#323330" d="M116.347 96.736c-.917-5.711-4.641-10.508-15.672-14.981-3.832-1.761-8.104-3.022-9.377-5.926-.452-1.69-.512-2.642-.226-3.665.821-3.32 4.784-4.355 7.925-3.403 2.023.678 3.938 2.237 5.093 4.724 5.402-3.498 5.391-3.475 9.163-5.879-1.381-2.141-2.118-3.129-3.022-4.045-3.249-3.629-7.676-5.498-14.756-5.355l-3.688.477c-3.534.893-6.902 2.748-8.877 5.235-5.926 6.724-4.236 18.492 2.975 23.335 7.104 5.332 17.54 6.545 18.873 11.531 1.297 6.104-4.486 8.08-10.234 7.378-4.236-.881-6.592-3.034-9.139-6.949-4.688 2.713-4.688 2.713-9.508 5.485 1.143 2.499 2.344 3.63 4.26 5.795 9.068 9.198 31.76 8.746 35.83-5.176.165-.478 1.261-3.666.38-8.581zM69.462 58.943H57.753l-.048 30.272c0 6.438.333 12.34-.714 14.149-1.713 3.558-6.152 3.117-8.175 2.427-2.059-1.012-3.106-2.451-4.319-4.485-.333-.584-.583-1.036-.667-1.071l-9.52 5.83c1.583 3.249 3.915 6.069 6.902 7.901 4.462 2.678 10.459 3.499 16.731 2.059 4.082-1.189 7.604-3.652 9.448-7.401 2.666-4.915 2.094-10.864 2.07-17.444.06-10.735.001-21.468.001-32.237z"/></svg>',
            },
        };
        const FILE_ICON_FALLBACK = '<svg width="@SIZE@" height="@SIZE@" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h8l4 4v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z" fill="#cbd5e1"/><path d="M15 3l4 4h-4z" fill="#94a3b8"/></svg>';
        const buildIcon = (name, size = 14) => {
            const key = String(name).split('.').pop().toLowerCase();
            const meta = FILE_ICONS[key];
            return (meta?.icon || FILE_ICON_FALLBACK).replace('@SIZE@', String(size));
        };
        const languageBadge = (name) => {
            const key = String(name).split('.').pop().toLowerCase();
            const meta = FILE_ICONS[key];
            const badge = document.createElement('span');
            badge.dataset.fileLanguage = key;
            badge.title = meta?.label || key || 'File';
            badge.className = 'flex shrink-0 items-center';
            badge.innerHTML = buildIcon(name);
            return badge;
        };

        function renderTabs() {
            if (!fileTabs) return;
            fileTabs.replaceChildren();
            files.forEach((file, index) => {
                const tab = document.createElement('div');
                tab.dataset.fileTab = file.name;
                tab.className = `group flex shrink-0 items-center gap-1 rounded-md border px-2 py-1 text-[11px] font-mono ${index === active ? 'border-brand/25 bg-brand/10 text-brand-dark' : 'border-transparent text-muted hover:bg-white'}`;
                const core = document.createElement('button');
                core.type = 'button';
                core.dataset.fileSelect = '';
                core.title = file.name;
                core.className = 'max-w-44 truncate font-semibold';
                core.textContent = file.name;
                core.addEventListener('click', () => select(index));
                core.addEventListener('dblclick', () => startRename(index));
                const renameBtn = document.createElement('button');
                renameBtn.type = 'button';
                renameBtn.dataset.fileRename = '';
                renameBtn.title = 'Ubah nama berkas';
                renameBtn.setAttribute('aria-label', `Ubah nama ${file.name}`);
                renameBtn.className = 'px-0.5 leading-none text-muted opacity-60 hover:text-ink group-hover:opacity-100';
                renameBtn.textContent = 'Ubah';
                renameBtn.addEventListener('click', () => startRename(index));
                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.dataset.fileDelete = '';
                deleteBtn.title = 'Hapus berkas';
                deleteBtn.setAttribute('aria-label', `Hapus ${file.name}`);
                deleteBtn.className = 'px-0.5 leading-none text-muted opacity-60 hover:text-danger group-hover:opacity-100';
                deleteBtn.textContent = '×';
                deleteBtn.addEventListener('click', () => removeFile(index));
                tab.append(languageBadge(file.name), core, renameBtn, deleteBtn);
                fileTabs.append(tab);
            });
            const addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.dataset.fileAdd = '';
            addBtn.title = 'Tambah berkas baru';
            addBtn.className = 'shrink-0 rounded-md border border-dashed border-line/70 px-2 py-1 text-[11px] font-semibold text-muted hover:bg-white hover:text-ink';
            addBtn.textContent = '+ Berkas';
            addBtn.addEventListener('click', addFile);
            fileTabs.append(addBtn);
        }

        let renameInput = null;
        function startRename(index) {
            if (renameInput) { renameInput.remove(); renameInput = null; }
            const tab = fileTabs?.children[index];
            const core = tab?.querySelector('[data-file-select]');
            if (!core) return;
            const input = document.createElement('input');
            input.type = 'text';
            input.value = String(files[index].name).replace(/\.\w+$/, '');
            input.spellcheck = false;
            input.setAttribute('aria-label', 'Nama berkas baru');
            input.className = 'w-28 rounded border border-brand/50 bg-white px-1.5 py-0.5 text-[11px] font-mono text-ink outline-none';
            core.replaceWith(input);
            renameInput = input;
            input.focus();
            input.select();
            let settled = false;
            const finish = (commit) => {
                if (settled) return;
                settled = true;
                renameInput = null;
                const candidate = input.value.trim();
                input.remove();
                if (commit) commitRename(index, candidate);
                renderTabs();
            };
            input.addEventListener('keydown', (event) => {
                event.stopPropagation();
                if (event.key === 'Enter') finish(true);
                if (event.key === 'Escape') finish(false);
            });
            input.addEventListener('blur', () => finish(true));
            if (fileTabs) fileTabs.scrollLeft = fileTabs.scrollWidth;
        }

        function commitRename(index, candidate) {
            if (!candidate) return setStatus('Nama berkas tidak boleh kosong.', true);
            if (candidate.includes('/') || candidate.includes('\\')) return setStatus('Nama berkas tidak boleh memuat jalur folder.', true);
            const lastDot = candidate.lastIndexOf('.');
            let defaultForCandidate = DEFAULT_EXT;
            const lower = candidate.toLowerCase();
            if (lower.startsWith('style') || lower === 'css') defaultForCandidate = 'css';
            else if (lower.startsWith('script') || lower === 'js' || lower === 'app') defaultForCandidate = 'js';
            else if (lower.startsWith('index') || lower === 'html' || lower === 'page') defaultForCandidate = 'html';
            const ext = lastDot >= 0 ? candidate.slice(lastDot + 1).toLowerCase() : defaultForCandidate;
            const name = lastDot >= 0 ? candidate : `${candidate}.${ext}`;
            if (!ALLOWED.includes(ext)) return setStatus(`Ekstensi .${ext} tidak diizinkan. Gunakan .html, .css, .js, atau .py.`, true);
            if (files.some((file, i) => i !== index && file.name.toLowerCase() === name.toLowerCase())) return setStatus(`Berkas "${name}" sudah ada.`, true);
            files[index].name = name;
            if (index === active) {
                if (fileNameEl) fileNameEl.textContent = name;
                editor.dispatch({ effects: compartment.reconfigure(modeFor(name)) });
            }
            persist();
        }

        function addFile() {
            if (files.length >= maxFiles) return setStatus(`Maksimal ${maxFiles} berkas.`, true);
            let n = 1;
            let name;
            do { name = `berkas-${n}`; n += 1; } while (files.some((file) => file.name.toLowerCase() === name.toLowerCase()));
            flush();
            files.push({ name, code: '' });
            active = files.length - 1;
            load(active);
            startRename(active);
        }

        function removeFile(index) {
            if (files.length <= 1) return setStatus('Berkas terakhir tidak dapat dihapus.', true);
            if (!window.confirm(`Hapus berkas "${files[index].name}"?`)) return;
            const wasActive = index === active;
            flush();
            files.splice(index, 1);
            if (wasActive) {
                active = Math.min(index, files.length - 1);
                load(active);
            } else {
                if (index < active) active -= 1;
                renderTabs();
                persist();
            }
        }

        renderTabs();

        mention?.addEventListener('click', () => {
            const { from, to, empty } = editor.state.selection.main;
            if (empty) return;
            const first = editor.state.doc.lineAt(from).number;
            const last = editor.state.doc.lineAt(Math.max(from, to - 1)).number;
            context = { label: `${files[active].name} · Baris ${first}${last === first ? '' : `–${last}`}`, code: editor.state.sliceDoc(from, to) };
            document.querySelector('[data-code-context-label]').textContent = context.label;
            document.querySelector('[data-code-context-text]').textContent = context.code;
            document.querySelector('[data-code-context]').hidden = false;
            document.querySelector('#assistant-message').focus();
        });
        document.querySelector('[data-remove-context]')?.addEventListener('click', () => {
            context = null;
            document.querySelector('[data-code-context]').hidden = true;
        });
        document.querySelector('[data-code-reset]')?.addEventListener('click', () => {
            if (!window.confirm('Kembalikan semua berkas ke template awal? Draf saat ini akan diganti.')) return;
            files = defaultFiles.map((file) => ({ name: String(file?.name || `main.${DEFAULT_EXT}`), code: String(file?.code ?? '') }));
            load(0);
        });
        document.querySelector('[data-code-submit]')?.addEventListener('submit', (event) => {
            flush();
            const problem = validate();
            if (problem) {
                event.preventDefault();
                setStatus(problem, true);
                return;
            }
            document.querySelector('[data-code-answer]').value = JSON.stringify(files);
        });

        const runBtn = document.querySelector('[data-run-code]');
        const testBtn = document.querySelector('[data-test-code]');
        const stopBtn = document.querySelector('[data-stop-code]');
        const terminalOutput = document.querySelector('[data-terminal-output]');
        const terminalBody = document.querySelector('[data-terminal-body]');
        const previewFrame = document.querySelector('[data-preview-frame]');
        const outputTabs = [...document.querySelectorAll('[data-output-tab]')];
        const consolePanel = document.querySelector('[data-console-panel]');
        const previewPanel = document.querySelector('[data-preview-panel]');
        const terminalWrapper = document.querySelector('#terminal-wrapper');
        const panelTerminal = document.querySelector('#panel-terminal');
        const terminalToggleBtn = document.querySelector('[data-terminal-toggle]');
        const terminalCloseBtns = document.querySelectorAll('[data-terminal-close], [data-terminal-close-btn], [data-terminal-minimize]');
        const terminalMaximizeBtn = document.querySelector('[data-terminal-maximize]');
        const terminalResizer = document.querySelector('[data-resizer="terminal"]');
        let execution = null;

        // Terminal On-Demand Toggle Logic
        const updateTerminalToggleState = (isOpen) => {
            if (!terminalToggleBtn) return;
            terminalToggleBtn.classList.toggle('bg-brand/10', isOpen);
            terminalToggleBtn.classList.toggle('text-brand', isOpen);
            terminalToggleBtn.classList.toggle('border-brand/40', isOpen);
            terminalToggleBtn.classList.toggle('bg-white', !isOpen);
            terminalToggleBtn.classList.toggle('text-ink', !isOpen);
        };

        const openTerminal = () => {
            if (!terminalWrapper) return;
            terminalWrapper.hidden = false;
            updateTerminalToggleState(true);
        };

        const closeTerminal = () => {
            if (!terminalWrapper) return;
            terminalWrapper.hidden = true;
            updateTerminalToggleState(false);
        };

        const toggleTerminal = () => {
            if (!terminalWrapper) return;
            if (terminalWrapper.hidden) {
                openTerminal();
            } else {
                closeTerminal();
            }
        };

        terminalToggleBtn?.addEventListener('click', toggleTerminal);
        terminalCloseBtns.forEach((btn) => btn.addEventListener('click', closeTerminal));
        terminalMaximizeBtn?.addEventListener('click', () => {
            if (!panelTerminal) return;
            const currentH = panelTerminal.offsetHeight;
            if (currentH < 360) {
                panelTerminal.style.height = '420px';
            } else {
                panelTerminal.style.height = '240px';
            }
            openTerminal();
        });

        // Vertical Resizing for Terminal
        if (terminalResizer && panelTerminal) {
            const savedHeight = localStorage.getItem('sale.workbench.terminalHeight');
            if (savedHeight) {
                const h = Math.max(120, Math.min(window.innerHeight - 220, parseInt(savedHeight, 10)));
                if (!isNaN(h)) panelTerminal.style.height = `${h}px`;
            }

            let isDraggingTerminal = false;
            let startY = 0;
            let startHeight = 0;

            const onTerminalPointerMove = (e) => {
                if (!isDraggingTerminal) return;
                const deltaY = e.clientY - startY;
                const newHeight = Math.max(120, Math.min(window.innerHeight - 200, startHeight - deltaY));
                panelTerminal.style.height = `${newHeight}px`;
            };

            const onTerminalPointerUp = () => {
                if (!isDraggingTerminal) return;
                isDraggingTerminal = false;
                document.body.classList.remove('workbench-resizing', 'workbench-resizing-row');
                window.removeEventListener('pointermove', onTerminalPointerMove);
                window.removeEventListener('pointerup', onTerminalPointerUp);
                localStorage.setItem('sale.workbench.terminalHeight', parseInt(panelTerminal.style.height, 10));
            };

            terminalResizer.addEventListener('pointerdown', (e) => {
                e.preventDefault();
                isDraggingTerminal = true;
                startY = e.clientY;
                startHeight = panelTerminal.offsetHeight;
                document.body.classList.add('workbench-resizing', 'workbench-resizing-row');
                window.addEventListener('pointermove', onTerminalPointerMove);
                window.addEventListener('pointerup', onTerminalPointerUp);
            });
        }


        const appendOutput = (text, stream = 'stdout') => {
            const line = document.createElement('pre');
            line.className = `whitespace-pre-wrap break-words ${stream === 'stderr' ? 'text-amber-300' : 'text-slate-300'}`;
            line.textContent = text;
            terminalOutput.append(line);
            terminalBody.scrollTop = terminalBody.scrollHeight;
            return line;
        };
        const activateTab = (name) => {
            outputTabs.forEach((tab) => {
                const active = tab.dataset.outputTab === name;
                tab.setAttribute('aria-selected', String(active));
                tab.classList.toggle('bg-white/10', active);
                tab.classList.toggle('text-white', active);
                tab.classList.toggle('border-white/10', active);
                tab.classList.toggle('border-transparent', !active);
                tab.classList.toggle('text-slate-400', !active);
                tab.classList.toggle('hover:text-slate-200', !active);
            });
            if (consolePanel) consolePanel.hidden = name !== 'console';
            if (previewPanel) previewPanel.hidden = name !== 'preview';
        };
        outputTabs.forEach((tab) => tab.addEventListener('click', () => activateTab(tab.dataset.outputTab)));
        runBtn.disabled = false;
        if (testBtn) testBtn.disabled = false;
        const setRunBtnState = (running) => {
            if (!runBtn) return;
            runBtn.disabled = running;
            if (running) {
                runBtn.innerHTML = '<svg class="h-3.5 w-3.5 animate-spin text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor"/></svg>';
                runBtn.title = 'Sedang menjalankan…';
            } else {
                runBtn.innerHTML = '<svg class="h-3.5 w-3.5 fill-current text-white ml-0.5" viewBox="0 0 24 24" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>';
                runBtn.title = 'Jalankan kode';
            }
        };

        const isHtmlCode = (text) => /^<!doctype\s+html|^<html|^<body|^<div|^<h[1-6]|^<p[\s>]/i.test(String(text || '').trim());
        const detectRunAsWeb = (testAssignment) => {
            if (testAssignment) return false;
            if (isWeb) return true;
            const currentName = files[active]?.name?.toLowerCase() || '';
            if (currentName.endsWith('.html') || currentName.endsWith('.htm')) return true;
            if (isHtmlCode(files[active]?.code)) return true;
            const hasHtml = files.some((f) => f.name.toLowerCase().endsWith('.html') || f.name.toLowerCase().endsWith('.htm') || isHtmlCode(f.code));
            const hasPy = files.some((f) => f.name.toLowerCase().endsWith('.py'));
            if (hasHtml && !hasPy) return true;
            if ((currentName.endsWith('.css') || currentName.endsWith('.js')) && hasHtml) return true;
            return false;
        };

        const execute = async (testAssignment = false) => {
            if (execution) return;
            openTerminal();
            flush();
            const problem = validate();
            if (problem) return appendOutput(problem, 'stderr');
            const runAsWeb = detectRunAsWeb(testAssignment);
            if (!files.some((file) => file.code.trim())) return appendOutput(runAsWeb ? 'Tulis kode HTML/CSS/JS terlebih dahulu.' : 'Tulis kode Python terlebih dahulu.');
            execution = new AbortController();
            setRunBtnState(true);
            if (testBtn) testBtn.disabled = true;
            terminalOutput.replaceChildren();
            stopBtn.hidden = false;
            const progress = appendOutput(runAsWeb ? 'Menyiapkan pratinjau…' : 'Sedang mengompilasi…');
            progress.dataset.executionProgress = '';
            progress.setAttribute('role', 'status');
            try {
                if (runAsWeb) {
                    if (!previewFrame) throw new Error('Panel pratinjau tidak tersedia. Muat ulang halaman.');
                    await runWeb({
                        files,
                        activeName: files[active]?.name,
                        frame: previewFrame,
                        signal: execution.signal,
                        onOutput: (stream, text) => {
                            progress.remove();
                            appendOutput(text, stream === 'warn' || stream === 'error' ? 'stderr' : 'stdout');
                        },
                    });
                    progress.remove();
                    appendOutput('Selesai · pratinjau dirender di tab Pratinjau. Kode berjalan di iframe tanpa akses data situs.');
                    activateTab('preview');
                } else {
                    const { runPython } = await import('./python-runner');
                    const exitCode = await runPython({
                        files, assignmentId: editorMount.dataset.assignmentId, testAssignment,
                        runtimeUrl: editorMount.dataset.runtimeUrl,
                        signal: execution.signal,
                        onOutput: (stream, text) => {
                            progress.remove();
                            appendOutput(text, stream);
                        },
                        onReady: (version) => {
                            const pyVerEl = document.querySelector('[data-python-version]');
                            if (pyVerEl) pyVerEl.textContent = `Python ${version}`;
                            progress.textContent = testAssignment ? 'Sedang menguji tugas BST…' : 'Sedang menjalankan main.py…';
                        },
                    });
                    progress.remove();
                    appendOutput(exitCode === 0
                        ? 'Selesai · exit code 0.'
                        : `Eksekusi gagal · exit code ${exitCode}.`, exitCode === 0 ? 'stdout' : 'stderr');
                    activateTab('console');
                }
            } catch (error) {
                progress.remove();
                appendOutput(error.message || 'Eksekusi gagal.', 'stderr');
            } finally {
                progress.remove();
                execution = null;
                setRunBtnState(false);
                if (testBtn) testBtn.disabled = false;
                stopBtn.hidden = true;
            }
        };
        runBtn.addEventListener('click', () => execute(false));
        testBtn?.addEventListener('click', () => execute(true));
        stopBtn.addEventListener('click', () => execution?.abort());
        document.querySelector('[data-clear-terminal]')?.addEventListener('click', () => terminalOutput.replaceChildren());

        const aiForm = document.querySelector('[data-ai-form]');
        const messages = document.querySelector('[data-ai-messages]');
        const status = document.querySelector('[data-ai-status]');
        let busy = false;
        let ready = false;
        let thinkingEl = null;

        const showThinking = () => {
            if (thinkingEl) return;
            thinkingEl = document.createElement('article');
            thinkingEl.className = 'self-start mr-auto rounded-2xl rounded-tl-xs bg-white border border-line/70 p-3 shadow-xs flex items-center gap-2 text-xs text-muted';
            thinkingEl.innerHTML = `
                <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-brand/10 text-brand text-[10px] font-bold">✦</span>
                <span class="font-medium text-slate-600">Lumina AI sedang berpikir</span>
                <span class="inline-flex items-center gap-1 pl-1 py-0.5" aria-hidden="true">
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </span>
            `;
            messages.append(thinkingEl);
            messages.scrollTop = messages.scrollHeight;
        };

        const removeThinking = () => {
            if (thinkingEl) {
                thinkingEl.remove();
                thinkingEl = null;
            }
        };

        /** Minimal markdown renderer for AI responses: bold, italic, inline-code, code-block, unordered lists. */
        const renderMarkdown = (text) => {
            // Escape HTML entities first to prevent XSS
            const esc = (s) => s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            const lines = text.split('\n');
            let html = '';
            let inCode = false;
            let inList = false;
            for (let i = 0; i < lines.length; i++) {
                const raw = lines[i];
                // Fenced code block
                if (raw.trim().startsWith('```')) {
                    if (inList) { html += '</ul>'; inList = false; }
                    if (!inCode) {
                        html += '<div class="ai-code-wrap"><pre class="ai-code-block">';
                        inCode = true;
                    } else {
                        html += '</pre><button class="ai-copy-btn" data-copy-code type="button">Salin kode</button></div>';
                        inCode = false;
                    }
                    continue;
                }
                if (inCode) {
                    html += esc(raw) + '\n';
                    continue;
                }
                // Unordered list items
                if (/^[\-\*] /.test(raw)) {
                    if (!inList) { html += '<ul class="ai-list">'; inList = true; }
                    const content = inlineMarkdown(esc(raw.replace(/^[\-\*] /, '')));
                    html += `<li>${content}</li>`;
                    continue;
                }
                if (inList) { html += '</ul>'; inList = false; }
                if (raw.trim() === '') {
                    html += '<br>';
                    continue;
                }
                html += `<p class="ai-para">${inlineMarkdown(esc(raw))}</p>`;
            }
            if (inCode) html += '</pre>';
            if (inList) html += '</ul>';
            return html;
        };
        const inlineMarkdown = (s) => s
            .replace(/`([^`]+)`/g, '<code class="ai-inline-code">$1</code>')
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            .replace(/\*([^*]+)\*/g, '<em>$1</em>')
            .replace(/\_\_([^_]+)\_\_/g, '<strong>$1</strong>')
            .replace(/\_([^_]+)\_/g, '<em>$1</em>');

        const bubble = (name, text, isUser = false) => {
            const article = document.createElement('article');
            if (isUser) {
                article.className = 'self-end ml-auto max-w-[72%] rounded-2xl rounded-tr-xs bg-brand-soft border border-brand/20 p-3 shadow-xs text-ink';
                const heading = document.createElement('p');
                heading.className = 'text-[11px] font-semibold text-brand-dark mb-1 text-right';
                heading.textContent = name;
                const body = document.createElement('p');
                body.className = 'text-xs leading-relaxed text-ink whitespace-pre-wrap text-right';
                body.textContent = text;
                article.append(heading, body);
            } else {
                article.className = 'self-start mr-auto max-w-[90%] rounded-2xl rounded-tl-xs bg-white border border-line/70 p-3.5 shadow-xs text-ink';
                const heading = document.createElement('div');
                heading.className = 'flex items-center gap-1.5 mb-2';
                heading.innerHTML = `
                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-brand/10 text-brand text-[10px] font-bold">✦</span>
                    <span class="text-[11px] font-semibold text-ink">${name}</span>
                `;
                const body = document.createElement('div');
                body.className = 'text-xs leading-relaxed text-slate-700 ai-response';
                body.innerHTML = renderMarkdown(text);
                article.append(heading, body);
            }
            messages.append(article);
            messages.scrollTop = messages.scrollHeight;
        };
        const updateStatus = async (restore = false) => {
            try {
                const response = await fetch(aiForm.action, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await response.json();
                ready = response.ok && data.enabled && data.remaining_turns > 0 && data.remaining_tokens > 0;
                status.textContent = response.ok
                    ? `${data.enabled ? 'Sisa' : 'AI belum aktif · sisa'} ${data.remaining_tokens.toLocaleString('id-ID')} token · ${data.remaining_turns} permintaan tugas · reset token 07.00 WIB`
                    : (data.message || 'Status AI belum tersedia.');
                if (restore && response.ok) data.history.forEach(turn => {
                    bubble('Anda', turn.question, true);
                    bubble('Lumina AI', turn.answer || 'Permintaan sebelumnya belum menghasilkan jawaban.', false);
                });
            } catch {
                ready = false;
                status.textContent = 'Tidak dapat memuat status AI. Muat ulang halaman.';
            }
            aiForm.querySelector('button[type="submit"]').disabled = busy || !ready;
        };
        if (aiForm) updateStatus(true);

        const assistantInput = document.querySelector('#assistant-message');
        const assistantSubmitBtn = aiForm?.querySelector('button[type="submit"]');

        const updateAssistantSubmitVisibility = () => {
            if (!assistantSubmitBtn || !assistantInput) return;
            const hasText = assistantInput.value.trim().length > 0;
            if (hasText) {
                assistantSubmitBtn.classList.remove('scale-0', 'opacity-0', 'pointer-events-none');
                assistantSubmitBtn.classList.add('scale-100', 'opacity-100');
            } else {
                assistantSubmitBtn.classList.add('scale-0', 'opacity-0', 'pointer-events-none');
                assistantSubmitBtn.classList.remove('scale-100', 'opacity-100');
            }
        };

        assistantInput?.addEventListener('input', updateAssistantSubmitVisibility);
        updateAssistantSubmitVisibility();

        // Enter submits; Shift+Enter inserts a new line
        assistantInput?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (!busy && ready) aiForm.requestSubmit();
            }
        });

        // Copy code block via event delegation
        messages?.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-copy-code]');
            if (!btn) return;
            const pre = btn.closest('.ai-code-wrap')?.querySelector('pre');
            if (!pre) return;
            navigator.clipboard.writeText(pre.textContent.trimEnd()).then(() => {
                btn.textContent = 'Tersalin';
                btn.classList.add('ai-copy-btn--done');
                setTimeout(() => { btn.textContent = 'Salin'; btn.classList.remove('ai-copy-btn--done'); }, 2000);
            }).catch(() => { btn.textContent = 'Gagal'; setTimeout(() => { btn.textContent = 'Salin'; }, 1500); });
        });

        aiForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (busy || !ready) return;
            const input = document.querySelector('#assistant-message');
            const question = input.value.trim();
            if (!question || !aiForm.reportValidity()) return;
            const code = context?.code || '';
            if (code.length > 4000) {
                status.textContent = 'Pilih potongan kode maksimal 4.000 karakter.';
                return;
            }
            busy = true;
            aiForm.querySelector('button[type="submit"]').disabled = true;
            bubble('Anda', question + (context ? `\n${context.label}\n${code}` : ''), true);
            showThinking();
            status.textContent = 'Memeriksa pertanyaan dan menyiapkan bantuan…';
            try {
                const response = await fetch(aiForm.action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': aiForm.querySelector('[name="_token"]').value },
                    body: JSON.stringify({ question, code }),
                });
                const data = await response.json();
                removeThinking();
                bubble('Lumina AI', response.ok ? data.answer : (data.message || 'Permintaan tidak dapat diproses.'), false);
                if (response.ok) {
                    input.value = '';
                    context = null;
                    document.querySelector('[data-code-context]').hidden = true;
                    updateAssistantSubmitVisibility();
                }
            } catch {
                removeThinking();
                bubble('Lumina AI', 'Koneksi terputus. Muat ulang untuk memeriksa riwayat sebelum mengirim kembali.', false);
            } finally {
                removeThinking();
                busy = false;
                await updateStatus();
                updateAssistantSubmitVisibility();
            }
        });
    }).catch(() => {
        editorMount.textContent = 'Editor gagal dimuat. Muat ulang halaman untuk mencoba kembali.';
    });
}

const settingsLinks = [...document.querySelectorAll('[data-settings-link]')];
const settingsPanels = [...document.querySelectorAll('[data-settings-panel]')];

if (settingsLinks.length && settingsPanels.length) {
    const activateSettings = (hash) => {
        const target = settingsPanels.some((panel) => `#${panel.id}` === hash) ? hash : '#profil';

        settingsLinks.forEach((link) => {
            const active = link.hash === target;
            link.classList.toggle('border-brand', active);
            link.classList.toggle('border-transparent', !active);
            link.classList.toggle('text-brand', active);
            link.classList.toggle('text-muted', !active);
            link.setAttribute('aria-selected', String(active));
            link.tabIndex = active ? 0 : -1;
        });

        settingsPanels.forEach((panel) => panel.classList.toggle('hidden', `#${panel.id}` !== target));
    };

    settingsLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            history.replaceState(null, '', link.hash);
            activateSettings(link.hash);
        });
    });

    activateSettings(window.location.hash);
    window.addEventListener('hashchange', () => activateSettings(window.location.hash));
}

// Keep upload feedback local; actual file validation happens on the server.
document.querySelectorAll('[data-file-input]').forEach((input) => {
    const list = input.parentElement.querySelector('[data-file-list]');
    let files = [];
    let urls = [];
    const render = () => {
        const transfer = new DataTransfer(); files.forEach(file => transfer.items.add(file)); input.files = transfer.files;
        input.setCustomValidity(files.length > 5 || files.some(file => file.size > 20*1024*1024) ? 'Maksimal 5 berkas, masing-masing 20 MB.' : '');
        urls.forEach(url=>URL.revokeObjectURL(url)); urls=[];
        if (!list) return;
        list.replaceChildren();
        files.forEach((file,index)=>{
            const row=document.createElement('div'); row.className='flex items-center gap-3 rounded-lg bg-white p-3 shadow-sm';
            if (['image/jpeg','image/png','image/webp'].includes(file.type)) {
                const img=document.createElement('img'); const url=URL.createObjectURL(file);urls.push(url);img.src=url;img.alt='';img.className='h-12 w-12 shrink-0 rounded object-cover';row.append(img);
            } else {
                const extension = file.name.split('.').pop()?.toUpperCase() || 'FILE';
                const badge = document.createElement('span');
                const isPdf = extension === 'PDF';
                const isWord = ['DOC', 'DOCX'].includes(extension);
                const isSlides = ['PPT', 'PPTX'].includes(extension);
                const isVideo = extension === 'MP4';
                badge.className = `flex h-12 w-12 shrink-0 items-center justify-center rounded-lg text-[10px] font-bold ${isPdf ? 'bg-rose-50 text-rose-700' : isWord ? 'bg-blue-50 text-blue-700' : isSlides ? 'bg-orange-50 text-orange-700' : isVideo ? 'bg-violet-50 text-violet-700' : 'bg-slate-100 text-slate-600'}`;
                badge.textContent = extension.slice(0, 4);
                row.append(badge);
            }
            const name=document.createElement('span');name.className='min-w-0 flex-1 break-all text-xs';name.textContent=`${file.name} · ${(file.size/1024/1024).toFixed(1)} MB`;
            const remove=document.createElement('button');remove.type='button';remove.className='p-2 text-sm text-muted';remove.textContent='×';remove.setAttribute('aria-label',`Hapus ${file.name}`);remove.addEventListener('click',()=>{files.splice(index,1);render();});
            row.append(name,remove);list.append(row);
        });
    };
    input.addEventListener('change',()=>{
        for(const file of input.files) if(!files.some(existing=>existing.name===file.name && existing.size===file.size && existing.lastModified===file.lastModified)) files.push(file);
        render();
    });
});
const coverInput = document.querySelector('[data-cover-input]');
if (coverInput) {
    let url;
    const preview = document.querySelector('[data-cover-preview]');
    const remove = document.querySelector('[data-cover-remove]');
    const clear = () => {
        if (url) URL.revokeObjectURL(url);
        preview.hidden = true;
        preview.removeAttribute('src');
        remove.hidden = true;
    };
    coverInput.addEventListener('change', () => {
        clear();
        const file = coverInput.files[0];
        coverInput.setCustomValidity('');
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            coverInput.setCustomValidity('Gunakan JPG, PNG, atau WebP maksimal 5 MB.');
            coverInput.reportValidity();
            return;
        }
        url = URL.createObjectURL(file);
        preview.src = url;
        preview.hidden = false;
        remove.hidden = false;
    });
    remove.addEventListener('click', () => { clear(); coverInput.value = ''; coverInput.setCustomValidity(''); });
}
const parseCategory = (value) => {
    const normalized = (value || '').toLowerCase().trim();
    if (!normalized) return '';
    if (normalized === 'materi') return 'materi';
    if (normalized === 'pengumuman') return 'pengumuman';
    if (normalized.includes('coding') || normalized.includes('pemrograman')) return 'coding';
    if (normalized.includes('uts')) return 'uts';
    if (normalized.includes('uas')) return 'uas';
    if (normalized.includes('kuis') || normalized.includes('quiz') || normalized.includes('ujian')) return 'kuis';
    if (normalized === 'lainnya') {
        const customValue = (document.querySelector('[data-custom-type]')?.value || '').toLowerCase().trim();
        if (customValue.includes('uas')) return 'uas';
        if (customValue.includes('uts')) return 'uts';
        return 'uts';
    }
    if (normalized === 'tugas') return 'tugas';
    return '';
};

const contentType = document.querySelector('[data-content-type]');
if (contentType) {
    const questionType = document.querySelector('[data-question-type]');
    const customTypeContainer = document.querySelector('[data-custom-type-container]');
    const customTypeInput = document.querySelector('[data-custom-type]');
    const assignmentFields = document.querySelector('[data-assignment-fields]');
    const materialModeSettings = document.querySelector('[data-material-mode-settings]');
    const pinVideoOption = document.querySelector('[data-pin-video-option]');
    const questionBuilder = document.querySelector('[data-question-builder]');
    const quizDurationSettings = document.querySelector('[data-quiz-duration-settings]');
    const assessmentTitleLabel = document.querySelector('[data-assessment-title-label]');
    const moduleInput = document.querySelector('#module');
    const titleInput = document.querySelector('#title');
    const bodyInput = document.querySelector('#body');
    const taskModes = document.querySelectorAll('[data-task-mode]');
    const materialModes = document.querySelectorAll('[data-material-mode]');
    const codingStepBuilder = document.querySelector('[data-coding-step-builder]');
    const manualCpmkSettings = document.querySelector('[data-manual-cpmk-settings]');
    let initialized = false;

    const setSectionVisibility = (element, visible) => {
        if (!element) return;
        if (visible) {
            element.hidden = false;
            if (initialized) {
                element.style.opacity = '0';
                element.style.transform = 'translateY(6px)';
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }));
            } else {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        } else if (initialized && !element.hidden) {
            element.style.opacity = '0';
            element.style.transform = 'translateY(6px)';
            setTimeout(() => {
                if (element.style.opacity === '0') element.hidden = true;
            }, 200);
        } else {
            element.hidden = true;
            element.style.opacity = '0';
            element.style.transform = 'translateY(6px)';
        }
    };

    const sync = () => {
        const isCustom = contentType.value === 'lainnya';
        if (customTypeContainer) customTypeContainer.hidden = !isCustom;
        if (customTypeInput) {
            customTypeInput.disabled = !isCustom;
            const customIsValid = ['UTS', 'UAS'].includes(customTypeInput.value.trim().toUpperCase());
            customTypeInput.setCustomValidity(isCustom && !customIsValid ? 'Hanya dapat diisi "UTS" atau "UAS"' : '');
        }

        const category = parseCategory(contentType.value);
        const selectedText = contentType.options[contentType.selectedIndex]?.text || '';

        if (assessmentTitleLabel && selectedText) assessmentTitleLabel.textContent = `Susun Soal — ${selectedText}`;

        const showAssignment = ['tugas', 'coding'].includes(category);
        const showMaterialMode = category === 'materi';
        const showQuizOrExam = ['kuis', 'uts', 'uas'].includes(category);
        setSectionVisibility(assignmentFields, showAssignment);
        setSectionVisibility(materialModeSettings, showMaterialMode);
        setSectionVisibility(pinVideoOption, showMaterialMode);
        if (pinVideoOption) {
            const pinInput = pinVideoOption.querySelector('input');
            if (pinInput) pinInput.disabled = !showMaterialMode;
        }
        const isQuestionStep = document.querySelector('[data-content-form]')?.dataset.step === 'questions';
        setSectionVisibility(questionBuilder, showQuizOrExam && isQuestionStep);
        setSectionVisibility(quizDurationSettings, showQuizOrExam);

        const selectedTaskMode = document.querySelector('[data-task-mode]:checked')?.value || 'regular';
        const selectedMaterialMode = document.querySelector('[data-material-mode]:checked')?.value || 'regular';
        const isCoding = (showAssignment && (category === 'coding' || selectedTaskMode === 'coding'))
            || (showMaterialMode && selectedMaterialMode === 'coding');
        if (questionType) questionType.value = isCoding ? 'coding' : 'uraian';
        setSectionVisibility(codingStepBuilder, isCoding);
        codingStepBuilder?.querySelectorAll('input,select,textarea').forEach(field => {
            field.disabled = !isCoding;
        });
        const isRegularTask = showAssignment && !isCoding;
        setSectionVisibility(manualCpmkSettings, isRegularTask);
        manualCpmkSettings?.querySelectorAll('input').forEach(field => {
            field.disabled = !isRegularTask;
        });
    };

    let previousType = contentType.value;
    const resetFormContent = () => {
        if (customTypeInput) customTypeInput.value = '';
        document.querySelector('[data-image-remove]')?.click();
        const altInput = document.querySelector('#image_alt');
        if (altInput) altInput.value = '';
        const attachments = document.querySelector('[data-file-input]');
        if (attachments) {
            attachments.value = '';
            attachments.parentElement?.querySelector('[data-file-list]')?.replaceChildren();
        }
        const linkInput = document.querySelector('#link');
        if (linkInput) linkInput.value = '';
        const regularTask = document.querySelector('[data-task-mode][value="regular"]');
        if (regularTask) regularTask.checked = true;
        const regularMaterial = document.querySelector('[data-material-mode][value="regular"]');
        if (regularMaterial) regularMaterial.checked = true;
        const points = document.querySelector('#points');
        if (points) points.value = '100';
    };

    contentType.addEventListener('change', () => {
        if (contentType.value !== previousType) {
            resetFormContent();
            previousType = contentType.value;
        }
        sync();
    });
    contentType.addEventListener('input', sync);
    customTypeInput?.addEventListener('input', sync);
    moduleInput?.addEventListener('input', () => {
        if (titleInput) titleInput.value = moduleInput.value;
        sync();
    });
    titleInput?.addEventListener('input', sync);
    bodyInput?.addEventListener('input', sync);
    questionType?.addEventListener('change', sync);
    taskModes.forEach(mode => mode.addEventListener('change', sync));
    materialModes.forEach(mode => mode.addEventListener('change', sync));
    sync();
    initialized = true;
}

const contentForm = document.querySelector('[data-content-form]');
if (contentForm) {
    const typeInput = contentForm.querySelector('[data-content-type]');
    const setup = contentForm.querySelector('[data-content-setup]');
    const builder = contentForm.querySelector('[data-question-builder]');
    const durationSettings = contentForm.querySelector('[data-quiz-duration-settings]');
    const legacySettings = contentForm.querySelector('[data-legacy-question-settings]');
    const progress = contentForm.querySelector('[data-content-progress]');
    const nextButton = contentForm.querySelector('[data-next-to-questions]');
    const backButton = contentForm.querySelector('[data-back-to-setup]');
    const submitButton = contentForm.querySelector('[data-submit-content]');
    const formErrorEl = contentForm.querySelector('[data-form-error]');
    const isQuiz = () => ['kuis', 'uts', 'uas'].includes(parseCategory(typeInput?.value));

    // Submit button is never permanently disabled
    if (submitButton) {
        submitButton.disabled = false;
        submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
    }

    const showFormError = (msg) => {
        if (!formErrorEl) return;
        if (msg) {
            formErrorEl.textContent = msg;
            formErrorEl.classList.remove('hidden');
        } else {
            formErrorEl.textContent = '';
            formErrorEl.classList.add('hidden');
        }
    };

    const clearHighlights = () => {
        contentForm.querySelectorAll('.ring-2').forEach(el => {
            el.classList.remove('ring-2', 'ring-danger/40', 'border-danger');
        });
    };

    const paintProgress = (step) => {
        contentForm.querySelectorAll('[data-step-indicator]').forEach(indicator => {
            const active = indicator.dataset.stepIndicator === step;
            indicator.classList.toggle('text-brand', active);
            indicator.classList.toggle('text-muted', !active);
            const badge = indicator.querySelector('span');
            badge?.classList.toggle('bg-brand', active);
            badge?.classList.toggle('text-white', active);
            badge?.classList.toggle('border', !active);
            badge?.classList.toggle('border-line', !active);
            badge?.classList.toggle('bg-white', !active);
        });
    };

    const showStep = (step) => {
        const quizActive = isQuiz();
        if (!quizActive) step = 'setup';
        contentForm.dataset.step = step;
        const questionsStep = step === 'questions';

        if (setup) setup.hidden = questionsStep;
        if (builder) {
            builder.hidden = !questionsStep || !quizActive;
            if (questionsStep && quizActive) {
                builder.style.opacity = '1';
                builder.style.transform = 'translateY(0)';
            }
        }
        if (durationSettings) durationSettings.hidden = !quizActive;
        if (legacySettings) legacySettings.hidden = questionsStep || !['tugas', 'coding'].includes(parseCategory(typeInput?.value));
        if (progress) progress.hidden = !quizActive;
        if (nextButton) nextButton.hidden = questionsStep || !quizActive;
        if (backButton) backButton.hidden = !questionsStep || !quizActive;
        if (submitButton) submitButton.hidden = quizActive && !questionsStep;

        paintProgress(step);

        if (questionsStep && builder) {
            const firstInput = builder.querySelector('textarea[data-q-field="prompt"], input, select');
            firstInput?.focus();
        }
    };

    typeInput?.addEventListener('change', () => {
        showStep('setup');
        clearHighlights();
        showFormError('');
    });
    typeInput?.addEventListener('input', () => {
        showStep('setup');
    });

    nextButton?.addEventListener('click', () => {
        clearHighlights();
        showFormError('');

        const category = parseCategory(typeInput?.value);
        const moduleInput = contentForm.querySelector('#module');
        const bodyInput = contentForm.querySelector('#body');

        // 1. Jenis Konten
        if (!typeInput?.value) {
            typeInput?.classList.add('ring-2', 'ring-danger/40', 'border-danger');
            typeInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            typeInput?.focus();
            showFormError('Pilih jenis konten terlebih dahulu.');
            return;
        }

        if (typeInput.value === 'lainnya') {
            const customTypeInput = contentForm.querySelector('#custom_type');
            const customVal = customTypeInput?.value.trim().toUpperCase() || '';
            if (!['UTS', 'UAS'].includes(customVal)) {
                customTypeInput?.classList.add('ring-2', 'ring-danger/40', 'border-danger');
                customTypeInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                customTypeInput?.focus();
                showFormError('Nama jenis konten kustom harus diisi "UTS" atau "UAS".');
                return;
            }
        }

        // 2. Modul / Topik
        if (!moduleInput?.value.trim()) {
            moduleInput?.classList.add('ring-2', 'ring-danger/40', 'border-danger');
            moduleInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            moduleInput?.focus();
            showFormError('Isi nama modul / topik pembelajaran.');
            return;
        }

        // 3. Materi / Instruksi
        if (!bodyInput?.value.trim()) {
            bodyInput?.classList.add('ring-2', 'ring-danger/40', 'border-danger');
            bodyInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            bodyInput?.focus();
            showFormError('Isi materi, instruksi, atau stimulus soal.');
            return;
        }

        showStep('questions');
        contentForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    backButton?.addEventListener('click', () => {
        showStep('setup');
        contentForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    contentForm.querySelectorAll('[data-content-addon]').forEach(button => {
        button.addEventListener('click', () => {
            const panel = contentForm.querySelector(`[data-content-addon-panel="${button.dataset.contentAddon}"]`);
            if (!panel) return;
            panel.hidden = !panel.hidden;
            button.setAttribute('aria-expanded', String(!panel.hidden));
            button.closest('[data-content-addon-menu]')?.removeAttribute('open');
            if (!panel.hidden) {
                const input = panel.querySelector('input:not([type="hidden"])');
                if (['image', 'files'].includes(button.dataset.contentAddon)) input?.click();
                else input?.focus();
            }
        });
    });

    const durationToggle = contentForm.querySelector('[data-duration-toggle]');
    const durationMode = contentForm.querySelector('#duration_mode');
    const durationOptions = contentForm.querySelector('[data-duration-options]');
    const durationInput = contentForm.querySelector('#duration_minutes');
    const syncDuration = () => {
        const enabled = !!durationToggle?.checked;
        if (durationMode) durationMode.value = enabled ? 'enabled' : 'disabled';
        if (durationOptions) durationOptions.hidden = !enabled;
        if (durationInput) durationInput.disabled = !enabled;
    };
    durationToggle?.addEventListener('change', syncDuration);
    contentForm.querySelectorAll('[data-duration-preset]').forEach(button => {
        button.addEventListener('click', () => {
            if (durationInput) durationInput.value = button.dataset.durationPreset;
        });
    });
    syncDuration();

    const dueToggle = contentForm.querySelector('[data-due-toggle]');
    const dueOptions = contentForm.querySelector('[data-due-options]');
    const dueInput = contentForm.querySelector('#task_due');
    const syncDue = () => {
        const enabled = !!dueToggle?.checked;
        if (dueOptions) dueOptions.hidden = !enabled;
        if (dueInput) {
            dueInput.disabled = !enabled;
            if (!enabled) dueInput.value = '';
        }
    };
    dueToggle?.addEventListener('change', syncDue);
    syncDue();

    const quizDueToggle = contentForm.querySelector('[data-quiz-due-toggle]');
    const quizDueOptions = contentForm.querySelector('[data-quiz-due-options]');
    const quizDueInput = contentForm.querySelector('#quiz_due');
    const syncQuizDue = () => {
        const enabled = !!quizDueToggle?.checked;
        if (quizDueOptions) quizDueOptions.hidden = !enabled;
        if (quizDueInput) {
            quizDueInput.disabled = !enabled;
            if (!enabled) quizDueInput.value = '';
        }
    };
    quizDueToggle?.addEventListener('change', syncQuizDue);
    syncQuizDue();

    contentForm.addEventListener('submit', (e) => {
        clearHighlights();
        showFormError('');

        const category = parseCategory(typeInput?.value);
        const moduleInput = contentForm.querySelector('#module');
        const bodyInput = contentForm.querySelector('#body');

        // 1. Jenis Konten
        if (!typeInput?.value) {
            showStep('setup');
            typeInput?.classList.add('ring-2', 'ring-danger/40', 'border-danger');
            typeInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            typeInput?.focus();
            showFormError('Pilih jenis konten terlebih dahulu.');
            e.preventDefault();
            return false;
        }

        if (typeInput.value === 'lainnya') {
            const customTypeInput = contentForm.querySelector('#custom_type');
            const customVal = customTypeInput?.value.trim().toUpperCase() || '';
            if (!['UTS', 'UAS'].includes(customVal)) {
                showStep('setup');
                customTypeInput?.classList.add('ring-2', 'ring-danger/40', 'border-danger');
                customTypeInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                customTypeInput?.focus();
                showFormError('Nama jenis konten kustom harus diisi "UTS" atau "UAS".');
                e.preventDefault();
                return false;
            }
        }

        // 2. Modul / Topik
        if (!moduleInput?.value.trim()) {
            showStep('setup');
            moduleInput?.classList.add('ring-2', 'ring-danger/40', 'border-danger');
            moduleInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            moduleInput?.focus();
            showFormError('Isi nama modul / topik pembelajaran.');
            e.preventDefault();
            return false;
        }

        // 3. Materi / Instruksi
        if (!bodyInput?.value.trim()) {
            showStep('setup');
            bodyInput?.classList.add('ring-2', 'ring-danger/40', 'border-danger');
            bodyInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            bodyInput?.focus();
            showFormError('Isi materi, instruksi, atau stimulus soal.');
            e.preventDefault();
            return false;
        }

        // 4. Khusus Kuis / UTS / UAS
        if (['kuis', 'uts', 'uas'].includes(category)) {
            const questionRows = [...contentForm.querySelectorAll('[data-question-row]')];
            if (questionRows.length === 0) {
                showStep('questions');
                builder?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                showFormError('Tambahkan minimal 1 soal.');
                e.preventDefault();
                return false;
            }

            for (let idx = 0; idx < questionRows.length; idx++) {
                const row = questionRows[idx];
                const promptInput = row.querySelector('[data-q-field="prompt"]');
                const cpmkSelect = row.querySelector('[data-q-field="cpmk"]');
                const pointsInput = row.querySelector('input[data-q-field="points"]');
                const qType = row.querySelector('[data-q-field="type"]')?.value || 'uraian';
                const pts = pointsInput ? (parseInt(pointsInput.value) || 0) : 0;

                if (!promptInput?.value.trim()) {
                    showStep('questions');
                    const tabs = builder?.querySelectorAll('[data-question-tabs] button');
                    tabs?.[idx]?.click();
                    promptInput?.classList.add('ring-2', 'ring-danger/40', 'border-danger');
                    promptInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    promptInput?.focus();
                    showFormError(`Soal ${idx + 1}: Tuliskan pertanyaan atau stimulus soal.`);
                    e.preventDefault();
                    return false;
                }

                if (!cpmkSelect?.value) {
                    showStep('questions');
                    const tabs = builder?.querySelectorAll('[data-question-tabs] button');
                    tabs?.[idx]?.click();
                    cpmkSelect?.classList.add('ring-2', 'ring-danger/40', 'border-danger');
                    cpmkSelect?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    cpmkSelect?.focus();
                    showFormError(`Soal ${idx + 1}: Pilih target CPMK.`);
                    e.preventDefault();
                    return false;
                }

                if (pts <= 0) {
                    showStep('questions');
                    const tabs = builder?.querySelectorAll('[data-question-tabs] button');
                    tabs?.[idx]?.click();
                    pointsInput?.classList.add('ring-2', 'ring-danger/40', 'border-danger');
                    pointsInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    pointsInput?.focus();
                    showFormError(`Soal ${idx + 1}: Skor poin harus lebih dari 0.`);
                    e.preventDefault();
                    return false;
                }

                if (['pilihan', 'kompleks'].includes(qType)) {
                    const choices = [...row.querySelectorAll('[data-choice-item-input]')].map(i => i.value.trim()).filter(Boolean);
                    if (choices.length < 2) {
                        showStep('questions');
                        const tabs = builder?.querySelectorAll('[data-question-tabs] button');
                        tabs?.[idx]?.click();
                        const firstChoice = row.querySelector('[data-choice-item-input]');
                        firstChoice?.classList.add('ring-2', 'ring-danger/40', 'border-danger');
                        firstChoice?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstChoice?.focus();
                        showFormError(`Soal ${idx + 1}: Masukkan minimal 2 pilihan jawaban.`);
                        e.preventDefault();
                        return false;
                    }

                    const checkedKeys = [...row.querySelectorAll('[data-choice-correct-input]:checked')];
                    if (checkedKeys.length === 0) {
                        showStep('questions');
                        const tabs = builder?.querySelectorAll('[data-question-tabs] button');
                        tabs?.[idx]?.click();
                        const firstOption = row.querySelector('[data-choice-correct-input]');
                        firstOption?.focus();
                        showFormError(`Soal ${idx + 1}: Tentukan kunci jawaban yang benar dengan memilih tombol ${qType === 'kompleks' ? 'checklist' : 'radio'}.`);
                        e.preventDefault();
                        return false;
                    }
                }

                if (qType === 'mencocokkan') {
                    const pairs = [...row.querySelectorAll('[data-pair-item]')];
                    const incomplete = pairs.some(p => !p.querySelector('[data-pair-left]')?.value.trim() || !p.querySelector('[data-pair-right]')?.value.trim());
                    if (pairs.length === 0 || incomplete) {
                        showStep('questions');
                        const tabs = builder?.querySelectorAll('[data-question-tabs] button');
                        tabs?.[idx]?.click();
                        row.querySelector('[data-pair-left], [data-pair-right]')?.focus();
                        showFormError(`Soal ${idx + 1}: Lengkapi pasangan premis dan jawaban mencocokkan.`);
                        e.preventDefault();
                        return false;
                    }
                }
            }

            const totalPoints = questionRows.reduce((sum, r) => sum + (parseInt(r.querySelector('input[data-q-field="points"]')?.value) || 0), 0);
            if (totalPoints !== 100) {
                showStep('questions');
                builder?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const badge = builder?.querySelector('[data-total-points-badge]');
                badge?.classList.add('ring-2', 'ring-danger/50');
                setTimeout(() => badge?.classList.remove('ring-2', 'ring-danger/50'), 3000);
                showFormError(`Total skor adalah ${totalPoints} / 100 (${totalPoints < 100 ? 'kurang ' + (100 - totalPoints) : 'lebih +' + (totalPoints - 100)} poin). Klik "Bagi Rata (100 / n)" di toolbar atau sesuaikan skor agar pas 100.`);
                e.preventDefault();
                return false;
            }
        }

        // 5. Khusus Tugas / Coding / CPMK Manual
        const codingRows = [...contentForm.querySelectorAll('[data-coding-step-row]')].filter(row => !row.querySelector('[data-step-field]')?.disabled);
        for (let idx = 0; idx < codingRows.length; idx++) {
            const row = codingRows[idx];
            const titleInp = row.querySelector('[data-step-field="title"]');
            const bodyInp = row.querySelector('[data-step-field="body"]');
            if (!titleInp?.value.trim()) {
                titleInp?.classList.add('ring-2', 'ring-danger/40', 'border-danger');
                titleInp?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                titleInp?.focus();
                showFormError(`Tahap ${idx + 1}: Judul tahap belum diisi.`);
                e.preventDefault();
                return false;
            }
            if (!bodyInp?.value.trim()) {
                bodyInp?.classList.add('ring-2', 'ring-danger/40', 'border-danger');
                bodyInp?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                bodyInp?.focus();
                showFormError(`Tahap ${idx + 1}: Materi / instruksi tahap belum diisi.`);
                e.preventDefault();
                return false;
            }
        }

        const manualWeights = [...contentForm.querySelectorAll('[data-manual-cpmk-weight]')].filter(input => !input.disabled);
        if (manualWeights.length > 0) {
            const sum = manualWeights.reduce((s, input) => s + Number(input.value || 0), 0);
            if (Math.abs(sum - 100) >= 0.01) {
                showFormError(`Total bobot CPMK tugas saat ini ${sum}%. Pastikan tepat 100%.`);
                contentForm.querySelector('[data-manual-weight-total]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                e.preventDefault();
                return false;
            }
        }

        const title = contentForm.querySelector('#title')?.value.trim();
        const imageAlt = contentForm.querySelector('#image_alt');
        if (imageAlt && !imageAlt.value.trim()) imageAlt.value = title ? `Gambar pendukung untuk ${title}` : 'Gambar pendukung materi';
        contentForm.querySelectorAll('[data-question-row]').forEach(row => {
            const alt = row.querySelector('[data-q-field="alt"]');
            const prompt = row.querySelector('[data-q-field="prompt"]')?.value.trim();
            if (alt && !alt.value.trim()) alt.value = (prompt ? `Gambar pendukung untuk ${prompt}` : 'Gambar pendukung soal').slice(0, 300);
        });
    });

    contentForm.addEventListener('input', (e) => {
        e.target.classList.remove('ring-2', 'ring-danger/40', 'border-danger');
        showFormError('');
    });
    contentForm.addEventListener('change', (e) => {
        e.target.classList.remove('ring-2', 'ring-danger/40', 'border-danger');
        showFormError('');
    });

    const manualWeights = [...contentForm.querySelectorAll('[data-manual-cpmk-weight]')];
    const syncManualWeight = () => {
        const enabled = manualWeights.filter(input => !input.disabled);
        const total = enabled.reduce((sum, input) => sum + Number(input.value || 0), 0);
        const output = contentForm.querySelector('[data-manual-weight-total]');
        if (output) {
            output.textContent = `Total: ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(total)}%`;
            output.classList.toggle('text-danger', enabled.length > 0 && Math.abs(total - 100) >= 0.01);
        }
        enabled.forEach(input => input.setCustomValidity(''));
        if (enabled.length && Math.abs(total - 100) >= 0.01) {
            enabled[0].setCustomValidity('Total persentase CPMK harus tepat 100%.');
        }
    };
    manualWeights.forEach(input => input.addEventListener('input', syncManualWeight));
    contentForm.addEventListener('change', () => requestAnimationFrame(syncManualWeight));
    syncManualWeight();

    const initialStep = contentForm.dataset.step || 'setup';
    showStep(initialStep);
}

// Tahapan tutorial/tugas pemrograman menggunakan pola satu tahap per layar.
const codingStepBuilder = document.querySelector('[data-coding-step-builder]');
if (codingStepBuilder) {
    const rows = codingStepBuilder.querySelector('[data-coding-step-rows]');
    const template = codingStepBuilder.querySelector('[data-coding-step-template]');
    const tabs = codingStepBuilder.querySelector('[data-coding-step-tabs]');
    let activeIndex = 0;

    const update = () => {
        const total = rows.children.length;
        activeIndex = Math.max(0, Math.min(activeIndex, total - 1));
        [...rows.children].forEach((row, index) => {
            row.hidden = index !== activeIndex;
            row.querySelector('[data-coding-step-title]').textContent = `Tahap ${index + 1}`;
            row.querySelectorAll('[data-step-field]').forEach(input => {
                input.name = `coding_steps[${index}][${input.dataset.stepField}]`;
            });
        });
        tabs.replaceChildren();
        for (let index = 0; index < total; index++) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = `Tahap ${index + 1}`;
            button.className = index === activeIndex
                ? 'rounded-lg bg-brand px-3 py-1.5 text-xs font-bold text-white'
                : 'rounded-lg border border-line/60 bg-white px-3 py-1.5 text-xs font-semibold text-ink';
            button.addEventListener('click', () => { activeIndex = index; update(); });
            tabs.appendChild(button);
        }
        const previous = codingStepBuilder.querySelector('[data-prev-coding-step]');
        const next = codingStepBuilder.querySelector('[data-next-coding-step]');
        if (previous) previous.disabled = activeIndex === 0;
        if (next) next.disabled = activeIndex >= total - 1;
    };
    const add = (data = {}) => {
        if (rows.children.length >= 20) return;
        const row = template.content.firstElementChild.cloneNode(true);
        row.querySelectorAll('[data-step-field]').forEach(input => {
            if (input.type !== 'file' && data[input.dataset.stepField] !== undefined) input.value = data[input.dataset.stepField];
            input.disabled = codingStepBuilder.hidden;
        });
        rows.appendChild(row);
        activeIndex = rows.children.length - 1;
        update();
    };
    codingStepBuilder.querySelector('[data-add-coding-step]')?.addEventListener('click', () => add());
    codingStepBuilder.querySelector('[data-prev-coding-step]')?.addEventListener('click', () => { if (activeIndex > 0) activeIndex--; update(); });
    codingStepBuilder.querySelector('[data-next-coding-step]')?.addEventListener('click', () => { if (activeIndex < rows.children.length - 1) activeIndex++; update(); });
    rows.addEventListener('click', event => {
        if (!event.target.closest('[data-remove-coding-step]') || rows.children.length === 1) return;
        event.target.closest('[data-coding-step-row]')?.remove();
        update();
    });
    let oldSteps = [];
    try { oldSteps = JSON.parse(codingStepBuilder.querySelector('[data-old-coding-steps]')?.textContent || '[]'); } catch (_) {}
    (oldSteps.length ? oldSteps : [{}]).forEach(step => add(step));
}

document.querySelectorAll('[data-code-steps]').forEach(stepper => {
    const steps = [...stepper.querySelectorAll('[data-code-step]')];
    const tabs = stepper.querySelector('[data-code-step-tabs]');
    let active = 0;
    const render = () => {
        steps.forEach((step, index) => { step.hidden = index !== active; });
        tabs?.replaceChildren(...steps.map((_, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = `${index + 1}`;
            button.className = index === active
                ? 'h-7 min-w-7 rounded-md bg-brand px-2 text-[11px] font-bold text-white'
                : 'h-7 min-w-7 rounded-md border border-line/60 bg-white px-2 text-[11px] font-semibold text-ink';
            button.addEventListener('click', () => { active = index; render(); });
            return button;
        }));
        const previous = stepper.querySelector('[data-code-step-prev]');
        const next = stepper.querySelector('[data-code-step-next]');
        if (previous) previous.disabled = active === 0;
        if (next) next.disabled = active === steps.length - 1;
    };
    stepper.querySelector('[data-code-step-prev]')?.addEventListener('click', () => { if (active > 0) active--; render(); });
    stepper.querySelector('[data-code-step-next]')?.addEventListener('click', () => { if (active < steps.length - 1) active++; render(); });
    render();
});

const academicChart = document.querySelector('[data-academic-chart]');
academicChart?.querySelectorAll('[data-chart-mode]').forEach(button => {
    button.addEventListener('click', () => {
        const mode = button.dataset.chartMode;
        academicChart.querySelectorAll('[data-chart-mode]').forEach(control => {
            const active = control === button;
            control.setAttribute('aria-pressed', String(active));
            control.classList.toggle('bg-white', active);
            control.classList.toggle('shadow-sm', active);
            control.classList.toggle('text-brand', active);
            control.classList.toggle('text-muted', !active);
        });
        const points = [...academicChart.querySelectorAll('[data-chart-point]')].map(point => {
            const x = Number(point.dataset.x);
            const value = point.dataset[mode];
            const y = 197 - Number(value.replace(',', '.')) * 43;
            point.querySelector('circle')?.setAttribute('cy', y);
            const label = point.querySelector('[data-point-value]');
            label.setAttribute('y', y - 8);
            label.textContent = value;
            return `${x} ${y}`;
        });
        const path = `M${points.join('L')}`;
        academicChart.querySelector('[data-chart-line]').setAttribute('d', path);
        academicChart.querySelector('[data-chart-area]').setAttribute('d', `${path}V197H60Z`);
        academicChart.querySelector('#chart-title').textContent = mode === 'ips' ? 'IP semester' : 'IPK kumulatif';
        academicChart.querySelector('#chart-description').textContent = [...academicChart.querySelectorAll('[data-chart-point]')].map((p,i) => `Semester ${i+1}: ${p.dataset[mode]}`).join('; ');
    });
});

const academicType = document.querySelector('[data-academic-type]');
if (academicType) {
    const syncAcademic = () => {
        const type = academicType.value;
        document.querySelector('[data-academic-parent]').hidden = !['prodi','kelas'].includes(type);
        document.querySelector('[data-academic-course]').hidden = type !== 'kelas';
        document.querySelector('[data-academic-students]').hidden = type !== 'kelas';
        const parent = document.querySelector('#parent');
        [...parent.options].forEach(option => { option.hidden = !!option.value && option.dataset.parentType !== (type === 'kelas' ? 'prodi' : 'fakultas'); });
        if (parent.selectedOptions[0]?.hidden) parent.value = '';
    };
    academicType.addEventListener('change',syncAcademic);
    syncAcademic();
}

document.querySelectorAll('[data-attach]').forEach(button => {
    button.addEventListener('click', () => {
        const form = button.closest('form');
        const target = button.dataset.attach;
        const panel = form.querySelector(`[data-attach-panel="${target}"]`);
        if (!panel) return;
        panel.hidden = false;
        form.querySelector('[data-attachment-menu]').open = false;
        const input = panel.querySelector('input,textarea');
        if (target === 'files') {
            input.accept = button.dataset.accept;
            input.click();
        } else input.focus();
    });
});
document.addEventListener('click', event => {
    document.querySelectorAll('[data-attachment-menu][open]').forEach(menu => { if (!menu.contains(event.target)) menu.open = false; });
});
document.addEventListener('keydown', event => {
    if (event.key === 'Escape') document.querySelectorAll('[data-attachment-menu]').forEach(menu => { menu.open = false; });
});
const questionImage = document.querySelector('[data-image-input]');
if (questionImage) {
    let url;
    const preview = document.querySelector('[data-image-preview]');
    const remove = document.querySelector('[data-image-remove]');
    const alt = document.querySelector('#image_alt');
    const clear = () => { if (url) URL.revokeObjectURL(url); preview.hidden = true; remove.hidden = true; questionImage.setCustomValidity(''); };
    questionImage.addEventListener('change', () => {
        clear();
        const file = questionImage.files[0];
        if (!file) return;
        if (!['image/jpeg','image/png','image/webp'].includes(file.type) || file.size > 5*1024*1024) {
            questionImage.setCustomValidity('Gambar harus JPG, PNG, atau WebP maksimal 5 MB.'); questionImage.reportValidity(); return;
        }
        url = URL.createObjectURL(file); preview.src = url; preview.hidden = false; remove.hidden = false;
    });
    remove.addEventListener('click', () => { clear(); questionImage.value = ''; });
}
const optionsInput = document.querySelector('#options');
if (optionsInput) {
    const container = document.querySelector('[data-option-images]');
    const syncOptions = () => {
        const options = optionsInput.value.split('\n').map(v=>v.trim()).filter(Boolean);
        while (container.children.length > options.length) container.lastElementChild.remove();
        options.forEach((value,index) => {
            let row = container.children[index];
            if (!row) {
                row = document.createElement('div'); row.className = 'rounded-lg bg-canvas p-3';
                const label = document.createElement('label');label.className = 'form-label';label.htmlFor = `option-image-${index}`;
                const input = document.createElement('input');input.type='file';input.accept='image/jpeg,image/png,image/webp';input.name=`option_images[${index}]`;input.id=`option-image-${index}`;input.className='field text-xs';
                const preview = document.createElement('img');preview.hidden=true;preview.className='mt-3 max-h-32 rounded object-contain';
                const remove = document.createElement('button');remove.type='button';remove.textContent='Hapus gambar pilihan';remove.className='quiet-link mt-2';remove.hidden=true;
                let url;
                input.addEventListener('change',()=>{if(url)URL.revokeObjectURL(url);const file=input.files[0];preview.hidden=!file;remove.hidden=!file;input.setCustomValidity(file && (file.size>2*1024*1024 || !['image/jpeg','image/png','image/webp'].includes(file.type)) ? 'Gunakan gambar maksimal 2 MB.' : '');if(file){url=URL.createObjectURL(file);preview.src=url;preview.alt=value;}});
                remove.addEventListener('click',()=>{if(url)URL.revokeObjectURL(url);input.value='';input.setCustomValidity('');preview.hidden=true;remove.hidden=true;});
                row.append(label,input,preview,remove);container.append(row);
            }
            row.querySelector('label').textContent = `Gambar pilihan ${index+1} · ${value}`;
        });
    };
    optionsInput.addEventListener('change',syncOptions);syncOptions();
}

// Repeatable academic settings keep submitted indexes contiguous.
document.querySelectorAll('[data-repeat-group]').forEach(group=>{
    const rows=group.querySelector('[data-rows]');
    const renumber=()=>{[...rows.children].forEach((row,index)=>row.querySelectorAll('[name]').forEach(input=>input.name=input.name.replace(/\[\d+\]/,`[${index}]`)));const total=group.querySelector('[data-weight-total]');if(total)total.textContent=`Total bobot: ${[...group.querySelectorAll('[data-weight]')].reduce((sum,input)=>sum+Number(input.value||0),0)}%`;};
    group.querySelector('[data-add-row]').addEventListener('click',()=>{const clone=rows.firstElementChild.cloneNode(true);clone.querySelectorAll('input').forEach(input=>input.value=input.type==='number'?'0':'');rows.append(clone);renumber();});
    rows.addEventListener('click',event=>{if(event.target.closest('[data-remove-row]') && rows.children.length>1){event.target.closest('[data-row]').remove();renumber();}});rows.addEventListener('input',renumber);
});
const builder = document.querySelector('[data-question-builder]');
if (builder) {
    const rows = builder.querySelector('[data-question-rows]');
    const template = builder.querySelector('template');
    const type = document.querySelector('[data-content-type]');
    const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    const syncChoices = (row) => {
        const textarea = row.querySelector('textarea[data-q-field="options"]');
        const correctInput = row.querySelector('input[data-q-field="correct_answer"]');
        const inputs = row.querySelectorAll('[data-choice-item-input]');
        const values = [...inputs].map(i => i.value.trim()).filter(Boolean);
        if (textarea) textarea.value = values.join('\n');

        if (correctInput) {
            const checkedInputs = [...row.querySelectorAll('[data-choice-correct-input]:checked')];
            const checkedLetters = checkedInputs.map(inp => inp.value);
            correctInput.value = checkedLetters.join(', ');
        }
    };

    const renderChoices = (row, choices = [], correctVal = null) => {
        const list = row.querySelector('[data-choice-list]');
        if (!list) return;
        list.innerHTML = '';
        const items = choices.length ? choices : ['', '', '', ''];
        const qType = row.querySelector('select[data-q-field="type"]')?.value || 'pilihan';
        const isComplex = qType === 'kompleks';
        const inputType = isComplex ? 'checkbox' : 'radio';

        const rowIndex = [...rows.children].indexOf(row);
        const radioName = `correct_choice_${rowIndex >= 0 ? rowIndex : Math.random().toString(36).substring(2, 7)}`;

        const correctInput = row.querySelector('input[data-q-field="correct_answer"]');
        const currentCorrect = (correctVal !== null && correctVal !== undefined)
            ? String(correctVal)
            : (correctInput?.value || (isComplex ? 'A' : 'A'));
        const correctArray = currentCorrect.split(',').map(s => s.trim().toUpperCase()).filter(Boolean);

        const hintEl = row.querySelector('[data-q-options-hint]');
        if (hintEl) {
            hintEl.textContent = isComplex
                ? 'Centang checklist pada opsi yang merupakan jawaban benar (bisa lebih dari satu).'
                : 'Pilih tombol radio pada opsi yang merupakan jawaban benar (satu jawaban).';
        }

        items.forEach((val, idx) => {
            const letter = letters[idx] || String(idx + 1);
            const isChecked = correctArray.includes(letter.toUpperCase()) || (!isComplex && idx === 0 && correctArray.length === 0);
            const item = document.createElement('div');
            item.className = 'flex items-center gap-2 p-1 rounded-lg hover:bg-slate-50/70 transition';
            item.innerHTML = `
                <label class="flex items-center gap-1.5 cursor-pointer px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-xs shrink-0 select-none border border-line/60" title="${isComplex ? 'Centang jika opsi ini adalah jawaban benar' : 'Pilih opsi ini sebagai kunci jawaban benar'}">
                    <input type="${inputType}" ${!isComplex ? `name="${radioName}"` : ''} value="${letter}" data-choice-correct-input class="text-brand h-3.5 w-3.5 ${isComplex ? 'rounded' : ''}" ${isChecked ? 'checked' : ''}>
                    <span class="text-[11px] font-semibold text-slate-700">Kunci</span>
                </label>
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-canvas text-xs font-bold text-ink shrink-0 border border-line/50" data-choice-letter>${letter}</span>
                <input type="text" class="field text-xs py-1.5 flex-1" value="${val.replace(/"/g, '&quot;')}" placeholder="Pilihan ${letter}..." data-choice-item-input>
                <button type="button" class="h-7 w-7 rounded-md text-muted hover:text-danger hover:bg-rose-50 flex items-center justify-center text-sm" data-remove-choice title="Hapus pilihan">×</button>
            `;
            list.appendChild(item);
        });
        syncChoices(row);
    };

    const syncPairs = (row) => {
        const textarea = row.querySelector('textarea[data-q-field="options"]');
        if (!textarea) return;
        const leftInputs = row.querySelectorAll('[data-pair-left]');
        const lines = [];
        leftInputs.forEach(leftInp => {
            const rightInp = leftInp.closest('[data-pair-item]')?.querySelector('[data-pair-right]');
            const left = leftInp.value.trim();
            const right = rightInp ? rightInp.value.trim() : '';
            if (left || right) lines.push(`${left} = ${right}`);
        });
        textarea.value = lines.join('\n');
    };

    const createPairItem = (idx, left = '', right = '', mode = 'text') => {
        const item = document.createElement('div');
        item.setAttribute('data-pair-item', '');
        item.className = 'flex flex-col sm:flex-row items-stretch sm:items-center gap-2 p-2.5 rounded-lg bg-white border border-line/40';

        const isLeftImg = mode === 'image' || mode === 'image_text' || mode === 'image_image';
        const isRightImg = mode === 'text_image' || mode === 'image_image';
        const hasLeftImg = isLeftImg && left && (left.startsWith('http') || left.startsWith('data:image') || left.startsWith('/'));
        const hasRightImg = isRightImg && right && (right.startsWith('http') || right.startsWith('data:image') || right.startsWith('/'));

        const leftHtml = isLeftImg ? `
            <div class="flex-1 flex items-center gap-2 min-w-0">
                <label class="cursor-pointer inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md bg-canvas border border-line/60 text-xs text-ink hover:bg-slate-100 font-semibold shrink-0" title="Pilih berkas gambar kiri">
                    <svg class="h-3.5 w-3.5 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    <span>Pilih Gambar</span>
                    <input type="file" accept="image/*" class="sr-only" data-pair-file="left">
                </label>
                <input type="text" class="field text-xs py-1.5 flex-1 min-w-0" value="${(left || '').replace(/"/g, '&quot;')}" placeholder="URL / Data Gambar Kiri..." data-pair-left>
                <div class="relative shrink-0 ${hasLeftImg ? '' : 'hidden'}" data-pair-preview-box="left">
                    <img src="${left}" class="h-7 w-10 object-contain rounded border border-line/60 bg-slate-50" data-pair-preview="left" alt="Pratinjau Kiri">
                    <button type="button" data-pair-clear-img="left" class="absolute -top-1.5 -right-1.5 h-4 w-4 rounded-full bg-rose-600 text-white text-[10px] flex items-center justify-center font-bold hover:bg-rose-700 shadow cursor-pointer" title="Hapus gambar">×</button>
                </div>
            </div>
        ` : `
            <input type="text" class="field text-xs py-1.5 flex-1" value="${(left || '').replace(/"/g, '&quot;')}" placeholder="Premis / Istilah kiri (teks)" data-pair-left>
        `;

        const rightHtml = isRightImg ? `
            <div class="flex-1 flex items-center gap-2 min-w-0">
                <label class="cursor-pointer inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md bg-canvas border border-line/60 text-xs text-ink hover:bg-slate-100 font-semibold shrink-0" title="Pilih berkas gambar kanan">
                    <svg class="h-3.5 w-3.5 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    <span>Pilih Gambar</span>
                    <input type="file" accept="image/*" class="sr-only" data-pair-file="right">
                </label>
                <input type="text" class="field text-xs py-1.5 flex-1 min-w-0" value="${(right || '').replace(/"/g, '&quot;')}" placeholder="URL / Data Gambar Kanan..." data-pair-right>
                <div class="relative shrink-0 ${hasRightImg ? '' : 'hidden'}" data-pair-preview-box="right">
                    <img src="${right}" class="h-7 w-10 object-contain rounded border border-line/60 bg-slate-50" data-pair-preview="right" alt="Pratinjau Kanan">
                    <button type="button" data-pair-clear-img="right" class="absolute -top-1.5 -right-1.5 h-4 w-4 rounded-full bg-rose-600 text-white text-[10px] flex items-center justify-center font-bold hover:bg-rose-700 shadow cursor-pointer" title="Hapus gambar">×</button>
                </div>
            </div>
        ` : `
            <input type="text" class="field text-xs py-1.5 flex-1" value="${(right || '').replace(/"/g, '&quot;')}" placeholder="${isLeftImg ? 'Nama / Label teks jawaban...' : 'Pasangan / Jawaban kanan (teks)...'}" data-pair-right>
        `;

        item.innerHTML = `
            <span class="text-[11px] font-bold text-muted shrink-0 w-16" data-pair-label>Item ${idx + 1}:</span>
            ${leftHtml}
            <span class="text-xs text-muted font-bold self-center px-1 hidden sm:inline">↔</span>
            ${rightHtml}
            <button type="button" class="h-7 w-7 rounded-md text-muted hover:text-danger hover:bg-rose-50 flex items-center justify-center text-sm shrink-0 self-end sm:self-center font-bold" data-remove-pair title="Hapus pasangan">×</button>
        `;
        return item;
    };

    const renderPairs = (row, pairs = []) => {
        const list = row.querySelector('[data-pair-list]');
        if (!list) return;

        const isImgImg = pairs.some(p => {
            const l = p.left || '';
            const r = p.right || '';
            return (l.startsWith('http') || l.startsWith('data:image') || l.startsWith('/')) &&
                   (r.startsWith('http') || r.startsWith('data:image') || r.startsWith('/'));
        });
        const isImgText = !isImgImg && pairs.some(p => {
            const l = p.left || '';
            return l.startsWith('http') || l.startsWith('data:image') || l.startsWith('/');
        });
        const isTextImg = !isImgImg && !isImgText && pairs.some(p => {
            const r = p.right || '';
            return r.startsWith('http') || r.startsWith('data:image') || r.startsWith('/');
        });

        const modeRadios = row.querySelectorAll('[data-pair-mode]');
        let currentMode = 'text';
        modeRadios.forEach(radio => {
            if (isImgImg && radio.value === 'image_image') radio.checked = true;
            else if (isImgText && (radio.value === 'image_text' || radio.value === 'image')) radio.checked = true;
            else if (isTextImg && radio.value === 'text_image') radio.checked = true;
            if (radio.checked) currentMode = radio.value;
        });

        const hint = row.querySelector('[data-pair-mode-hint]');
        const instruction = row.querySelector('[data-pair-instruction]');
        if (hint) {
            if (currentMode === 'image_image') {
                hint.textContent = 'Format Gambar ↔ Gambar: Unggah gambar di kiri dan gambar pasangan di kanan';
            } else if (currentMode === 'image_text' || currentMode === 'image') {
                hint.textContent = 'Format Gambar ↔ Teks: Unggah gambar di kiri, ketik nama/label di kanan';
            } else if (currentMode === 'text_image') {
                hint.textContent = 'Format Teks ↔ Gambar: Ketik istilah di kiri, unggah gambar di kanan';
            } else {
                hint.textContent = 'Format Teks ↔ Teks: Ketik istilah di kiri dan penjelasan di kanan';
            }
        }
        if (instruction) {
            if (currentMode === 'image_image') {
                instruction.textContent = 'Setiap baris mencocokkan gambar stimulus di kiri dengan gambar jawaban di kanan.';
            } else if (currentMode === 'image_text' || currentMode === 'image') {
                instruction.textContent = 'Setiap baris mencocokkan gambar di sisi kiri dengan teks pilihan di sisi kanan.';
            } else if (currentMode === 'text_image') {
                instruction.textContent = 'Setiap baris mencocokkan teks premis di sisi kiri dengan gambar di sisi kanan.';
            } else {
                instruction.textContent = 'Isi item premis di sebelah kiri dan pasangan jawaban di sebelah kanan.';
            }
        }

        list.innerHTML = '';
        const items = pairs.length ? pairs : [{ left: '', right: '' }, { left: '', right: '' }, { left: '', right: '' }];
        items.forEach((pair, idx) => {
            list.appendChild(createPairItem(idx, pair.left || '', pair.right || '', currentMode));
        });
        syncPairs(row);
    };

    let activePageIndex = 0;

    const renderPagination = () => {
        const total = rows.children.length;
        const paginationHeader = builder.querySelector('[data-question-pagination-header]');
        if (paginationHeader) {
            paginationHeader.hidden = (total === 0);
        }
        if (total === 0) {
            const tabsContainer = builder.querySelector('[data-question-tabs]');
            if (tabsContainer) tabsContainer.replaceChildren();
            return;
        }
        activePageIndex = Math.max(0, Math.min(activePageIndex, total - 1));

        [...rows.children].forEach((row, index) => {
            row.hidden = index !== activePageIndex;
        });

        const tabsContainer = builder.querySelector('[data-question-tabs]');
        if (tabsContainer) {
            tabsContainer.replaceChildren();
            for (let index = 0; index < total; index++) {
                const row = rows.children[index];
                const ptsInput = row?.querySelector('input[data-q-field="points"]');
                const pts = ptsInput ? (parseInt(ptsInput.value) || 0) : 0;
                const tab = document.createElement('button');
                const isActive = index === activePageIndex;
                tab.type = 'button';
                tab.className = isActive
                    ? 'px-2.5 py-1 text-xs font-bold rounded-md bg-brand text-white shadow-2xs transition shrink-0'
                    : 'px-2.5 py-1 text-xs font-medium rounded-md bg-slate-50 border border-line/60 text-ink hover:bg-slate-100 transition shrink-0';
                tab.textContent = pts > 0 ? `Soal ${index + 1} (${pts}p)` : `Soal ${index + 1}`;
                tab.addEventListener('click', () => {
                    activePageIndex = index;
                    renderPagination();
                });
                tabsContainer.appendChild(tab);
            }
        }

        builder.querySelectorAll('[data-prev-question]').forEach(button => {
            button.disabled = activePageIndex === 0;
            button.classList.toggle('opacity-50', activePageIndex === 0);
            button.classList.toggle('pointer-events-none', activePageIndex === 0);
        });
        builder.querySelectorAll('[data-next-question]').forEach(button => {
            button.disabled = activePageIndex === total - 1;
            button.classList.toggle('opacity-50', activePageIndex === total - 1);
            button.classList.toggle('pointer-events-none', activePageIndex === total - 1);
        });
    };

    const update = () => {
        const category = parseCategory(type.value);
        const active = ['kuis', 'uts', 'uas'].includes(category);
        const questionStep = document.querySelector('[data-content-form]')?.dataset.step === 'questions';
        builder.hidden = !active || !questionStep;
        builder.querySelectorAll('input,textarea,select').forEach(input => input.disabled = !active);

        const hideCoding = ['tugas', 'kuis', 'uts', 'uas'].includes(category);
        const templateCodingOption = template.content.querySelector('select[data-q-field="type"] option[value="coding"]');
        if (templateCodingOption) {
            templateCodingOption.hidden = hideCoding;
            templateCodingOption.disabled = hideCoding;
        }

        const summaryPanel = builder.querySelector('[data-cpmk-summary-panel]');
        const summaryRows = summaryPanel?.querySelector('[data-cpmk-summary-rows]');
        const statEl = builder.querySelector('[data-cpmk-summary-stat]');
        const totalPointsBadge = builder.querySelector('[data-total-points-badge]');
        const countInput = builder.querySelector('[data-target-question-count]');
        const cpmkCounts = {};
        const cpmkPoints = {};
        const cpmkLabels = {};
        const totalSoal = rows.children.length;
        let totalPoints = 0;

        [...rows.children].forEach((row, index) => {
            const numBadge = row.querySelector('[data-question-number-badge]');
            if (numBadge) numBadge.textContent = `${index + 1}`;
            const number = row.querySelector('[data-question-number]');
            if (number) number.textContent = `Soal ${index + 1}`;
            row.querySelectorAll('[data-q-field]').forEach(input => input.name = `questions[${index}][${input.dataset.qField}]`);

            const radioName = `correct_choice_${index}`;
            row.querySelectorAll('[data-choice-correct-input][type="radio"]').forEach(inp => {
                inp.name = radioName;
            });
            row.querySelectorAll('[data-pair-mode]').forEach(inp => {
                inp.name = `pair_mode_${index}`;
            });

            const pointsInput = row.querySelector('input[data-q-field="points"]');
            const pts = pointsInput ? (parseInt(pointsInput.value) || 0) : 0;
            totalPoints += pts;

            const pointShare = row.querySelector('[data-q-point-share]');
            if (pointShare) {
                pointShare.textContent = `${pts} / 100`;
            }

            const qTypeSelect = row.querySelector('select[data-q-field="type"]');
            const codingOption = qTypeSelect?.querySelector('option[value="coding"]');
            if (codingOption) {
                codingOption.hidden = hideCoding;
                codingOption.disabled = hideCoding;
            }
            if (hideCoding && qTypeSelect?.value === 'coding') qTypeSelect.value = 'uraian';
            const qType = qTypeSelect?.value || 'uraian';
            row.querySelector('[data-q-options]').hidden = !['pilihan', 'kompleks'].includes(qType);
            const b = row.querySelector('[data-q-boolean]');
            if (b) b.hidden = qType !== 'benar_salah';
            const m = row.querySelector('[data-q-matching]');
            if (m) m.hidden = qType !== 'mencocokkan';

            const scoreModeContainer = row.querySelector('[data-q-score-mode-container]');
            if (scoreModeContainer) scoreModeContainer.hidden = qType !== 'kompleks';

            const essayInfo = row.querySelector('[data-q-essay-info]');
            if (essayInfo) essayInfo.hidden = qType !== 'uraian';

            const essayEl = row.querySelector('[data-q-essay]');
            if (essayEl) essayEl.hidden = qType !== 'uraian';

            const cpmkSelect = row.querySelector('select[data-q-field="cpmk"]');
            const cpmkBadge = row.querySelector('[data-q-cpmk-badge]');
            const cpmkCode = cpmkSelect?.value || 'CPMK';
            if (cpmkBadge) cpmkBadge.textContent = cpmkCode;

            if (cpmkCode) {
                cpmkCounts[cpmkCode] = (cpmkCounts[cpmkCode] || 0) + 1;
                cpmkPoints[cpmkCode] = (cpmkPoints[cpmkCode] || 0) + pts;
                if (cpmkSelect && cpmkSelect.selectedIndex >= 0) {
                    cpmkLabels[cpmkCode] = cpmkSelect.options[cpmkSelect.selectedIndex].text;
                }
            }
        });

        // Update Live Total Point Summary (n / 100)
        if (totalPointsBadge) {
            totalPointsBadge.className = 'text-xs font-semibold text-slate-700';
            if (totalPoints === 100) {
                totalPointsBadge.textContent = 'Total Skor: 100 / 100';
            } else if (totalPoints < 100) {
                totalPointsBadge.textContent = `Total Skor: ${totalPoints} / 100 (Kurang ${100 - totalPoints})`;
            } else {
                totalPointsBadge.textContent = `Total Skor: ${totalPoints} / 100 (Lebih +${totalPoints - 100})`;
            }
        }

        const emptyState = builder.querySelector('[data-question-empty-state]');
        if (emptyState) emptyState.hidden = (rows.children.length > 0);

        // Update Live CPMK Summary Panel
        if (summaryPanel && summaryRows) {
            const uniqueCpmkCodes = Object.keys(cpmkCounts);
            if (statEl) {
                statEl.textContent = `Ringkasan CPMK (${uniqueCpmkCodes.length})`;
            }
            if (totalSoal === 0) {
                summaryRows.innerHTML = '<tr><td colspan="4" class="py-2.5 px-3 text-center text-muted italic">Tambahkan soal untuk melihat ringkasan</td></tr>';
            } else {
                summaryRows.innerHTML = '';
                uniqueCpmkCodes.forEach(code => {
                    const count = cpmkCounts[code];
                    const bobot = totalSoal > 0 ? (count / totalSoal * 100) : 0;
                    const porsi = count > 0 ? (100 / count) : 0;
                    const label = cpmkLabels[code] || code;

                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50/70 transition';
                    tr.innerHTML = `
                        <td class="py-2 px-3">
                            <span class="font-bold text-ink">${code}</span>
                            <span class="block text-[11px] text-muted line-clamp-1">${label}</span>
                        </td>
                        <td class="py-2 px-3 text-center font-mono font-medium text-ink">${count} soal</td>
                        <td class="py-2 px-3 text-center">
                            <span class="inline-block px-2 py-0.5 rounded font-mono font-bold text-xs bg-brand/10 text-brand">
                                ${bobot.toFixed(1).replace(/\.0$/, '')}%
                            </span>
                            <span class="text-[10px] text-muted font-normal block mt-0.5">(${count}/${totalSoal})</span>
                        </td>
                        <td class="py-2 px-3 text-center font-mono font-bold text-slate-800">
                            ${porsi.toFixed(2).replace(/\.00$/, '')}
                            <span class="text-[10px] text-muted font-normal block mt-0.5">(100 / ${count})</span>
                        </td>
                    `;
                    summaryRows.appendChild(tr);
                });
            }
        }

        if (countInput && document.activeElement !== countInput && rows.children.length > 0) {
            countInput.value = rows.children.length;
        }

        builder.querySelector('[data-question-total]').textContent = `${rows.children.length} soal`;
        renderPagination();
    };

    const add = (data = {}, setAsActive = true) => {
        const row = template.content.firstElementChild.cloneNode(true);

        // Smart point calculation if points not provided in data
        if (data.points === undefined) {
            const currentTotal = [...rows.children].reduce((sum, r) => {
                const p = r.querySelector('input[data-q-field="points"]');
                return sum + (p ? (parseInt(p.value) || 0) : 0);
            }, 0);
            if (rows.children.length === 0) {
                data.points = 20;
            } else if (100 - currentTotal > 0) {
                data.points = 100 - currentTotal;
            } else {
                data.points = 20;
            }
        }

        row.querySelectorAll('[data-q-field]').forEach(input => {
            if (input.type !== 'file' && data[input.dataset.qField] !== undefined) {
                input.value = data[input.dataset.qField];
            }
        });

        // Initialize choices
        const rawOptions = data.options || '';
        const qType = data.type || row.querySelector('[data-q-field="type"]').value;
        if (['pilihan', 'kompleks'].includes(qType)) {
            const choices = rawOptions.split('\n').map(s => s.trim()).filter(Boolean);
            renderChoices(row, choices, data.correct_answer);
        } else {
            renderChoices(row, []);
        }

        // Initialize matching pairs
        if (qType === 'mencocokkan') {
            const lines = rawOptions.split('\n').map(s => s.trim()).filter(Boolean);
            const pairs = lines.map(line => {
                const parts = line.split('=');
                return { left: (parts[0] || '').trim(), right: (parts.slice(1).join('=') || '').trim() };
            });
            renderPairs(row, pairs);
        } else {
            renderPairs(row, []);
        }

        rows.append(row);
        if (setAsActive) activePageIndex = rows.children.length - 1;
        update();
    };

    const countInput = builder.querySelector('[data-target-question-count]');

    const setQuestionCount = (targetCount) => {
        targetCount = Math.max(1, parseInt(targetCount) || 1);
        const currentCount = rows.children.length;
        if (targetCount > currentCount) {
            for (let i = currentCount; i < targetCount; i++) {
                add({}, false);
            }
        } else if (targetCount < currentCount) {
            while (rows.children.length > targetCount) {
                rows.lastElementChild.remove();
            }
        }
        // Auto-distribute 100 points evenly across all targetCount questions
        const base = Math.floor(100 / targetCount);
        const remainder = 100 - (base * targetCount);
        [...rows.children].forEach((row, idx) => {
            const input = row.querySelector('input[data-q-field="points"]');
            if (input) {
                input.value = idx < remainder ? (base + 1) : base;
            }
        });
        if (countInput) countInput.value = targetCount;
        activePageIndex = 0;
        update();
    };

    builder.querySelector('[data-apply-question-count]')?.addEventListener('click', () => {
        const target = parseInt(countInput?.value) || 5;
        setQuestionCount(target);
    });

    builder.querySelector('[data-toggle-cpmk-summary]')?.addEventListener('click', () => {
        const panel = builder.querySelector('[data-cpmk-summary-panel]');
        if (panel) panel.hidden = !panel.hidden;
    });

    countInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const target = parseInt(countInput.value) || 5;
            setQuestionCount(target);
        }
    });

    builder.querySelectorAll('[data-add-question]').forEach(button => {
        button.addEventListener('click', () => add({}, true));
    });
    builder.querySelectorAll('[data-auto-distribute-points]').forEach(button => {
        button.addEventListener('click', () => {
            const count = rows.children.length;
            if (count === 0) return;
            const base = Math.floor(100 / count);
            const remainder = 100 - (base * count);
            [...rows.children].forEach((row, idx) => {
                const input = row.querySelector('input[data-q-field="points"]');
                if (input) {
                    input.value = idx < remainder ? (base + 1) : base;
                }
            });
            update();
        });
    });
    builder.querySelectorAll('[data-prev-question]').forEach(button => {
        button.addEventListener('click', () => {
            if (activePageIndex > 0) activePageIndex--;
            renderPagination();
        });
    });
    builder.querySelectorAll('[data-next-question]').forEach(button => {
        button.addEventListener('click', () => {
            if (activePageIndex < rows.children.length - 1) activePageIndex++;
            renderPagination();
        });
    });
    rows.addEventListener('invalid', event => {
        const row = event.target.closest('[data-question-row]');
        if (!row) return;
        const index = [...rows.children].indexOf(row);
        if (index >= 0) {
            activePageIndex = index;
            renderPagination();
        }
    }, true);

    rows.addEventListener('input', (event) => {
        const row = event.target.closest('[data-question-row]');
        if (event.target.matches('[data-choice-item-input]')) {
            if (row) syncChoices(row);
        } else if (event.target.matches('[data-pair-left], [data-pair-right]')) {
            const isLeft = event.target.matches('[data-pair-left]');
            const side = isLeft ? 'left' : 'right';
            const item = event.target.closest('[data-pair-item]');
            const preview = item?.querySelector(`[data-pair-preview="${side}"]`);
            const previewBox = item?.querySelector(`[data-pair-preview-box="${side}"]`);
            const val = event.target.value.trim();
            if (preview && (val.startsWith('http') || val.startsWith('data:image') || val.startsWith('/'))) {
                preview.src = val;
                if (previewBox) previewBox.classList.remove('hidden');
            } else if (previewBox) {
                previewBox.classList.add('hidden');
            }
            if (row) syncPairs(row);
        }
        update();
    });

    rows.addEventListener('change', (event) => {
        const row = event.target.closest('[data-question-row]');
        if (!row) return;

        if (event.target.matches('[data-pair-mode]')) {
            const list = row.querySelector('[data-pair-list]');
            const existingPairs = [];
            list.querySelectorAll('[data-pair-item]').forEach(item => {
                const l = item.querySelector('[data-pair-left]')?.value || '';
                const r = item.querySelector('[data-pair-right]')?.value || '';
                existingPairs.push({ left: l, right: r });
            });
            renderPairs(row, existingPairs);
            return;
        }

        if (event.target.matches('[data-pair-file]')) {
            const side = event.target.dataset.pairFile || 'left';
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const item = event.target.closest('[data-pair-item]');
                    const inp = item?.querySelector(side === 'right' ? '[data-pair-right]' : '[data-pair-left]');
                    const preview = item?.querySelector(`[data-pair-preview="${side}"]`);
                    const previewBox = item?.querySelector(`[data-pair-preview-box="${side}"]`);
                    if (inp) inp.value = e.target.result;
                    if (preview) preview.src = e.target.result;
                    if (previewBox) previewBox.classList.remove('hidden');
                    syncPairs(row);
                };
                reader.readAsDataURL(file);
            }
            return;
        }

        if (event.target.matches('[data-choice-correct-input]')) {
            syncChoices(row);
        }

        if (event.target.dataset.qField === 'type') {
            const qType = event.target.value;
            if (['pilihan', 'kompleks'].includes(qType)) {
                const existingChoices = [...row.querySelectorAll('[data-choice-item-input]')].map(i => i.value);
                const currentCorrect = row.querySelector('input[data-q-field="correct_answer"]')?.value;
                renderChoices(row, existingChoices, currentCorrect);
            } else if (qType === 'mencocokkan') {
                renderPairs(row);
            }
        }
        update();

        if (event.target.dataset.qField === 'image') {
            const previewBox = row.querySelector('[data-q-preview-box]');
            const preview = row.querySelector('[data-q-preview]');
            const altInput = row.querySelector('[data-q-field="alt"]');
            const file = event.target.files[0];

            if (preview && preview.dataset.url) URL.revokeObjectURL(preview.dataset.url);
            if (previewBox) previewBox.hidden = !file;
            if (altInput && file) {
                const prompt = row.querySelector('[data-q-field="prompt"]')?.value.trim();
                altInput.value = (prompt ? `Gambar pendukung untuk ${prompt}` : 'Gambar pendukung soal').slice(0, 300);
            }

            if (file && preview) {
                preview.src = URL.createObjectURL(file);
                preview.dataset.url = preview.src;
            }
        }
    });

    rows.addEventListener('click', (event) => {
        const row = event.target.closest('[data-question-row]');
        if (!row) return;

        // Add choice button
        if (event.target.closest('[data-add-choice-btn]')) {
            const list = row.querySelector('[data-choice-list]');
            const count = list.children.length;
            const qType = row.querySelector('select[data-q-field="type"]')?.value || 'pilihan';
            const isComplex = qType === 'kompleks';
            const inputType = isComplex ? 'checkbox' : 'radio';
            const rowIndex = [...rows.children].indexOf(row);
            const radioName = `correct_choice_${rowIndex >= 0 ? rowIndex : '0'}`;
            const letter = letters[count] || String(count + 1);

            const item = document.createElement('div');
            item.className = 'flex items-center gap-2 p-1 rounded-lg hover:bg-slate-50/70 transition';
            item.innerHTML = `
                <label class="flex items-center gap-1.5 cursor-pointer px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-xs shrink-0 select-none border border-line/60" title="${isComplex ? 'Centang jika opsi ini adalah jawaban benar' : 'Pilih opsi ini sebagai kunci jawaban benar'}">
                    <input type="${inputType}" ${!isComplex ? `name="${radioName}"` : ''} value="${letter}" data-choice-correct-input class="text-brand h-3.5 w-3.5 ${isComplex ? 'rounded' : ''}">
                    <span class="text-[11px] font-semibold text-slate-700">Kunci</span>
                </label>
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-canvas text-xs font-bold text-ink shrink-0 border border-line/50" data-choice-letter>${letter}</span>
                <input type="text" class="field text-xs py-1.5 flex-1" placeholder="Pilihan ${letter}..." data-choice-item-input>
                <button type="button" class="h-7 w-7 rounded-md text-muted hover:text-danger hover:bg-rose-50 flex items-center justify-center text-sm" data-remove-choice title="Hapus pilihan">×</button>
            `;
            list.appendChild(item);
            item.querySelector('input[data-choice-item-input]').focus();
            syncChoices(row);
            return;
        }

        // Remove choice button
        if (event.target.closest('[data-remove-choice]')) {
            const list = row.querySelector('[data-choice-list]');
            if (list.children.length > 2) {
                event.target.closest('div').remove();
                [...list.children].forEach((child, idx) => {
                    const l = child.querySelector('[data-choice-letter]');
                    const letter = letters[idx] || String(idx + 1);
                    if (l) l.textContent = letter;
                    const correctInp = child.querySelector('[data-choice-correct-input]');
                    if (correctInp) correctInp.value = letter;
                    const textInp = child.querySelector('[data-choice-item-input]');
                    if (textInp) textInp.placeholder = `Pilihan ${letter}...`;
                });
                syncChoices(row);
            }
            return;
        }

        // Add pair button
        if (event.target.closest('[data-add-pair-btn]')) {
            const list = row.querySelector('[data-pair-list]');
            const count = list.children.length;
            const mode = row.querySelector('[data-pair-mode]:checked')?.value || 'text';
            const item = createPairItem(count, '', '', mode);
            list.appendChild(item);
            item.querySelector('input[data-pair-left], input[data-pair-right]')?.focus();
            syncPairs(row);
            return;
        }

        // Remove pair button
        if (event.target.closest('[data-remove-pair]')) {
            const list = row.querySelector('[data-pair-list]');
            if (list.children.length > 1) {
                event.target.closest('[data-pair-item]').remove();
                [...list.children].forEach((child, idx) => {
                    const l = child.querySelector('[data-pair-label]');
                    if (l) l.textContent = `Item ${idx + 1}:`;
                });
                syncPairs(row);
            }
            return;
        }

        // Clear pair image button
        if (event.target.closest('[data-pair-clear-img]')) {
            const btn = event.target.closest('[data-pair-clear-img]');
            const side = btn.dataset.pairClearImg;
            const item = event.target.closest('[data-pair-item]');
            const inp = item?.querySelector(side === 'right' ? '[data-pair-right]' : '[data-pair-left]');
            const preview = item?.querySelector(`[data-pair-preview="${side}"]`);
            const previewBox = item?.querySelector(`[data-pair-preview-box="${side}"]`);
            const fileInp = item?.querySelector(`[data-pair-file="${side}"]`);
            if (inp) inp.value = '';
            if (fileInp) fileInp.value = '';
            if (preview) preview.removeAttribute('src');
            if (previewBox) previewBox.classList.add('hidden');
            syncPairs(row);
            return;
        }

        // Remove image button
        if (event.target.closest('[data-q-remove-image]')) {
            const fileInput = row.querySelector('input[data-q-field="image"]');
            const previewBox = row.querySelector('[data-q-preview-box]');
            const preview = row.querySelector('[data-q-preview]');
            const altInput = row.querySelector('[data-q-field="alt"]');

            if (fileInput) fileInput.value = '';
            if (preview && preview.dataset.url) URL.revokeObjectURL(preview.dataset.url);
            if (preview) preview.removeAttribute('src');
            if (previewBox) previewBox.hidden = true;
            if (altInput) {
                altInput.value = '';
            }
            return;
        }

        // Remove question button
        if (event.target.closest('[data-remove-question]') && rows.children.length > 1) {
            row.remove();
            activePageIndex = Math.min(activePageIndex, rows.children.length - 1);
            update();
        }
    });

    const categoryQuestionsMap = {};
    let activeCategory = parseCategory(type?.value);

    const getQuestionsDataFromRows = () => [...rows.children].map(row => {
        const question = {};
        row.querySelectorAll('[data-q-field]').forEach(input => {
            question[input.dataset.qField] = input.value;
        });
        return question;
    });

    const switchCategoryQuestions = newCategory => {
        if (newCategory === activeCategory) return;
        if (activeCategory) categoryQuestionsMap[activeCategory] = getQuestionsDataFromRows();
        activeCategory = newCategory;
        rows.replaceChildren();
        const savedQuestions = categoryQuestionsMap[newCategory];
        if (savedQuestions?.length) {
            savedQuestions.forEach((question, index) => add(question, index === 0));
        }
        activePageIndex = 0;
        update();
    };

    const old = JSON.parse(builder.querySelector('[data-old-questions]').textContent);
    if (old.length) {
        if (activeCategory) categoryQuestionsMap[activeCategory] = old;
        old.forEach((question, index) => add(question, index === 0));
    }

    const sync = () => {
        const category = parseCategory(type.value);
        if (category !== activeCategory) switchCategoryQuestions(category);
        update();
        const quizOrder = document.querySelector('[data-quiz-order-settings]');
        if (quizOrder) quizOrder.hidden = !['kuis', 'uts', 'uas'].includes(category);
    };
    type.addEventListener('change', sync);
    type.addEventListener('input', sync);
    const customType = document.querySelector('[data-custom-type]');
    customType?.addEventListener('input', sync);
    customType?.addEventListener('change', sync);
    document.querySelector('#module')?.addEventListener('input', sync);
    document.querySelector('#title')?.addEventListener('input', sync);
    document.querySelector('#body')?.addEventListener('input', sync);
    sync();
}

// Google Classroom style submission widget (+ Tambah atau buat, Modal Link, File Upload, Text Answer)
const addWorkDropdown = document.querySelector('[data-add-work-dropdown]');
if (addWorkDropdown) {
    const toggleBtn = addWorkDropdown.querySelector('[data-toggle-dropdown]');
    const menu = addWorkDropdown.querySelector('[data-dropdown-menu]');
    const fileInput = document.querySelector('[data-submission-files]');
    const linkInput = document.querySelector('[data-submission-link]');
    const activeList = document.querySelector('[data-active-attachments]');
    const textBox = document.querySelector('[data-text-answer-box]');
    const linkModal = document.querySelector('#link-modal');
    const modalInput = document.querySelector('#modal-link-input');
    const modalError = document.querySelector('#modal-link-error');
    const modalCancel = document.querySelector('#modal-link-cancel');
    const modalSubmit = document.querySelector('#modal-link-submit');
    const removeTextBoxBtn = document.querySelector('[data-remove-text-box]');

    toggleBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.hidden = !menu.hidden;
    });

    document.addEventListener('click', (e) => {
        if (!addWorkDropdown.contains(e.target)) {
            if (menu) menu.hidden = true;
        }
    });

    // Action 1: Link
    addWorkDropdown.querySelector('[data-action-add="link"]')?.addEventListener('click', () => {
        menu.hidden = true;
        if (linkModal) {
            modalInput.value = linkInput?.value || '';
            if (modalError) modalError.classList.add('hidden');
            linkModal.showModal();
            modalInput.focus();
        }
    });

    modalCancel?.addEventListener('click', () => linkModal?.close());

    modalSubmit?.addEventListener('click', () => {
        const val = modalInput.value.trim();
        if (!val || (!val.startsWith('http://') && !val.startsWith('https://'))) {
            if (modalError) modalError.classList.remove('hidden');
            return;
        }
        if (linkInput) linkInput.value = val;
        renderLinkChip(val);
        linkModal?.close();
    });

    const renderLinkChip = (url) => {
        let chip = activeList?.querySelector('[data-link-chip]');
        if (!chip) {
            chip = document.createElement('div');
            chip.setAttribute('data-link-chip', '');
            chip.className = 'flex items-center justify-between text-xs p-2.5 rounded-lg bg-canvas border border-line/40';
            activeList?.appendChild(chip);
        }
        chip.innerHTML = `
            <div class="flex items-center gap-2 min-w-0">
                <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                <a href="${url.replace(/"/g, '&quot;')}" target="_blank" class="text-brand truncate font-medium hover:underline">${url}</a>
            </div>
            <button type="button" class="text-muted hover:text-danger text-sm font-bold px-1" title="Hapus tautan">×</button>
        `;
        chip.querySelector('button')?.addEventListener('click', () => {
            chip.remove();
            if (linkInput) linkInput.value = '';
        });
    };

    // Action 2: File
    addWorkDropdown.querySelector('[data-action-add="file"]')?.addEventListener('click', () => {
        menu.hidden = true;
        fileInput?.click();
    });

    fileInput?.addEventListener('change', () => {
        activeList?.querySelectorAll('[data-file-chip]').forEach(c => c.remove());
        const files = [...(fileInput.files || [])];
        files.forEach((file, idx) => {
            const chip = document.createElement('div');
            chip.setAttribute('data-file-chip', '');
            chip.className = 'flex items-center justify-between text-xs p-2.5 rounded-lg bg-canvas border border-line/40';
            chip.innerHTML = `
                <div class="flex items-center gap-2 min-w-0">
                    <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    <span class="text-ink truncate font-medium">${file.name}</span>
                    <span class="text-[10px] text-muted">(${(file.size / 1024 / 1024).toFixed(1)} MB)</span>
                </div>
            `;
            activeList?.appendChild(chip);
        });
    });

    // Action 3: Text
    addWorkDropdown.querySelector('[data-action-add="text"]')?.addEventListener('click', () => {
        menu.hidden = true;
        if (textBox) {
            textBox.hidden = false;
            textBox.querySelector('textarea')?.focus();
        }
    });

    removeTextBoxBtn?.addEventListener('click', () => {
        if (textBox) {
            const ta = textBox.querySelector('textarea');
            if (ta) ta.value = '';
            textBox.hidden = true;
        }
    });

    // If initial link exists from previous submission or old input
    if (linkInput && linkInput.value) {
        renderLinkChip(linkInput.value);
    }
}
