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
const contentType = document.querySelector('[data-content-type]');
if (contentType) {
    const questionType = document.querySelector('[data-question-type]');
    const sync = () => {
        const assignment = ['tugas', 'coding', 'kuis'].includes(contentType.value);
        document.querySelector('[data-assignment-fields]').hidden = !assignment;
        const hasChoices = assignment && ['pilihan', 'kompleks'].includes(questionType.value);
        document.querySelector('[data-choice-fields]').hidden = !hasChoices;
        document.querySelectorAll('[data-option-images] input').forEach(input => input.disabled = !hasChoices);
    };
    contentType.addEventListener('change', () => { if (contentType.value === 'coding') questionType.value = 'coding'; sync(); });
    questionType.addEventListener('change', sync);
    sync();
}

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
            point.querySelector('circle').setAttribute('cy', y);
            const label = point.querySelector('[data-point-value]');
            label.setAttribute('y', y - 13);
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
    const clear = () => { if (url) URL.revokeObjectURL(url); preview.hidden = true; remove.hidden = true; alt.required = false; questionImage.setCustomValidity(''); };
    questionImage.addEventListener('change', () => {
        clear();
        const file = questionImage.files[0];
        if (!file) return;
        if (!['image/jpeg','image/png','image/webp'].includes(file.type) || file.size > 5*1024*1024) {
            questionImage.setCustomValidity('Gambar harus JPG, PNG, atau WebP maksimal 5 MB.'); questionImage.reportValidity(); return;
        }
        url = URL.createObjectURL(file); preview.src = url; preview.hidden = false; remove.hidden = false; alt.required = true;
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
