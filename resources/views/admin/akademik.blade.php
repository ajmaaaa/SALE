@extends('layouts.mahasiswa')
@section('header','Data akademik')
@section('content')
<div class="space-y-6">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-heading">Data akademik</h1>
            <p class="page-description">Susun fakultas, program studi, dan semester.</p>
        </div>
        <a class="button-primary" href="{{ route('admin.page','akademik') }}?create=1">+ Tambah data</a>
    </header>

    @if(request('create') || $record || $errors->any())
    <form method="post" action="{{ route('admin.academic.store') }}" class="surface space-y-5 p-6">
        @csrf
        @if($record)
            <input type="hidden" name="id" value="{{ $record['id'] }}">
        @endif
        <h2 class="section-heading">{{ $record ? 'Edit data akademik' : 'Data akademik baru' }}</h2>
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="form-label" for="type">Jenis data</label>
                <select name="type" id="type" class="field" data-academic-type>
                    @foreach(['fakultas'=>'Fakultas','prodi'=>'Program studi','semester'=>'Semester'] as $key=>$label)
                        <option value="{{ $key }}" @selected(old('type',$record['type'] ?? '')===$key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="code">Kode unik</label>
                <input class="field" name="code" id="code" required value="{{ old('code',$record['code'] ?? '') }}">
            </div>
            <div>
                <label class="form-label" for="name">Nama</label>
                <input class="field" name="name" id="name" required value="{{ old('name',$record['name'] ?? '') }}">
            </div>
            <div>
                <label class="form-label" for="status">Status</label>
                <select class="field" id="status" name="status">
                    @foreach(['aktif'=>'Aktif','nonaktif'=>'Nonaktif'] as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}" @selected(old('status',$record['status'] ?? '')===$statusKey)>{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div data-academic-parent>
                <label class="form-label" for="parent">Induk fakultas</label>
                <select class="field" name="parent" id="parent">
                    <option value="">Pilih induk</option>
                    @foreach($academic as $entry)
                        @if($entry['type'] === 'fakultas' && $entry['id'] !== ($record['id'] ?? null))
                            <option data-parent-type="{{ $entry['type'] }}" value="{{ $entry['id'] }}" @selected(old('parent',$record['parent'] ?? '')==$entry['id'])>{{ $entry['name'] }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <div class="flex gap-3">
                <button class="button-primary">Simpan data</button>
                <a class="button-secondary" href="{{ route('admin.page','akademik') }}">Batal</a>
            </div>
            @if($record)
                <button type="submit" name="action" value="delete" onclick="return confirm('Apakah Anda yakin ingin menghapus data akademik {{ $record['name'] }}?');" class="button-secondary text-danger hover:bg-rose-50 border-danger/40">
                    Hapus data
                </button>
            @endif
        </div>
    </form>
    @endif

    <form class="flex flex-wrap gap-3">
        <label class="sr-only" for="q">Cari data</label>
        <input class="field sm:w-72" name="q" id="q" value="{{ request('q') }}" placeholder="Cari kode atau nama">
        <label class="sr-only" for="type-filter">Jenis data</label>
        <select class="field sm:w-48" name="type" id="type-filter">
            <option value="">Semua jenis</option>
            @foreach(['fakultas'=>'Fakultas','prodi'=>'Program studi','semester'=>'Semester'] as $typeKey => $typeLabel)
                <option value="{{ $typeKey }}" @selected(request('type')===$typeKey)>{{ $typeLabel }}</option>
            @endforeach
        </select>
        <button class="button-secondary">Cari</button>
    </form>

    <div class="surface overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Kode &amp; nama</th>
                    <th>Jenis</th>
                    <th>Induk</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($visibleAcademic as $entry)
                <tr>
                    <td>
                        <p class="font-semibold text-ink">{{ $entry['name'] }}</p>
                        <p class="mt-1 text-xs text-muted">{{ $entry['code'] }}</p>
                    </td>
                    <td>{{ ['fakultas' => 'Fakultas', 'prodi' => 'Program Studi', 'semester' => 'Semester'][$entry['type']] ?? ucfirst($entry['type']) }}</td>
                    <td>
                        @if(!empty($entry['parent']) && isset($academic[$entry['parent']]))
                            {{ $academic[$entry['parent']]['name'] }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="status {{ $entry['status'] === 'aktif' ? 'status-active' : 'status-inactive' }}">
                            {{ ucfirst($entry['status']) }}
                        </span>
                    </td>
                    <td class="text-right whitespace-nowrap">
                        <div class="inline-flex items-center justify-end gap-1.5">
                            <a class="button-secondary text-xs py-1 px-2.5" href="{{ route('admin.page','akademik') }}?edit={{ $entry['id'] }}">Edit</a>
                            <form method="POST" action="{{ route('admin.academic.destroy', $entry['id']) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus {{ $entry['name'] }}?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="button-secondary text-xs py-1 px-2.5 text-danger hover:bg-danger/10 border-danger/30">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-6 text-center text-muted">Data tidak ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
