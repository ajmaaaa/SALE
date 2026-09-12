const dialog = document.querySelector('#grade-import');

if (dialog) {
    const fileInput = dialog.querySelector('[data-grade-import-file]');
    const status = dialog.querySelector('[data-grade-import-status]');
    const errors = dialog.querySelector('[data-grade-import-errors]');
    const preview = dialog.querySelector('[data-grade-import-preview]');
    const rows = dialog.querySelector('[data-grade-import-rows]');
    const apply = dialog.querySelector('[data-grade-import-apply]');
    const students = new Map([...document.querySelectorAll('.student-row')].map(row => [row.dataset.number, row]));
    let pending = [];
    let request = 0;
    let trigger;
    let previousOverflow = '';

    document.querySelector('[data-grade-import-open]')?.addEventListener('click', event => {
        trigger = event.currentTarget;
        fileInput.value = '';
        pending = [];
        apply.disabled = true;
        errors.hidden = preview.hidden = true;
        status.textContent = 'Pilih file untuk memeriksa nilai sebelum diterapkan.';
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        dialog.showModal();
    });
    dialog.querySelectorAll('[data-grade-import-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('close', () => {
        request++;
        pending = [];
        apply.disabled = true;
        document.body.style.overflow = previousOverflow;
        trigger?.focus();
    });
    fileInput.addEventListener('change', async () => {
        const current = ++request;
        pending = [];
        apply.disabled = true;
        errors.hidden = preview.hidden = true;
        errors.replaceChildren();
        rows.replaceChildren();
        const file = fileInput.files[0];
        status.textContent = file ? 'Membaca dan memvalidasi file...' : 'Pilih file nilai.';
        if (!file) return;
        try {
            if (file.size > 2 * 1024 * 1024) throw new Error('Ukuran file melebihi 2 MB.');
            const extension = file.name.split('.').pop().toLowerCase();
            if (!['csv', 'xlsx'].includes(extension)) throw new Error('Gunakan CSV atau Excel .xlsx. Simpan ulang file .xls menjadi .xlsx.');
            const { parseGradeCsv, validateGradeRows } = await import('./grade-import-data');
            let matrix;
            if (extension === 'csv') {
                matrix = parseGradeCsv(await file.text());
            } else {
                const { readSheet } = await import('read-excel-file/browser');
                matrix = await readSheet(file, { sheet: 1 });
            }
            if (current !== request) return;
            const result = validateGradeRows(matrix, new Set(students.keys()));
            if (result.errors.length) {
                errors.hidden = false;
                result.errors.slice(0, 20).forEach(text => {
                    const li = document.createElement('li');
                    li.textContent = text;
                    errors.append(li);
                });
                status.textContent = `${result.errors.length} masalah ditemukan. Perbaiki file dan unggah ulang. Tidak ada nilai yang diubah.`;
                return;
            }
            pending = result.grades;
            pending.forEach(grade => {
                const tr = document.createElement('tr');
                [grade.nim, grade.cpmk1, grade.cpmk2, grade.score].forEach(value => {
                    const td = document.createElement('td');
                    td.textContent = value;
                    tr.append(td);
                });
                rows.append(tr);
            });
            preview.hidden = false;
            apply.disabled = false;
            status.textContent = `${pending.length} mahasiswa siap diperbarui dari ${file.name}. Periksa pratinjau sebelum menerapkan.`;
        } catch (error) {
            if (current !== request) return;
            status.textContent = `Impor gagal: ${error.message || 'File tidak dapat dibaca.'} Tidak ada nilai yang diubah.`;
        }
    });
    apply.addEventListener('click', () => {
        if (!pending.length) return;
        const count = pending.length;
        pending.forEach(grade => {
            const row = students.get(grade.nim);
            const inputs = [...row.querySelectorAll('input[data-cpmk]')];
            inputs.forEach(input => { input.value = grade[input.dataset.cpmk]; });
            window.recalcScore(inputs[0]);
            row.dataset.status = Number(inputs[0].value) < Number(inputs[0].dataset.threshold)
                ? 'belum' : Number(inputs[1].value) < Number(inputs[1].dataset.threshold) ? 'evaluasi' : 'memenuhi';
        });
        window.filterStudents();
        const notice = document.querySelector('[data-grade-import-notice]');
        notice.textContent = `${count} nilai mahasiswa diterapkan dari file. Perubahan hanya pada tabel preview, belum tersimpan ke database.`;
        notice.hidden = false;
        dialog.close();
    });
    dialog.querySelector('[data-grade-import-template]').addEventListener('click', () => {
        const content = ['NIM,CPMK_01,CPMK_02', ...[...students.keys()].map(nim => `${nim},,`)].join('\r\n');
        const url = URL.createObjectURL(new Blob(['\uFEFF' + content], { type: 'text/csv;charset=utf-8' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = `template-nilai-${dialog.dataset.assessment}.csv`;
        link.click();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    });
}
