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

const editorMount = document.querySelector('[data-code-editor]');
const editorSource = document.querySelector('[data-code-source]');

if (editorMount && editorSource) {
    Promise.all([
        import('codemirror'),
        import('@codemirror/lang-python'),
        import('@codemirror/theme-one-dark'),
    ]).then(([{ basicSetup, EditorView }, { python }, { oneDark }]) => {
        let context = null;
        const mention = document.querySelector('[data-mention-code]');
        const saveStatus = document.querySelector('[data-code-save-status]');
        const draftKey = `sale.code.assignment.${editorMount.dataset.assignmentId}`;
        let draft = editorSource.value;
        try { draft = localStorage.getItem(draftKey) ?? draft; } catch { /* Storage is optional. */ }
        document.querySelector('[data-code-submit] button').disabled = false;
        const editor = new EditorView({
            doc: draft,
            extensions: [
                basicSetup,
                python(),
                oneDark,
                EditorView.lineWrapping,
                EditorView.updateListener.of((update) => {
                    if (mention) mention.disabled = update.state.selection.main.empty;
                    if (update.docChanged) {
                        try {
                            localStorage.setItem(draftKey, update.state.doc.toString());
                            saveStatus.textContent = 'Draf tersimpan di browser ini';
                        } catch { saveStatus.textContent = 'Draf belum tersimpan; penyimpanan browser tidak tersedia'; }
                    }
                }),
            ],
            parent: editorMount,
        });
        mention?.addEventListener('click', () => {
            const { from, to, empty } = editor.state.selection.main;
            if (empty) return;
            const first = editor.state.doc.lineAt(from).number;
            const last = editor.state.doc.lineAt(Math.max(from, to - 1)).number;
            context = { label: `main.py · Baris ${first}${last === first ? '' : `–${last}`}`, code: editor.state.sliceDoc(from, to) };
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
            if (!window.confirm('Kembalikan kode ke template awal? Draf saat ini akan diganti.')) return;
            editor.dispatch({ changes: { from: 0, to: editor.state.doc.length, insert: editorSource.value } });
        });
        document.querySelector('[data-code-submit]')?.addEventListener('submit', () => {
            document.querySelector('[data-code-answer]').value = editor.state.doc.toString();
        });
        document.querySelector('[data-ai-form]')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const input = document.querySelector('#assistant-message');
            if (!input.value.trim()) return;
            const messages = document.querySelector('[data-ai-messages]');
            const article = document.createElement('article');
            article.className = 'rounded-lg border border-line p-3';
            const author = document.createElement('p');
            author.className = 'mb-2 text-xs font-semibold text-muted';
            author.textContent = 'Kamu · pratinjau pesan';
            article.append(author);
            if (context) {
                const label = document.createElement('p');
                label.className = 'text-xs font-semibold text-brand';
                label.textContent = context.label;
                const code = document.createElement('pre');
                code.className = 'my-2 max-h-40 overflow-auto rounded bg-canvas p-2 text-xs';
                code.textContent = context.code;
                article.append(label, code);
            }
            const question = document.createElement('p');
            question.className = 'prose-content';
            question.textContent = input.value.trim();
            article.append(question);
            messages.append(article);
            const status = document.createElement('p');
            status.className = 'text-xs text-muted';
            status.textContent = 'Pesan dan konteks kode siap. Hubungan ke AI/RAG belum tersedia; pesan ini belum dikirim ke layanan AI.';
            messages.append(status);
            input.value = '';
            context = null;
            document.querySelector('[data-code-context]').hidden = true;
            messages.scrollTop = messages.scrollHeight;
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
    input.addEventListener('change', () => {
        const list = input.parentElement.querySelector('[data-file-list]');
        const files = [...input.files];
        input.setCustomValidity(files.length > 5 || files.some(file => file.size > 20 * 1024 * 1024) ? 'Maksimal 5 berkas, masing-masing 20 MB.' : '');
        if (list) list.textContent = files.map(file => `${file.name} (${(file.size / 1024 / 1024).toFixed(1)} MB)`).join(' · ');
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
const contentType = document.querySelector('[data-content-type]');
if (contentType) {
    const questionType = document.querySelector('[data-question-type]');
    const sync = () => {
        const assignment = ['tugas', 'coding', 'kuis'].includes(contentType.value);
        document.querySelector('[data-assignment-fields]').hidden = !assignment;
        document.querySelector('[data-choice-fields]').hidden = !assignment || !['pilihan', 'kompleks'].includes(questionType.value);
    };
    contentType.addEventListener('change', () => { if (contentType.value === 'coding') questionType.value = 'coding'; sync(); });
    questionType.addEventListener('change', sync);
    sync();
}
