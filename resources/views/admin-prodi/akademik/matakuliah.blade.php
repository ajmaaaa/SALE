@extends('layouts.mahasiswa')

@section('title', 'Manajemen Mata Kuliah | SALE')
@section('header', 'Mata Kuliah Program Studi')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('admin-prodi.dashboard') }}" class="hover:text-brand">Admin Prodi</a>
                <span>/</span>
                <span class="text-ink font-semibold">Mata Kuliah</span>
            </nav>
            <h1 class="page-heading">Mata Kuliah Program Studi</h1>
            <p class="page-description">Kelola mata kuliah kurikulum, penetapan SKS, dan pembukaan kelas perkuliahan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <select id="select-prodi" onchange="switchProdi(this.value)" class="field text-xs font-semibold w-56">
                @foreach($prodis as $p)
                    <option value="{{ $p->id }}" {{ $activeProdi && $activeProdi->id === $p->id ? 'selected' : '' }}>
                        {{ $p->code }} - {{ $p->name }}
                    </option>
                @endforeach
            </select>
            <button type="button" onclick="openCreateMkModal()" class="button-primary text-xs">
                + Tambah Mata Kuliah
            </button>
        </div>
    </header>

    <div class="surface p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="relative w-72 max-w-full">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                    </svg>
                </div>
                <input type="text" id="mk-search" onkeyup="filterMks()" placeholder="Cari kode atau nama mata kuliah..." class="field text-xs font-medium w-full pl-9">
            </div>
            <div class="text-xs text-muted whitespace-nowrap">
                Total terdaftar di <span class="font-semibold text-ink">{{ $activeProdi?->name }}</span>: <strong class="text-ink font-bold" id="mk-count">{{ $mataKuliahs->count() }}</strong> mata kuliah
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas/60 text-muted">
                        <th class="px-4 py-3.5 text-center w-14 !align-middle">No</th>
                        <th class="px-4 py-3.5 w-32 !align-middle">Kode MK</th>
                        <th class="px-4 py-3.5 !align-middle">Nama Mata Kuliah</th>
                        <th class="px-4 py-3.5 text-center w-28 !align-middle">Bobot SKS</th>
                        <th class="px-4 py-3.5 text-center w-32 !align-middle">Kelas Terbuka</th>
                        <th class="px-4 py-3.5 text-center w-36 !align-middle">CPMK Terdefinisi</th>
                        <th class="px-4 py-3.5 text-right w-36 !align-middle">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($mataKuliahs as $index => $mk)
                    <tr class="hover:bg-canvas/30 transition-colors mk-row" data-search="{{ strtolower($mk->code . ' ' . $mk->name) }}">
                        <td class="px-4 py-3.5 text-center text-muted font-medium !align-middle">{{ $index + 1 }}</td>
                        <td class="px-4 py-3.5 font-mono font-bold text-brand !align-middle whitespace-nowrap">
                            {{ $mk->code }}
                        </td>
                        <td class="px-4 py-3.5 font-semibold text-ink !align-middle">
                            {{ $mk->name }}
                        </td>
                        <td class="px-4 py-3.5 text-center font-medium text-ink !align-middle whitespace-nowrap">
                            {{ $mk->sks }} SKS
                        </td>
                        <td class="px-4 py-3.5 text-center !align-middle whitespace-nowrap">
                            <a href="{{ route('admin-prodi.akademik.kelas', ['prodi_id' => $mk->prodi_id]) }}" class="font-semibold text-ink hover:text-brand hover:underline transition-colors">
                                {{ $mk->class_sections_count }} Kelas
                            </a>
                        </td>
                        <td class="px-4 py-3.5 text-center !align-middle whitespace-nowrap">
                            <a href="{{ route('admin-prodi.kurikulum.index', ['prodi_id' => $mk->prodi_id, 'tab' => 'cpmk']) }}" class="font-semibold text-ink hover:text-brand hover:underline transition-colors">
                                {{ $mk->cpmks_count }} CPMK
                            </a>
                        </td>
                        <td class="px-4 py-3.5 text-right !align-middle whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <button type="button" 
                                        onclick="openEditMkModal({{ $mk->id }}, '{{ addslashes($mk->code) }}', '{{ addslashes($mk->name) }}', {{ $mk->sks }})" 
                                        class="button-secondary text-[11px] py-1 px-2.5">
                                    Ubah
                                </button>
                                <form action="{{ route('admin-prodi.akademik.matakuliah.destroy', $mk->id) }}" method="POST" onsubmit="return confirm('Hapus mata kuliah {{ $mk->name }}?');" class="inline">
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
                        <td colspan="7" class="py-12 text-center text-muted !align-middle">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="h-8 w-8 text-muted/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <p class="text-xs">Belum ada mata kuliah untuk program studi ini.</p>
                                <button type="button" onclick="openCreateMkModal()" class="button-primary text-xs mt-1">
                                    + Tambah Mata Kuliah
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

<!-- Modal Tambah MK -->
<div id="createMkModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 class="text-base font-bold text-ink">Tambah Mata Kuliah Baru</h2>
            <button type="button" onclick="closeCreateMkModal()" class="text-muted hover:text-ink text-xl">&times;</button>
        </div>
        <form action="{{ route('admin-prodi.akademik.matakuliah.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="prodi_id" value="{{ $activeProdi?->id }}">
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label for="mk_create_code" class="block text-xs font-semibold text-ink mb-1">Kode MK (contoh: IF204)</label>
                    <input type="text" name="code" id="mk_create_code" required maxlength="20" placeholder="IF204" class="field text-xs font-semibold uppercase">
                </div>
                <div>
                    <label for="mk_create_sks" class="block text-xs font-semibold text-ink mb-1">Bobot SKS</label>
                    <input type="number" name="sks" id="mk_create_sks" required min="1" max="8" value="3" class="field text-xs font-semibold">
                </div>
            </div>
            <div>
                <label for="mk_create_name" class="block text-xs font-semibold text-ink mb-1">Nama Mata Kuliah</label>
                <input type="text" name="name" id="mk_create_name" required maxlength="150" placeholder="Struktur Data dan Algoritma" class="field text-xs font-semibold">
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="button" onclick="closeCreateMkModal()" class="button-secondary text-xs">Batal</button>
                <button type="submit" class="button-primary text-xs">Simpan Mata Kuliah</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit MK -->
<div id="editMkModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 class="text-base font-bold text-ink">Ubah Mata Kuliah</h2>
            <button type="button" onclick="closeEditMkModal()" class="text-muted hover:text-ink text-xl">&times;</button>
        </div>
        <form id="editMkForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label for="mk_edit_code" class="block text-xs font-semibold text-ink mb-1">Kode MK</label>
                    <input type="text" name="code" id="mk_edit_code" required maxlength="20" class="field text-xs font-semibold uppercase">
                </div>
                <div>
                    <label for="mk_edit_sks" class="block text-xs font-semibold text-ink mb-1">Bobot SKS</label>
                    <input type="number" name="sks" id="mk_edit_sks" required min="1" max="8" class="field text-xs font-semibold">
                </div>
            </div>
            <div>
                <label for="mk_edit_name" class="block text-xs font-semibold text-ink mb-1">Nama Mata Kuliah</label>
                <input type="text" name="name" id="mk_edit_name" required maxlength="150" class="field text-xs font-semibold">
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="button" onclick="closeEditMkModal()" class="button-secondary text-xs">Batal</button>
                <button type="submit" class="button-primary text-xs">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchProdi(prodiId) {
        window.location.href = `{{ route('admin-prodi.akademik.matakuliah') }}?prodi_id=${prodiId}`;
    }

    function openCreateMkModal() {
        document.getElementById('createMkModal').classList.remove('hidden');
        document.getElementById('createMkModal').classList.add('flex');
    }
    function closeCreateMkModal() {
        document.getElementById('createMkModal').classList.add('hidden');
        document.getElementById('createMkModal').classList.remove('flex');
    }

    function openEditMkModal(id, code, name, sks) {
        const form = document.getElementById('editMkForm');
        form.action = `/admin-prodi/akademik/matakuliah/${id}`;
        document.getElementById('mk_edit_code').value = code;
        document.getElementById('mk_edit_name').value = name;
        document.getElementById('mk_edit_sks').value = sks;
        document.getElementById('editMkModal').classList.remove('hidden');
        document.getElementById('editMkModal').classList.add('flex');
    }
    function closeEditMkModal() {
        document.getElementById('editMkModal').classList.add('hidden');
        document.getElementById('editMkModal').classList.remove('flex');
    }

    function filterMks() {
        const query = document.getElementById('mk-search').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.mk-row');
        let count = 0;
        rows.forEach(r => {
            const match = !query || (r.dataset.search && r.dataset.search.includes(query));
            if (match) {
                r.style.display = '';
                count++;
            } else {
                r.style.display = 'none';
            }
        });
        const label = document.getElementById('mk-count');
        if (label) label.textContent = count;
    }
</script>
@endsection
