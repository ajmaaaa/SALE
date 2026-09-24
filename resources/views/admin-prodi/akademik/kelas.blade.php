@extends('layouts.mahasiswa')

@section('title', 'Manajemen Kelas Perkuliahan | SALE')
@section('header', 'Kelas Perkuliahan & Penugasan Dosen')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('admin-prodi.dashboard') }}" class="hover:text-brand">Admin Prodi</a>
                <span>/</span>
                <span class="text-ink font-semibold">Kelas Perkuliahan</span>
            </nav>
            <h1 class="page-heading">Kelas Perkuliahan &amp; Penugasan Dosen</h1>
            <p class="page-description">Bentuk kelas mata kuliah, tetapkan Dosen Ketua &amp; Dosen Wakil, serta bagikan Link / Barcode QR Code untuk pendaftaran mahasiswa.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="openCreateKelasModal()" class="button-primary text-xs">
                + Buka Kelas Baru
            </button>
        </div>
    </header>

    <!-- Filter Bar -->
    <div class="surface p-4 flex flex-wrap items-center justify-between gap-4">
        <form id="filterForm" method="GET" class="flex flex-wrap items-center gap-3">
            <label for="filter_prodi" class="text-xs font-semibold text-muted">Prodi:</label>
            <select name="prodi_id" id="filter_prodi" onchange="this.form.submit()" class="field text-xs font-semibold w-52">
                @foreach($prodis as $p)
                    <option value="{{ $p->id }}" {{ $activeProdi && $activeProdi->id === $p->id ? 'selected' : '' }}>
                        {{ $p->code }} - {{ $p->name }}
                    </option>
                @endforeach
            </select>

            <label for="filter_semester" class="text-xs font-semibold text-muted">Semester:</label>
            <select name="semester_id" id="filter_semester" onchange="this.form.submit()" class="field text-xs font-semibold w-48">
                @foreach($semesters as $sem)
                    <option value="{{ $sem->id }}" {{ $selectedSemesterId == $sem->id ? 'selected' : '' }}>
                        {{ $sem->name }} {{ $sem->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </form>

        <div class="text-xs text-muted">
            Menampilkan <strong class="text-ink">{{ $classes->count() }}</strong> seksi kelas
        </div>
    </div>

    <!-- Table Kelas -->
    <div class="surface p-5">
        <div class="overflow-x-auto">
            <table class="admin-table w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas/60 text-muted">
                        <th class="px-4 py-3.5 text-center w-12 !align-middle">No</th>
                        <th class="px-4 py-3.5 w-32 !align-middle">Kode Kelas</th>
                        <th class="px-4 py-3.5 !align-middle">Mata Kuliah &amp; SKS</th>
                        <th class="px-4 py-3.5 w-52 !align-middle">Dosen Ketua (Koordinator)</th>
                        <th class="px-4 py-3.5 w-44 !align-middle">Dosen Wakil (Pendamping)</th>
                        <th class="px-4 py-3.5 text-center w-40 !align-middle">Kode Masuk &amp; Barcode</th>
                        <th class="px-4 py-3.5 text-center w-28 !align-middle">Kapasitas</th>
                        <th class="px-4 py-3.5 text-right w-36 !align-middle">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($classes as $index => $cls)
                    <tr class="hover:bg-canvas/30 transition-colors">
                        <td class="px-4 py-3.5 text-center text-muted font-medium !align-middle">{{ $index + 1 }}</td>
                        <td class="px-4 py-3.5 !align-middle whitespace-nowrap">
                            <span class="font-mono font-bold text-brand block">{{ $cls->display_code }}</span>
                            <span class="text-[11px] text-muted block mt-0.5">Seksi {{ $cls->section_code }}</span>
                        </td>
                        <td class="px-4 py-3.5 !align-middle">
                            <span class="font-semibold text-ink block">{{ $cls->mataKuliah->name }}</span>
                            <span class="text-[11px] text-muted block mt-0.5">{{ $cls->mataKuliah->sks }} SKS, {{ $cls->semester->name }}</span>
                        </td>
                        <td class="px-4 py-3.5 !align-middle">
                            <span class="font-semibold text-ink block">{{ $cls->dosen?->name ?? 'Belum ditentukan' }}</span>
                        </td>
                        <td class="px-4 py-3.5 !align-middle">
                            @if($cls->dosenPendamping)
                                <span class="font-medium text-ink block">{{ $cls->dosenPendamping->name }}</span>
                            @else
                                <span class="text-muted italic text-[11px]">— Tanpa Wakil —</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center !align-middle whitespace-nowrap">
                            <div class="inline-flex flex-col items-center gap-1">
                                <span class="font-mono font-bold text-ink tracking-wider text-xs">
                                    {{ $cls->enrollment_code }}
                                </span>
                                <button type="button" 
                                        onclick="showBarcodeModal('{{ $cls->display_code }}', '{{ addslashes($cls->mataKuliah->name) }}', '{{ $cls->enrollment_code }}', '{{ $cls->enrollment_url }}', '{{ route('kelas.qr', $cls->id) }}', '{{ route('kelas.barcode', $cls->id) }}')"
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-brand hover:underline transition-colors">
                                    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h7v7h-7z"/></svg>
                                    <span>Tampilkan Barcode</span>
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-center !align-middle whitespace-nowrap">
                            <span class="font-bold text-ink text-xs">{{ $cls->students_count }}</span>
                            <span class="text-muted text-[11px]">/ {{ $cls->capacity ?? '∞' }} mhs</span>
                        </td>
                        <td class="px-4 py-3.5 text-right !align-middle whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <button type="button" 
                                        onclick="openEditKelasModal({{ $cls->id }}, '{{ $cls->section_code }}', {{ $cls->capacity ?? 'null' }}, {{ $cls->dosen_id ?? 'null' }}, {{ $cls->dosen_pendamping_id ?? 'null' }})"
                                        class="button-secondary text-[11px] py-1 px-2.5">
                                    Ubah
                                </button>
                                <form action="{{ route('admin-prodi.akademik.kelas.destroy', $cls->id) }}" method="POST" onsubmit="return confirm('Hapus kelas {{ $cls->display_code }}?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button-secondary text-[11px] py-1 px-2.5 text-danger hover:bg-danger/10 hover:border-danger/30">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-muted !align-middle">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="h-8 w-8 text-muted/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <p class="text-xs">Belum ada kelas yang dibuka untuk program studi dan semester ini.</p>
                                <button type="button" onclick="openCreateKelasModal()" class="button-primary text-xs mt-1">
                                    + Buka Kelas Baru
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Buka Kelas Baru -->
<div id="createKelasModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="surface w-full max-w-lg p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 class="text-base font-bold text-ink">Buka Kelas Perkuliahan Baru</h2>
            <button type="button" onclick="closeCreateKelasModal()" class="text-muted hover:text-ink text-xl">&times;</button>
        </div>
        <form action="{{ route('admin-prodi.akademik.kelas.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="create_mk_id" class="block text-xs font-semibold text-ink mb-1">Mata Kuliah</label>
                <select name="mata_kuliah_id" id="create_mk_id" required class="field text-xs font-semibold">
                    @foreach($mataKuliahs as $mk)
                        <option value="{{ $mk->id }}">{{ $mk->code }} - {{ $mk->name }} ({{ $mk->sks }} SKS)</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="create_semester_id" class="block text-xs font-semibold text-ink mb-1">Semester</label>
                    <select name="semester_id" id="create_semester_id" required class="field text-xs font-semibold">
                        @foreach($semesters as $sem)
                            <option value="{{ $sem->id }}" {{ $selectedSemesterId == $sem->id ? 'selected' : '' }}>
                                {{ $sem->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="create_section_code" class="block text-xs font-semibold text-ink mb-1">Kode Seksi / Kelas (A, B, C)</label>
                    <input type="text" name="section_code" id="create_section_code" required maxlength="10" placeholder="A" class="field text-xs font-semibold uppercase">
                </div>
            </div>

            <div>
                <label for="create_capacity" class="block text-xs font-semibold text-ink mb-1">Kuota Mahasiswa (Kapasitas)</label>
                <input type="number" name="capacity" id="create_capacity" min="1" max="200" value="40" class="field text-xs font-semibold">
            </div>

            <!-- Dosen Ketua & Wakil -->
            <div class="rounded-xl border border-line bg-canvas/40 p-3.5 space-y-3">
                <div>
                    <label for="create_dosen_id" class="block text-xs font-bold text-ink mb-1">
                        Dosen Ketua (Koordinator Mata Kuliah) <span class="text-muted font-normal">(Opsional / Masuk via Kode)</span>
                    </label>
                    <select name="dosen_id" id="create_dosen_id" class="field text-xs font-semibold">
                        <option value="">-- Belum Ditentukan (Dosen Bergabung via Kode / Tautan) --</option>
                        @foreach($dosens as $dsn)
                            <option value="{{ $dsn->id }}">{{ $dsn->name }} ({{ $dsn->nim_nidn ?? 'NIDN' }})</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-muted mt-0.5">Dosen penanggung jawab utama. Jika dikosongkan, dosen dapat bergabung secara mandiri menggunakan kode kelas yang dibagikan.</p>
                </div>

                <div>
                    <label for="create_dosen_wakil" class="block text-xs font-bold text-ink mb-1">
                        Dosen Wakil (Pendamping / Team-Teaching) <span class="text-muted font-normal">(Opsional)</span>
                    </label>
                    <select name="dosen_pendamping_id" id="create_dosen_wakil" class="field text-xs font-semibold">
                        <option value="">-- Tanpa Dosen Wakil --</option>
                        @foreach($dosens as $dsn)
                            <option value="{{ $dsn->id }}">{{ $dsn->name }} ({{ $dsn->nim_nidn ?? 'NIDN' }})</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-muted mt-0.5">Dosen pendamping asistensi atau mitra team-teaching yang turut menilai mahasiswa.</p>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="button" onclick="closeCreateKelasModal()" class="button-secondary text-xs">Batal</button>
                <button type="submit" class="button-primary text-xs">Buat Kelas &amp; Generate Barcode</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ubah Kelas -->
<div id="editKelasModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="surface w-full max-w-lg p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 class="text-base font-bold text-ink">Ubah Data Kelas</h2>
            <button type="button" onclick="closeEditKelasModal()" class="text-muted hover:text-ink text-xl">&times;</button>
        </div>
        <form id="editKelasForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="edit_section_code" class="block text-xs font-semibold text-ink mb-1">Kode Seksi</label>
                    <input type="text" name="section_code" id="edit_section_code" required maxlength="10" class="field text-xs font-semibold uppercase">
                </div>
                <div>
                    <label for="edit_capacity" class="block text-xs font-semibold text-ink mb-1">Kuota Mahasiswa</label>
                    <input type="number" name="capacity" id="edit_capacity" min="1" max="200" class="field text-xs font-semibold">
                </div>
            </div>

            <div class="rounded-xl border border-line bg-canvas/40 p-3.5 space-y-3">
                <div>
                    <label for="edit_dosen_id" class="block text-xs font-bold text-ink mb-1">Dosen Ketua (Koordinator) <span class="text-muted font-normal">(Opsional)</span></label>
                    <select name="dosen_id" id="edit_dosen_id" class="field text-xs font-semibold">
                        <option value="">-- Belum Ditentukan (Dosen Bergabung via Kode) --</option>
                        @foreach($dosens as $dsn)
                            <option value="{{ $dsn->id }}">{{ $dsn->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="edit_dosen_wakil" class="block text-xs font-bold text-ink mb-1">Dosen Wakil (Pendamping)</label>
                    <select name="dosen_pendamping_id" id="edit_dosen_wakil" class="field text-xs font-semibold">
                        <option value="">-- Tanpa Dosen Wakil --</option>
                        @foreach($dosens as $dsn)
                            <option value="{{ $dsn->id }}">{{ $dsn->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="button" onclick="closeEditKelasModal()" class="button-secondary text-xs">Batal</button>
                <button type="submit" class="button-primary text-xs">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tampilkan Barcode & QR Code Kelas -->
<div id="barcodeModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl text-center space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <h2 class="text-base font-bold text-ink">Barcode &amp; Link Join Kelas</h2>
            <button type="button" onclick="closeBarcodeModal()" class="text-muted hover:text-ink text-xl">&times;</button>
        </div>

        <div>
            <span id="barcode_class_code" class="text-sm font-bold text-brand font-mono"></span>
            <h3 id="barcode_mk_name" class="font-bold text-base text-ink mt-1"></h3>
            <p class="text-xs text-muted">Mahasiswa dapat bergabung ke kelas ini dengan memindai barcode QR Code atau membuka link langsung di bawah ini.</p>
        </div>

        <!-- Tampilan QR Code SVG -->
        <div class="flex justify-center p-3 bg-white border border-line rounded-xl shadow-xs">
            <img id="qr_image" src="" alt="QR Code Join Kelas" class="h-48 w-48 object-contain">
        </div>

        <!-- Tampilan Barcode 1D Code128 -->
        <div class="flex justify-center p-2 bg-white border border-line rounded-lg">
            <img id="barcode_image" src="" alt="Barcode 1D Kelas" class="h-16 w-auto object-contain">
        </div>

        <!-- Kode Unik & Copy URL -->
        <div class="space-y-2 text-left">
            <div>
                <label class="block text-[11px] font-semibold text-muted uppercase tracking-wider mb-1">Kode Kelas:</label>
                <div class="flex items-center gap-2">
                    <input type="text" id="modal_enroll_code" readonly class="field font-mono font-bold text-xs bg-canvas text-center tracking-widest">
                    <button type="button" onclick="copyModalCode()" class="button-secondary text-xs shrink-0 py-2 px-3">Salin Kode</button>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-muted uppercase tracking-wider mb-1">Tautan Pendaftaran (Link):</label>
                <div class="flex items-center gap-2">
                    <input type="text" id="modal_enroll_url" readonly class="field font-mono text-[11px] bg-canvas">
                    <button type="button" onclick="copyModalUrl()" class="button-primary text-xs shrink-0 py-2 px-3" id="btnCopyUrl">Salin Link</button>
                </div>
            </div>
        </div>

        <div class="pt-3 border-t border-line flex justify-end gap-2">
            <button type="button" onclick="window.print()" class="button-secondary text-xs">Cetak Halaman</button>
            <button type="button" onclick="closeBarcodeModal()" class="button-primary text-xs">Tutup</button>
        </div>
    </div>
</div>

<script>
    function openCreateKelasModal() {
        document.getElementById('createKelasModal').classList.remove('hidden');
        document.getElementById('createKelasModal').classList.add('flex');
    }
    function closeCreateKelasModal() {
        document.getElementById('createKelasModal').classList.add('hidden');
        document.getElementById('createKelasModal').classList.remove('flex');
    }

    function openEditKelasModal(id, sectionCode, capacity, dosenId, dosenWakilId) {
        const form = document.getElementById('editKelasForm');
        form.action = `/admin-prodi/akademik/kelas/${id}`;
        document.getElementById('edit_section_code').value = sectionCode;
        document.getElementById('edit_capacity').value = capacity || '';
        document.getElementById('edit_dosen_id').value = dosenId || '';
        document.getElementById('edit_dosen_wakil').value = dosenWakilId || '';
        document.getElementById('editKelasModal').classList.remove('hidden');
        document.getElementById('editKelasModal').classList.add('flex');
    }
    function closeEditKelasModal() {
        document.getElementById('editKelasModal').classList.add('hidden');
        document.getElementById('editKelasModal').classList.remove('flex');
    }

    function showBarcodeModal(classCode, mkName, code, url, qrSrc, barcodeSrc) {
        document.getElementById('barcode_class_code').textContent = classCode;
        document.getElementById('barcode_mk_name').textContent = mkName;
        document.getElementById('modal_enroll_code').value = code;
        document.getElementById('modal_enroll_url').value = url;
        document.getElementById('qr_image').src = qrSrc;
        document.getElementById('barcode_image').src = barcodeSrc;

        document.getElementById('barcodeModal').classList.remove('hidden');
        document.getElementById('barcodeModal').classList.add('flex');
    }
    function closeBarcodeModal() {
        document.getElementById('barcodeModal').classList.add('hidden');
        document.getElementById('barcodeModal').classList.remove('flex');
    }

    function copyModalCode() {
        const input = document.getElementById('modal_enroll_code');
        input.select();
        navigator.clipboard.writeText(input.value);
        alert(`Kode kelas ${input.value} berhasil disalin ke clipboard!`);
    }

    function copyModalUrl() {
        const input = document.getElementById('modal_enroll_url');
        input.select();
        navigator.clipboard.writeText(input.value);
        const btn = document.getElementById('btnCopyUrl');
        btn.textContent = 'Tersalin!';
        setTimeout(() => { btn.textContent = 'Salin Link'; }, 2000);
    }
</script>
@endsection
