@extends('layouts.mahasiswa')

@section('title', 'Manajemen Mata Kuliah | SALE')
@section('header', 'Mata Kuliah Program Studi')

@section('content')
<div class="space-y-6 w-full">
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-center sm:justify-between w-full">
        <div class="min-w-0 flex-1">
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500 mb-1">
                <a class="flex items-center gap-1.5 font-medium text-slate-500 hover:text-brand transition" href="{{ route('admin-prodi.dashboard') }}">
                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                    <span>Admin Prodi</span>
                </a>
                <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @if($activeProdi)
                    <a class="font-medium text-slate-500 hover:text-brand transition" href="{{ route('admin-prodi.akademik.matakuliah') }}">
                        Mata Kuliah
                    </a>
                    <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <span class="font-semibold text-slate-800" aria-current="page">
                        {{ $activeProdi->name }}
                    </span>
                @else
                    <span class="font-semibold text-slate-800" aria-current="page">
                        Mata Kuliah
                    </span>
                @endif
            </nav>
            <h1 class="page-heading">Mata Kuliah Program Studi</h1>
            <p class="page-description">Kelola mata kuliah kurikulum, penetapan SKS, dan pembukaan kelas perkuliahan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0 w-full sm:w-auto sm:ml-auto">
            <button type="button" onclick="openCreateMkModal()" class="button-primary text-xs whitespace-nowrap w-full sm:w-auto justify-center">
                + Tambah Mata Kuliah
            </button>
        </div>
    </header>

@if(! $activeProdi)
    @include('admin-prodi.partials.prodi-selector', [
        'hideHeader' => true,
        'menuTitle' => 'Mata Kuliah',
        'description' => 'Silakan pilih program studi terlebih dahulu untuk mengelola kurikulum dan mata kuliah.',
        'targetRoute' => 'admin-prodi.akademik.matakuliah',
        'actionLabel' => 'Kelola Mata Kuliah',
    ])
@else

    <div class="surface p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative w-64 max-w-full">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </div>
                    <input type="text" id="mk-search" onkeyup="filterMks()" placeholder="Cari kode atau nama..." class="field text-xs font-medium w-full" style="padding-left: 2.25rem !important;">
                </div>
                <select id="filter-semester-paket" onchange="switchSemesterPaket(this.value)" class="field text-xs font-semibold w-40">
                    <option value="">Semua Semester</option>
                    @for($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}" {{ isset($selectedSemesterPaket) && $selectedSemesterPaket === $i ? 'selected' : '' }}>Semester {{ $i }}</option>
                    @endfor
                </select>
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
                        <th class="px-4 py-3.5 text-center w-24 !align-middle">Semester</th>
                        <th class="px-4 py-3.5 text-center w-24 !align-middle">Bobot SKS</th>
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
                            @if($mk->semester_paket)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">Sem. {{ $mk->semester_paket }}</span>
                            @else
                                <span class="text-xs text-muted">-</span>
                            @endif
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
                                        onclick="openEditMkModal({{ $mk->id }}, '{{ addslashes($mk->code) }}', '{{ addslashes($mk->name) }}', {{ $mk->sks }}, {{ $mk->is_lintas_prodi ? 'true' : 'false' }}, {{ $mk->semester_paket ?? 'null' }}, {{ json_encode($mk->cpmks->pluck('id')) }})"
                                        class="button-secondary text-[11px] py-1 px-2.5">
                                    Ubah
                                </button>
                                <form action="{{ route('admin-prodi.akademik.matakuliah.destroy', $mk->id) }}" method="POST" data-confirm="Hapus mata kuliah {{ $mk->name }}?" data-confirm-title="Hapus mata kuliah" data-confirm-label="Hapus" class="inline">
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <p class="text-xs">Belum ada mata kuliah untuk program studi ini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
</div>

<!-- Modal Tambah MK -->
<div id="createMkModal" onclick="if(event.target === this) closeCreateMkModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl rounded-2xl border border-line">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 class="text-base font-bold text-ink">Tambah Mata Kuliah Baru</h2>
            <button type="button" onclick="closeCreateMkModal()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="{{ route('admin-prodi.akademik.matakuliah.store') }}" method="POST" class="space-y-4">
            @csrf
            @if($activeProdi)
                <input type="hidden" name="prodi_id" value="{{ $activeProdi->id }}">
            @else
                <div>
                    <label for="mk_create_prodi" class="block text-xs font-semibold text-ink mb-1">Program Studi</label>
                    <select name="prodi_id" id="mk_create_prodi" required class="field text-xs font-semibold">
                        <option value="" disabled selected>-- Pilih Program Studi --</option>
                        @foreach($prodis as $p)
                            <option value="{{ $p->id }}">{{ $p->code }} - {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="grid grid-cols-4 gap-3">
                <div class="col-span-2">
                    <label for="mk_create_code" class="block text-xs font-semibold text-ink mb-1">Kode MK (contoh: IF204)</label>
                    <input type="text" name="code" id="mk_create_code" required maxlength="20" placeholder="IF204" class="field text-xs font-semibold uppercase">
                </div>
                <div>
                    <label for="mk_create_sks" class="block text-xs font-semibold text-ink mb-1">Bobot SKS</label>
                    <input type="number" name="sks" id="mk_create_sks" required min="1" max="8" value="3" class="field text-xs font-semibold">
                </div>
                <div>
                    <label for="mk_create_sem" class="block text-xs font-semibold text-ink mb-1">Semester</label>
                    <select name="semester_paket" id="mk_create_sem" class="field text-xs">
                        <option value="">-</option>
                        @for($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}">Sem. {{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div>
                <label for="mk_create_name" class="block text-xs font-semibold text-ink mb-1">Nama Mata Kuliah</label>
                <input type="text" name="name" id="mk_create_name" required maxlength="150" placeholder="Struktur Data dan Algoritma" class="field text-xs font-semibold">
            </div>
            <label class="flex items-start gap-2 rounded-lg border border-line/70 p-3 text-xs text-ink">
                <input type="hidden" name="is_lintas_prodi" value="0">
                <input type="checkbox" name="is_lintas_prodi" value="1" class="mt-0.5 rounded border-line text-brand">
                <span><strong class="block">Mata kuliah lintas prodi</strong><span class="text-muted">Izinkan penetapan dosen dari program studi lain pada kelas mata kuliah ini.</span></span>
            </label>
            {{-- Pilihan Multiple Choice CPMK untuk Mata Kuliah --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-semibold text-ink">
                        Pilih Butir CPMK yang Diampu (Multiple Choice)
                    </label>
                    <span class="text-[11px] text-muted">Bisa memilih lebih dari satu</span>
                </div>
                @if(isset($cpmks) && $cpmks->isNotEmpty())
                    <div class="space-y-1.5 max-h-40 overflow-y-auto p-2.5 rounded-lg border border-line bg-canvas/40">
                        @foreach($cpmks as $cpmk)
                        <label class="flex items-start gap-2.5 p-2 rounded-md border border-line/70 bg-white hover:bg-canvas/50 transition cursor-pointer text-xs">
                            <input type="checkbox" name="cpmk_ids[]" value="{{ $cpmk->id }}" class="mk-create-cpmk-checkbox mt-0.5 rounded text-brand focus:ring-brand">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded font-mono font-bold text-[11px] bg-brand text-white">{{ $cpmk->code }}</span>
                                    <span class="text-[11px] text-muted font-medium">Standar: {{ (float)$cpmk->threshold }}%</span>
                                    @foreach($cpmk->cpls as $cplBadge)
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-mono bg-canvas border border-line text-ink">{{ $cplBadge->code }}</span>
                                    @endforeach
                                </div>
                                <p class="text-ink text-[11px] mt-1 leading-snug line-clamp-2">{{ $cpmk->description }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                @else
                    <div class="p-3 text-center rounded-lg border border-dashed border-line bg-canvas/30 text-xs text-muted">
                        Belum ada butir CPMK yang dibuat pada prodi ini. Buat CPMK terlebih dahulu di menu <a href="{{ route('admin-prodi.kurikulum.index', ['prodi_id' => $activeProdi?->id, 'tab' => 'cpmk']) }}" class="text-brand underline font-semibold">Kurikulum &gt; CPMK</a>.
                    </div>
                @endif
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="submit" class="button-primary text-xs">Simpan Mata Kuliah</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit MK -->
<div id="editMkModal" onclick="if(event.target === this) closeEditMkModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl rounded-2xl border border-line">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 class="text-base font-bold text-ink">Ubah Mata Kuliah</h2>
            <button type="button" onclick="closeEditMkModal()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="editMkForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-4 gap-3">
                <div class="col-span-2">
                    <label for="mk_edit_code" class="block text-xs font-semibold text-ink mb-1">Kode MK</label>
                    <input type="text" name="code" id="mk_edit_code" required maxlength="20" class="field text-xs font-semibold uppercase">
                </div>
                <div>
                    <label for="mk_edit_sks" class="block text-xs font-semibold text-ink mb-1">Bobot SKS</label>
                    <input type="number" name="sks" id="mk_edit_sks" required min="1" max="8" class="field text-xs font-semibold">
                </div>
                <div>
                    <label for="mk_edit_sem" class="block text-xs font-semibold text-ink mb-1">Semester</label>
                    <select name="semester_paket" id="mk_edit_sem" class="field text-xs">
                        <option value="">-</option>
                        @for($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}">Sem. {{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div>
                <label for="mk_edit_name" class="block text-xs font-semibold text-ink mb-1">Nama Mata Kuliah</label>
                <input type="text" name="name" id="mk_edit_name" required maxlength="150" class="field text-xs font-semibold">
            </div>
            <label class="flex items-start gap-2 rounded-lg border border-line/70 p-3 text-xs text-ink">
                <input type="hidden" name="is_lintas_prodi" value="0">
                <input type="checkbox" name="is_lintas_prodi" id="mk_edit_lintas" value="1" class="mt-0.5 rounded border-line text-brand">
                <span><strong class="block">Mata kuliah lintas prodi</strong><span class="text-muted">Izinkan penetapan dosen dari program studi lain.</span></span>
            </label>
            {{-- Pilihan Multiple Choice CPMK untuk Mata Kuliah --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-semibold text-ink">
                        Pilih Butir CPMK yang Diampu (Multiple Choice)
                    </label>
                    <span class="text-[11px] text-muted">Bisa memilih lebih dari satu</span>
                </div>
                @if(isset($cpmks) && $cpmks->isNotEmpty())
                    <div class="space-y-1.5 max-h-40 overflow-y-auto p-2.5 rounded-lg border border-line bg-canvas/40">
                        @foreach($cpmks as $cpmk)
                        <label class="flex items-start gap-2.5 p-2 rounded-md border border-line/70 bg-white hover:bg-canvas/50 transition cursor-pointer text-xs">
                            <input type="checkbox" name="cpmk_ids[]" value="{{ $cpmk->id }}" class="mk-edit-cpmk-checkbox mt-0.5 rounded text-brand focus:ring-brand">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded font-mono font-bold text-[11px] bg-brand text-white">{{ $cpmk->code }}</span>
                                    <span class="text-[11px] text-muted font-medium">Standar: {{ (float)$cpmk->threshold }}%</span>
                                    @foreach($cpmk->cpls as $cplBadge)
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-mono bg-canvas border border-line text-ink">{{ $cplBadge->code }}</span>
                                    @endforeach
                                </div>
                                <p class="text-ink text-[11px] mt-1 leading-snug line-clamp-2">{{ $cpmk->description }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                @else
                    <div class="p-3 text-center rounded-lg border border-dashed border-line bg-canvas/30 text-xs text-muted">
                        Belum ada butir CPMK yang dibuat pada prodi ini. Buat CPMK terlebih dahulu di menu <a href="{{ route('admin-prodi.kurikulum.index', ['prodi_id' => $activeProdi?->id, 'tab' => 'cpmk']) }}" class="text-brand underline font-semibold">Kurikulum &gt; CPMK</a>.
                    </div>
                @endif
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="submit" class="button-primary text-xs">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchProdi(prodiId) {
        window.location.href = `{{ route('admin-prodi.akademik.matakuliah') }}?prodi_id=${prodiId}`;
    }

    function switchSemesterPaket(sem) {
        const url = new URL(window.location.href);
        if (sem) {
            url.searchParams.set('semester_paket', sem);
        } else {
            url.searchParams.delete('semester_paket');
        }
        window.location.href = url.toString();
    }

    function openCreateMkModal() {
        document.querySelectorAll('.mk-create-cpmk-checkbox').forEach(cb => {
            cb.checked = false;
        });
        document.getElementById('createMkModal').classList.remove('hidden');
        document.getElementById('createMkModal').classList.add('flex');
    }
    function closeCreateMkModal() {
        document.getElementById('createMkModal').classList.add('hidden');
        document.getElementById('createMkModal').classList.remove('flex');
    }

    function openEditMkModal(id, code, name, sks, isLintasProdi, semesterPaket, cpmkIds = []) {
        const form = document.getElementById('editMkForm');
        form.action = `/admin-prodi/akademik/matakuliah/${id}`;
        document.getElementById('mk_edit_code').value = code;
        document.getElementById('mk_edit_name').value = name;
        document.getElementById('mk_edit_sks').value = sks;
        document.getElementById('mk_edit_sem').value = semesterPaket || '';
        document.getElementById('mk_edit_lintas').checked = Boolean(isLintasProdi);

        const assignedIds = Array.isArray(cpmkIds) ? cpmkIds.map(Number) : [];
        document.querySelectorAll('.mk-edit-cpmk-checkbox').forEach(cb => {
            cb.checked = assignedIds.includes(parseInt(cb.value));
        });

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
