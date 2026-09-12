const dialog = document.querySelector('#submission-preview');

if (dialog) {
    const title = dialog.querySelector('[data-preview-title]');
    const meta = dialog.querySelector('[data-preview-meta]');
    const badge = dialog.querySelector('[data-preview-type]');
    const body = dialog.querySelector('[data-preview-body]');
    const message = dialog.querySelector('[data-preview-message]');
    const picker = dialog.querySelector('[data-preview-picker]');
    const select = dialog.querySelector('[data-preview-select]');
    const download = dialog.querySelector('[data-preview-download]');
    const open = dialog.querySelector('[data-preview-open]');
    let files = [];
    let student = '';
    let trigger;
    let previousOverflow = '';

    const showFile = () => {
        body.querySelectorAll('iframe, img').forEach(element => element.remove());
        const file = files[Number(select.value) || 0];
        title.textContent = file ? `${student} - ${file.name}` : student;
        meta.textContent = file ? `Diunggah: ${file.time} | Ukuran: ${file.size} | ${file.assessment}` : 'Lampiran pengumpulan mahasiswa';
        badge.textContent = file ? file.name.split('.').pop().slice(0, 6).toUpperCase() : 'FILE';
        download.hidden = open.hidden = !file;
        download.removeAttribute('href');
        open.removeAttribute('href');
        message.hidden = false;
        message.textContent = 'Belum ada lampiran yang terhubung dengan mahasiswa dan penilaian ini.';
        if (!file) return;

        download.href = file.download;
        open.href = file.url;
        if (file.mime === 'application/pdf') {
            const frame = document.createElement('iframe');
            frame.title = `Pratinjau ${file.name}`;
            frame.src = file.url;
            body.append(frame);
            message.hidden = true;
        } else if (['image/jpeg', 'image/png', 'image/webp'].includes(file.mime)) {
            const image = document.createElement('img');
            image.alt = file.name;
            image.src = file.url;
            image.addEventListener('error', () => {
                image.remove();
                message.textContent = 'Gambar tidak dapat dimuat. Coba unduh berkas atau buka di tab baru.';
                message.hidden = false;
            });
            body.append(image);
            message.hidden = true;
        } else {
            message.textContent = 'Pratinjau format ini belum didukung browser. Pilih Unduh untuk membuka berkas dengan aplikasi yang sesuai.';
        }
    };

    document.querySelectorAll('[data-submission-open]').forEach(button => {
        button.addEventListener('click', () => {
            trigger = button;
            student = `${button.dataset.studentName} (${button.dataset.studentNumber})`;
            files = JSON.parse(button.dataset.files || '[]');
            select.replaceChildren();
            files.forEach((file, index) => select.add(new Option(`${file.assessment} - ${file.name}`, String(index))));
            picker.hidden = files.length < 2;
            showFile();
            previousOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            dialog.showModal();
        });
    });
    select.addEventListener('change', showFile);
    dialog.querySelectorAll('[data-preview-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', event => {
        const rect = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
    });
    dialog.addEventListener('close', () => {
        body.querySelectorAll('iframe, img').forEach(element => element.remove());
        document.body.style.overflow = previousOverflow;
        trigger?.focus();
    });
}
