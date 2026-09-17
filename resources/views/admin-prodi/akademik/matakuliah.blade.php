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
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <div class="flex items-center gap-2">
                <input type="text" id="mk-search" onkeyup="filterMks()" placeholder="Cari kode atau nama mata kuliah..." class="field text-xs font-semibold w-72">
            </div>
            <div class="text-xs text-muted">
                Total terdaftar di {{ $activeProdi?->name }}: <strong class="text-ink" id="mk-count">{{ $mataKuliahs->count() }}</strong> mata kuliah
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas/60 text-muted">
                        <th class="p-3 w-12 text-center">No</th>
                        <th class="p-3">Kode MK</th>
                        <th class="p-3">Nama Mata Kuliah</th>
                        <th class="p-3 text-center">Bobot SKS</th>
                        <th class="p-3 text-center">Kelas Terbuka</th>
                        <th class="p-3 text-center">CPMK Terdefinisi</th>
                        <th class="p-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($mataKuliahs as $index => $mk)
                    <tr class="hover:bg-canvas/30 mk-row" data-search="{{ strtolower($mk->code . ' ' . $mk->name) }}">
                        <td class="p-3 text-center text-muted">{{ $index + 1 }}</td>
                        <td class="p-3 font-bold text-brand">
                            <span class="px-2 py-0.5 rounded bg-brand-soft text-brand font-mono">{{ $mk->code }}</span>
                        </td>
                        <td class="p-3 font-semibold text-ink">
                            {{ $mk->name }}
                        </td>
                        <td class="p-3 text-center font-bold text-ink">
                            <span class="px-2 py-0.5 rounded bg-slate-100 border border-line">{{ $mk->sks }} SKS</span>
                        </td>
                        <td class="p-3 text-center">
                            <a href="{{ route('admin-prodi.akademik.kelas', ['prodi_id' => $mk->prodi_id]) }}" class="font-bold text-ink hover:text-brand">
                                {{ $mk->class_sections_count }} Kelas
                            </a>
                        </td>
                        <td class="p-3 text-center">
                            <a href="{{ route('admin-prodi.kurikulum.index', ['prodi_id' => $mk->prodi_id, 'tab' => 'cpmk']) }}" class="font-bold text-ink hover:text-brand">
                                {{ $mk->cpmks_count }} CPMK
                            </a>
                        </td>
                        <td class="p-3 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" 
                                        onclick="openEditMkModal({{ $mk->id }}, '{{ addslashes($mk->code) }}', '{{ addslashes($mk->name) }}', {{ $mk->sks }})" 
                                        class="button-secondary text-[11px] py-1 px-2.5">
                                    Ubah
                                </button>
                                <form action="{{ route('admin-prodi.akademik.matakuliah.destroy', $mk->id) }}" method="POST" onsubmit="return confirm('Hapus mata kuliah {{ $mk->name }}?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button-secondary text-[11px] py-1 px-2.5 text-danger hover:bg-danger/10">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-muted">
                            Belum ada mata kuliah untuk program studi ini. Klik "+ Tambah Mata Kuliah" di atas.
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
