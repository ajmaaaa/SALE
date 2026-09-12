<p class="text-xs text-brand" data-grade-import-notice role="status" hidden></p>
<dialog id="grade-import" class="grade-import" aria-labelledby="grade-import-title" data-assessment="{{ $activeType }}">
    <header class="flex items-start justify-between gap-4 border-b border-line p-5">
        <div>
            <h2 id="grade-import-title" class="text-lg font-semibold">Unggah Nilai {{ strtoupper($activeType) }}</h2>
            <p class="mt-1 text-xs text-muted">Isi nilai massal dari file CSV atau Excel (.xlsx).</p>
        </div>
        <button type="button" class="button-secondary text-xs" data-grade-import-close aria-label="Tutup impor nilai">Tutup</button>
    </header>
    <div class="space-y-4 p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <p class="max-w-md text-xs leading-relaxed text-muted">Kolom wajib: <strong class="text-ink">NIM, CPMK_01, CPMK_02</strong>. Nilai 0 sampai 100, kelipatan 0,5. Simpan NIM sebagai teks agar angka nol di depan tidak hilang. Excel dibaca dari lembar pertama.</p>
            <button type="button" class="button-secondary text-xs" data-grade-import-template>Unduh Template CSV</button>
        </div>
        <div>
            <label for="grade-import-file" class="mb-2 block text-xs font-semibold">File nilai (maksimal 2 MB, 500 baris)</label>
            <input id="grade-import-file" type="file" accept=".csv,.xlsx" class="field text-xs" data-grade-import-file aria-describedby="grade-import-status">
        </div>
        <p id="grade-import-status" class="text-xs text-muted" role="status" data-grade-import-status>Pilih file untuk memeriksa nilai sebelum diterapkan.</p>
        <ul class="list-disc space-y-1 pl-5 text-xs text-danger" data-grade-import-errors role="alert" hidden></ul>
        <div class="max-h-72 overflow-auto rounded-lg border border-line" data-grade-import-preview hidden>
            <table class="admin-table">
                <thead><tr><th scope="col">NIM</th><th scope="col">CPMK-01</th><th scope="col">CPMK-02</th><th scope="col">Nilai Akhir</th></tr></thead>
                <tbody data-grade-import-rows></tbody>
            </table>
        </div>
        <p class="text-xs leading-relaxed text-muted">Hanya mahasiswa dalam file yang diperbarui. Nilai akhir dihitung dari rata-rata CPMK sesuai tabel. Perubahan hanya berlaku pada halaman preview ini dan hilang saat halaman dimuat ulang; belum tersimpan ke database.</p>
    </div>
    <footer class="flex justify-end gap-3 border-t border-line p-5">
        <button type="button" class="button-secondary text-xs" data-grade-import-close>Batal</button>
        <button type="button" class="button-primary text-xs" data-grade-import-apply disabled>Terapkan Nilai ke Tabel</button>
    </footer>
</dialog>
