<dialog id="submission-preview" class="submission-preview" aria-labelledby="submission-title" aria-describedby="submission-meta">
    <header class="submission-header">
        <span class="submission-filetype text-xs font-bold" data-preview-type aria-hidden="true">FILE</span>
        <div class="min-w-0 flex-1">
            <h2 id="submission-title" class="text-sm font-semibold text-ink" data-preview-title>Pengumpulan mahasiswa</h2>
            <p id="submission-meta" class="mt-1 text-xs text-muted" data-preview-meta></p>
        </div>
        <div class="submission-actions">
            <a class="button-secondary text-xs" data-preview-download hidden>Unduh</a>
            <a class="button-secondary text-xs" data-preview-open target="_blank" rel="noopener noreferrer" hidden>Buka Tab Baru</a>
            <button type="button" class="submission-close" data-preview-close aria-label="Tutup pratinjau" autofocus>
                <svg width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="m6 6 12 12M6 18 18 6"/></svg>
            </button>
        </div>
    </header>
    <div class="submission-picker" data-preview-picker hidden>
        <label for="submission-file" class="text-xs font-semibold">Lampiran</label>
        <select id="submission-file" class="field text-xs" data-preview-select></select>
    </div>
    <div class="submission-body" data-preview-body>
        <div class="submission-empty" data-preview-message role="status"></div>
    </div>
    <footer class="submission-footer">
        <p class="text-xs text-muted">Gunakan navigasi dokumen untuk membaca lampiran. Jika pratinjau tidak muncul, pilih Unduh atau Buka Tab Baru.</p>
        <button type="button" class="button-secondary text-xs" data-preview-close>Tutup Pratinjau</button>
    </footer>
</dialog>
