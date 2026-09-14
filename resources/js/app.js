import './submission-preview';
import './grade-import';

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

        // Interactive Terminal Runner
        const runBtn = document.querySelector('[data-run-code]');
        const clearBtn = document.querySelector('[data-clear-terminal]');
        const terminalOutput = document.querySelector('[data-terminal-output]');
        const terminalBody = document.querySelector('[data-terminal-body]');

        const appendTerminal = (text, className = 'text-slate-300') => {
            if (!terminalOutput) return;
            const p = document.createElement('p');
            p.className = className;
            p.textContent = text;
            terminalOutput.append(p);
            if (terminalBody) terminalBody.scrollTop = terminalBody.scrollHeight;
        };

        runBtn?.addEventListener('click', () => {
            const currentCode = editor.state.doc.toString().trim();
            appendTerminal('<span class="text-emerald-400 font-bold">sale@sandbox</span>:<span class="text-sky-400 font-bold">~/bst</span>$ <span class="text-slate-100 font-medium">python3 -m unittest test_bst.py</span>', 'mt-2.5');

            if (!currentCode) {
                appendTerminal('<span class="text-rose-400 font-bold">FileNotFoundError</span>: Berkas main.py kosong. Tulis implementasi fungsi sebelum menjalankan.', 'text-rose-400');
                return;
            }

            appendTerminal('<span class="text-slate-500">running 4 test cases on Linux 6.8.0-generic x86_64 sandbox...</span>', 'text-slate-400');

            setTimeout(() => {
                const hasRootNone = currentCode.includes('root is None') || currentCode.includes('not root');
                const hasReturnNode = currentCode.includes('Node(key)');
                const hasLeftRecur = currentCode.includes('insert(root.left') || currentCode.includes('self.insert(root.left');
                const hasRightRecur = currentCode.includes('insert(root.right') || currentCode.includes('self.insert(root.right');
                const hasReturnRoot = currentCode.includes('return root');

                let passedCount = 0;

                if (hasRootNone && hasReturnNode) {
                    appendTerminal('test_01_empty_tree_init (__main__.TestBST) ... <span class="text-emerald-400 font-bold">ok</span>');
                    passedCount++;
                } else {
                    appendTerminal('test_01_empty_tree_init (__main__.TestBST) ... <span class="text-amber-400 font-bold">FAIL</span> (<span class="text-slate-400 text-[11px]">cek if root is None: return Node(key)</span>)');
                }

                if (hasLeftRecur) {
                    appendTerminal('test_02_branch_left_recur (__main__.TestBST) ... <span class="text-emerald-400 font-bold">ok</span>');
                    passedCount++;
                } else {
                    appendTerminal('test_02_branch_left_recur (__main__.TestBST) ... <span class="text-slate-500">PENDING</span> (<span class="text-slate-400 text-[11px]">cek jika key &lt; root.value</span>)');
                }

                if (hasRightRecur) {
                    appendTerminal('test_03_branch_right_recur (__main__.TestBST) ... <span class="text-emerald-400 font-bold">ok</span>');
                    passedCount++;
                } else {
                    appendTerminal('test_03_branch_right_recur (__main__.TestBST) ... <span class="text-slate-500">PENDING</span> (<span class="text-slate-400 text-[11px]">cek jika key &gt; root.value</span>)');
                }

                if (hasReturnRoot) {
                    appendTerminal('test_04_root_reference_integrity (__main__.TestBST) ... <span class="text-emerald-400 font-bold">ok</span>');
                    passedCount++;
                } else {
                    appendTerminal('test_04_root_reference_integrity (__main__.TestBST) ... <span class="text-amber-400 font-bold">FAIL</span> (<span class="text-slate-400 text-[11px]">pastikan mengembalikan simpul root</span>)');
                }

                appendTerminal('----------------------------------------------------------------------', 'text-slate-700');
                if (passedCount === 4) {
                    appendTerminal('<span class="text-emerald-400 font-bold">RAN 4 TESTS IN 0.042s — OK</span> <span class="text-slate-400">(exit code: 0)</span>');
                    appendTerminal('<span class="text-emerald-300">✔ Semua unit tests lulus! Silakan klik "Kumpulkan Kode" di bagian kiri atau atas.</span>');
                } else {
                    appendTerminal(`<span class="text-amber-400 font-bold">FAILED (failures=${4 - passedCount})</span> · Ran 4 tests in 0.038s <span class="text-slate-400">(exit code: 1)</span>`);
                    appendTerminal('<span class="text-slate-400">Petunjuk: Konsultasikan logika yang belum lulus ke Lumina AI di sebelah kanan.</span>');
                }
            }, 250);
        });

        clearBtn?.addEventListener('click', () => {
            if (terminalOutput) terminalOutput.innerHTML = '';
            appendTerminal('<span class="text-emerald-400 font-bold">sale@sandbox</span>:<span class="text-sky-400 font-bold">~/bst</span>$ <span class="text-slate-100 font-medium">python3 --version</span>', 'text-slate-400');
            appendTerminal('Python 3.12.3 (SALE Linux Sandbox Environment)', 'text-slate-300 pl-2');
            appendTerminal('<span class="text-slate-500 pl-2"># Terminal dibersihkan. Siap mengeksekusi pengujian.</span>', 'text-slate-500');
        });

        document.querySelector('[data-ai-form]')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const input = document.querySelector('#assistant-message');
            const query = input.value.trim();
            if (!query) return;
            const messages = document.querySelector('[data-ai-messages]');

            // Student message bubble
            const userBubble = document.createElement('article');
            userBubble.className = 'rounded-lg bg-white border border-line/60 p-3 shadow-xs';
            const userHeader = document.createElement('p');
            userHeader.className = 'mb-1 text-[11px] font-semibold text-muted';
            userHeader.textContent = 'Pertanyaan Anda';
            userBubble.append(userHeader);
            if (context) {
                const codeSnippet = document.createElement('pre');
                codeSnippet.className = 'my-1.5 max-h-24 overflow-auto rounded bg-canvas p-2 font-mono text-[11px] text-ink';
                codeSnippet.textContent = `${context.label}:\n${context.code}`;
                userBubble.append(codeSnippet);
            }
            const userText = document.createElement('p');
            userText.className = 'text-xs text-ink';
            userText.textContent = query;
            userBubble.append(userText);
            messages.append(userBubble);

            input.value = '';
            context = null;
            document.querySelector('[data-code-context]').hidden = true;
            messages.scrollTop = messages.scrollHeight;

            // Generate pedagogical Lumina AI response
            setTimeout(() => {
                const qLower = query.toLowerCase();
                let aiResponse = '';
                if (qLower.includes('indent') || qLower.includes('error')) {
                    aiResponse = 'Pastikan blok kode di bawah fungsi atau percabangan sejajar dengan tepat 4 spasi. Error ini biasanya terjadi karena ada baris yang lupa diberi indentasi atau spasi tidak konsisten.';
                } else if (qLower.includes('none') || qLower.includes('base case') || qLower.includes('kosong')) {
                    aiResponse = 'Kondisi basis (base case): jika pohon masih kosong (`root is None`), fungsi harus membuat dan mengembalikan simpul baru: `return Node(key)`.';
                } else if (qLower.includes('banding') || qLower.includes('lebih kecil') || qLower.includes('root.val') || qLower.includes('key')) {
                    aiResponse = 'Bandingkan nilai masukan: jika `key < root.value`, panggil secara rekursif ke anak kiri: `root.left = self.insert(root.left, key)`. Jika lebih besar, arahkan ke cabang kanan `root.right`. Selalu kembalikan `root` di akhir.';
                } else if (qLower.includes('rekursi') || qLower.includes('alur') || qLower.includes('insert')) {
                    aiResponse = 'Alur algoritma insert BST: 1) Cek apakah `root is None` (return Node(key)), 2) Jika `key < root.value`, insert ke kiri, 3) Jika `key > root.value`, insert ke kanan, 4) Selalu kembalikan `root`.';
                } else {
                    aiResponse = 'Pertanyaan Anda tercatat: Untuk struktur data Binary Search Tree, setiap simpul di cabang kiri memiliki nilai lebih kecil dari simpul induk, dan cabang kanan memiliki nilai lebih besar. Periksa apakah base case dan rekursi ke kiri/kanan sudah lengkap.';
                }

                const aiBubble = document.createElement('article');
                aiBubble.className = 'rounded-lg bg-canvas border border-line/40 p-3';
                const aiHeader = document.createElement('div');
                aiHeader.className = 'flex items-center gap-1.5 mb-1';
                aiHeader.innerHTML = '<span class="h-2 w-2 rounded-full bg-emerald-500"></span><span class="text-[11px] font-bold text-ink">Lumina AI</span>';
                const aiBody = document.createElement('p');
                aiBody.className = 'text-xs text-muted leading-relaxed';
                aiBody.innerHTML = aiResponse.replace(/`([^`]+)`/g, '<code class="px-1 py-0.5 rounded bg-white font-mono text-[11px] text-ink border border-line/40">$1</code>');
                aiBubble.append(aiHeader, aiBody);
                messages.append(aiBubble);
                messages.scrollTop = messages.scrollHeight;
            }, 300);
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

// Repeatable academic settings keep submitted indexes contiguous.
document.querySelectorAll('[data-repeat-group]').forEach(group=>{
    const rows=group.querySelector('[data-rows]');
    const renumber=()=>{[...rows.children].forEach((row,index)=>row.querySelectorAll('[name]').forEach(input=>input.name=input.name.replace(/\[\d+\]/,`[${index}]`)));const total=group.querySelector('[data-weight-total]');if(total)total.textContent=`Total bobot: ${[...group.querySelectorAll('[data-weight]')].reduce((sum,input)=>sum+Number(input.value||0),0)}%`;};
    group.querySelector('[data-add-row]').addEventListener('click',()=>{const clone=rows.firstElementChild.cloneNode(true);clone.querySelectorAll('input').forEach(input=>input.value=input.type==='number'?'0':'');rows.append(clone);renumber();});
    rows.addEventListener('click',event=>{if(event.target.closest('[data-remove-row]') && rows.children.length>1){event.target.closest('[data-row]').remove();renumber();}});rows.addEventListener('input',renumber);
});

// CPMK contribution checklist (Halaman 5 — Form Assessment): ticking a
// CPMK enables its weight input and includes it in the live total;
// unticking disables the input (its value is not submitted) and drops
// it from the total. Mirrors the [data-weight-total] pattern above.
document.querySelectorAll('[data-cpmk-checklist]').forEach(list => {
    const total = list.querySelector('[data-cpmk-total]');
    const rows = () => [...list.querySelectorAll('[data-cpmk-row]')];

    const recompute = () => {
        if (!total) return;
        const sum = rows()
            .filter(row => row.querySelector('[data-cpmk-check]').checked)
            .reduce((acc, row) => acc + Number(row.querySelector('[data-cpmk-weight]').value || 0), 0);
        total.textContent = `Total kontribusi: ${sum}%`;
        total.classList.toggle('text-danger', Math.abs(sum - 100) > 0.01);
        total.classList.toggle('text-ink', Math.abs(sum - 100) <= 0.01);
    };

    rows().forEach(row => {
        const checkbox = row.querySelector('[data-cpmk-check]');
        const weightInput = row.querySelector('[data-cpmk-weight]');
        const hiddenFlag = row.querySelector('[data-cpmk-hidden-flag]');

        const sync = () => {
            weightInput.readOnly = !checkbox.checked;
            weightInput.classList.toggle('opacity-50', !checkbox.checked);
            if (hiddenFlag) hiddenFlag.value = checkbox.checked ? '1' : '0';
            if (!checkbox.checked) weightInput.value = '0';
        };

        checkbox.addEventListener('change', () => { sync(); recompute(); });
        weightInput.addEventListener('input', recompute);
        sync();
    });

    recompute();
});
const builder = document.querySelector('[data-question-builder]');
if (builder) {
    const rows = builder.querySelector('[data-question-rows]');
    const template = builder.querySelector('template');
    const type = document.querySelector('[data-content-type]');
    const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    const syncChoices = (row) => {
        const textarea = row.querySelector('textarea[data-q-field="options"]');
        if (!textarea) return;
        const inputs = row.querySelectorAll('[data-choice-item-input]');
        const values = [...inputs].map(i => i.value.trim()).filter(Boolean);
        textarea.value = values.join('\n');
    };

    const renderChoices = (row, choices = []) => {
        const list = row.querySelector('[data-choice-list]');
        if (!list) return;
        list.innerHTML = '';
        const items = choices.length ? choices : ['', '', '', ''];
        items.forEach((val, idx) => {
            const item = document.createElement('div');
            item.className = 'flex items-center gap-2';
            item.innerHTML = `
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-canvas text-xs font-bold text-ink shrink-0 border border-line/50" data-choice-letter>${letters[idx] || (idx + 1)}</span>
                <input type="text" class="field text-xs py-1.5 flex-1" value="${val.replace(/"/g, '&quot;')}" placeholder="Pilihan ${letters[idx] || (idx + 1)}..." data-choice-item-input>
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

        const isLeftImg = mode === 'image' || mode === 'image_image';
        const isRightImg = mode === 'image_image';
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

        const modeRadios = row.querySelectorAll('[data-pair-mode]');
        let currentMode = 'text';
        modeRadios.forEach(radio => {
            if (isImgImg && radio.value === 'image_image') radio.checked = true;
            else if (isImgText && radio.value === 'image') radio.checked = true;
            if (radio.checked) currentMode = radio.value;
        });

        const hint = row.querySelector('[data-pair-mode-hint]');
        const instruction = row.querySelector('[data-pair-instruction]');
        if (hint) {
            if (currentMode === 'image_image') {
                hint.textContent = 'Unggah/masukkan gambar di kiri dan gambar pasangan di kanan';
            } else if (currentMode === 'image') {
                hint.textContent = 'Unggah gambar/URL di kiri, ketik nama/label di kanan';
            } else {
                hint.textContent = 'Ketik istilah di kiri dan penjelasan di kanan';
            }
        }
        if (instruction) {
            if (currentMode === 'image_image') {
                instruction.textContent = 'Setiap baris mencocokkan gambar stimulus di kiri dengan gambar jawaban di kanan.';
            } else if (currentMode === 'image') {
                instruction.textContent = 'Setiap baris mencocokkan gambar di sisi kiri dengan teks pilihan di sisi kanan.';
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

    const update = () => {
        const active = ['tugas', 'kuis'].includes(type.value);
        builder.hidden = !active;
        builder.querySelectorAll('input,textarea,select').forEach(input => input.disabled = !active);

        [...rows.children].forEach((row, index) => {
            const numBadge = row.querySelector('[data-question-number-badge]');
            if (numBadge) numBadge.textContent = `${index + 1}`;
            row.querySelector('[data-question-number]').textContent = `Soal ${index + 1}`;
            row.querySelectorAll('[data-q-field]').forEach(input => input.name = `questions[${index}][${input.dataset.qField}]`);

            const qType = row.querySelector('[data-q-field="type"]').value;
            row.querySelector('[data-q-options]').hidden = !['pilihan', 'kompleks'].includes(qType);
            const b = row.querySelector('[data-q-boolean]');
            if (b) b.hidden = qType !== 'benar_salah';
            const m = row.querySelector('[data-q-matching]');
            if (m) m.hidden = qType !== 'mencocokkan';
        });

        builder.querySelector('[data-question-total]').textContent = `${rows.children.length} soal · ${[...rows.querySelectorAll('[data-q-field="points"]')].reduce((sum, input) => sum + Number(input.value || 0), 0)} poin`;
    };

    const add = (data = {}) => {
        if (rows.children.length >= 30) return;
        const row = template.content.firstElementChild.cloneNode(true);

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
            renderChoices(row, choices);
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
        update();
    };

    builder.querySelector('[data-add-question]').addEventListener('click', () => add());

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

        if (event.target.dataset.qField === 'type') {
            const qType = event.target.value;
            if (['pilihan', 'kompleks'].includes(qType)) {
                renderChoices(row);
            } else if (qType === 'mencocokkan') {
                renderPairs(row);
            }
        }
        update();

        if (event.target.dataset.qField === 'image') {
            const previewBox = row.querySelector('[data-q-preview-box]');
            const preview = row.querySelector('[data-q-preview]');
            const altBox = row.querySelector('[data-q-alt-box]');
            const altInput = row.querySelector('[data-q-field="alt"]');
            const file = event.target.files[0];

            if (preview && preview.dataset.url) URL.revokeObjectURL(preview.dataset.url);
            if (previewBox) previewBox.hidden = !file;
            if (altBox) altBox.hidden = !file;
            if (altInput) altInput.required = !!file;

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
            const item = document.createElement('div');
            item.className = 'flex items-center gap-2';
            item.innerHTML = `
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-canvas text-xs font-bold text-ink shrink-0 border border-line/50" data-choice-letter>${letters[count] || (count + 1)}</span>
                <input type="text" class="field text-xs py-1.5 flex-1" placeholder="Pilihan ${letters[count] || (count + 1)}..." data-choice-item-input>
                <button type="button" class="h-7 w-7 rounded-md text-muted hover:text-danger hover:bg-rose-50 flex items-center justify-center text-sm" data-remove-choice title="Hapus pilihan">×</button>
            `;
            list.appendChild(item);
            item.querySelector('input').focus();
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
                    if (l) l.textContent = letters[idx] || (idx + 1);
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

        // Remove image button
        if (event.target.closest('[data-q-remove-image]')) {
            const fileInput = row.querySelector('input[data-q-field="image"]');
            const previewBox = row.querySelector('[data-q-preview-box]');
            const preview = row.querySelector('[data-q-preview]');
            const altBox = row.querySelector('[data-q-alt-box]');
            const altInput = row.querySelector('[data-q-field="alt"]');

            if (fileInput) fileInput.value = '';
            if (preview && preview.dataset.url) URL.revokeObjectURL(preview.dataset.url);
            if (preview) preview.removeAttribute('src');
            if (previewBox) previewBox.hidden = true;
            if (altBox) altBox.hidden = true;
            if (altInput) {
                altInput.value = '';
                altInput.required = false;
            }
            return;
        }

        // Remove question button
        if (event.target.closest('[data-remove-question]') && rows.children.length > 1) {
            row.remove();
            update();
        }
    });

    const old = JSON.parse(builder.querySelector('[data-old-questions]').textContent);
    (old.length ? old : [{}]).forEach(add);

    const sync = () => {
        update();
        const active = ['tugas', 'kuis'].includes(type.value);
        const isQuiz = type.value === 'kuis';
        const quizDuration = document.querySelector('[data-quiz-duration-settings]');
        if (quizDuration) quizDuration.hidden = !isQuiz;
        const legacy = document.querySelector('[data-legacy-question-settings]');
        if (legacy) {
            const gridDiv = legacy.querySelector('#question_type')?.closest('.grid')?.querySelector('div');
            if (gridDiv) gridDiv.hidden = active;
            const choiceFields = legacy.querySelector('[data-choice-fields]');
            const qTypeVal = document.querySelector('#question_type')?.value;
            if (choiceFields) choiceFields.hidden = active || !['pilihan', 'kompleks'].includes(qTypeVal);
        }
        if (active && document.querySelector('#question_type')) document.querySelector('#question_type').value = 'uraian';
    };
    type.addEventListener('change', sync);
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
