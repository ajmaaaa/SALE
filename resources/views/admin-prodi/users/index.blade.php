@extends('layouts.mahasiswa')

@section('title', 'Data Dosen & Mahasiswa | SALE')
@section('header', 'Data Dosen & Mahasiswa Program Studi')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('admin-prodi.dashboard') }}" class="hover:text-brand">Admin Prodi</a>
                <span>/</span>
                <span class="text-ink font-semibold">Pengguna</span>
            </nav>
            <h1 class="page-heading">Data Dosen &amp; Mahasiswa</h1>
            <p class="page-description">Input data dosen dan mahasiswa secara manual atau impor massal melalui file template Excel/CSV.</p>
        </div>
        <div class="flex items-center gap-2">
            <label for="select_prodi" class="text-xs font-semibold text-muted">Program Studi:</label>
            <select id="select_prodi" onchange="switchProdi(this.value)" class="field text-xs font-semibold w-56">
                @foreach($prodis as $p)
                    <option value="{{ $p->id }}" {{ $activeProdi && $activeProdi->id === $p->id ? 'selected' : '' }}>
                        {{ $p->code }} - {{ $p->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </header>

    @if(session('import_errors') && count(session('import_errors')) > 0)
    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-xs text-amber-900">
        <p class="font-bold mb-1">Pemberitahuan Baris Impor yang Diabaikan / Duplikat:</p>
        <ul class="list-disc list-inside space-y-0.5 max-h-32 overflow-y-auto">
            @foreach(session('import_errors') as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Tabs Nav -->
    <div class="flex border-b border-line gap-2">
        <a href="{{ route('admin-prodi.users.index', ['prodi_id' => $activeProdi?->id, 'tab' => 'dosen']) }}" 
           class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-all {{ $tab === 'dosen' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink' }}">
            Data Dosen Pengampu ({{ $dosens->count() }})
        </a>
        <a href="{{ route('admin-prodi.users.index', ['prodi_id' => $activeProdi?->id, 'tab' => 'mahasiswa']) }}" 
           class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-all {{ $tab === 'mahasiswa' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink' }}">
            Data Mahasiswa ({{ $mahasiswas->total() }})
        </a>
    </div>

    @if($tab === 'dosen')
    <!-- ================= TAB DOSEN ================= -->
    <div class="surface p-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-ink">Daftar Dosen Pengampu — {{ $activeProdi?->name }}</h2>
                <p class="text-xs text-muted">Dosen yang telah terdaftar dapat ditugaskan sebagai Dosen Ketua atau Dosen Wakil di kelas.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin-prodi.users.template', 'dosen') }}" class="button-secondary text-xs">
                    Unduh Template Excel Dosen
                </a>
                <button type="button" onclick="openImportModal('dosen')" class="button-secondary text-xs">
                    Impor Excel Dosen
                </button>
                <button type="button" onclick="openCreateUserModal('dosen')" class="button-primary text-xs">
                    + Tambah Dosen Manual
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas/60 text-muted">
                        <th class="px-4 py-3.5 w-12 text-center !align-middle">No</th>
                        <th class="px-4 py-3.5 w-48 !align-middle">NIDN / NIP</th>
                        <th class="px-4 py-3.5 !align-middle">Nama Lengkap &amp; Gelar</th>
                        <th class="px-4 py-3.5 w-60 !align-middle">Email Institusi</th>
                        <th class="px-4 py-3.5 w-36 !align-middle">Program Studi</th>
                        <th class="px-4 py-3.5 w-36 text-right !align-middle">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($dosens as $idx => $d)
                    <tr class="hover:bg-canvas/30">
                        <td class="px-4 py-3.5 text-center text-muted !align-middle">{{ $idx + 1 }}</td>
                        <td class="px-4 py-3.5 font-mono font-bold text-ink !align-middle">{{ $d->nim_nidn ?? '—' }}</td>
                        <td class="px-4 py-3.5 font-semibold text-ink !align-middle">{{ $d->name }}</td>
                        <td class="px-4 py-3.5 text-muted !align-middle">{{ $d->email }}</td>
                        <td class="px-4 py-3.5 !align-middle">
                            <span class="font-medium text-ink text-xs">{{ $d->prodi?->code ?? 'Semua Prodi' }}</span>
                        </td>
                        <td class="px-4 py-3.5 text-right !align-middle">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" 
                                        onclick="openEditUserModal({{ $d->id }}, '{{ addslashes($d->name) }}', '{{ addslashes($d->email) }}', '{{ addslashes($d->nim_nidn ?? '') }}', {{ $d->prodi_id ?? 'null' }}, 'dosen')"
                                        class="button-secondary text-[11px] py-1 px-2.5">
                                    Ubah
                                </button>
                                <form action="{{ route('admin-prodi.users.destroy', $d->id) }}" method="POST" onsubmit="return confirm('Hapus data dosen {{ $d->name }}?');" class="inline">
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
                        <td colspan="6" class="p-8 text-center text-muted !align-middle">Belum ada dosen yang terdaftar. Gunakan tombol di atas untuk menambah atau mengimpor data.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @else
    <!-- ================= TAB MAHASISWA ================= -->
    <div class="surface p-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-ink">Daftar Mahasiswa — {{ $activeProdi?->name }}</h2>
                <p class="text-xs text-muted">Mahasiswa terdaftar dapat bergabung ke kelas mata kuliah via Link / Barcode yang dibagikan dosen.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin-prodi.users.template', 'mahasiswa') }}" class="button-secondary text-xs">
                    Unduh Template Excel Mahasiswa
                </a>
                <button type="button" onclick="openImportModal('mahasiswa')" class="button-secondary text-xs">
                    Impor Excel Mahasiswa
                </button>
                <button type="button" onclick="openCreateUserModal('mahasiswa')" class="button-primary text-xs">
                    + Tambah Mahasiswa Manual
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas/60 text-muted">
                        <th class="px-4 py-3.5 w-12 text-center !align-middle">No</th>
                        <th class="px-4 py-3.5 w-44 !align-middle">NIM</th>
                        <th class="px-4 py-3.5 !align-middle">Nama Mahasiswa</th>
                        <th class="px-4 py-3.5 w-60 !align-middle">Email Mahasiswa</th>
                        <th class="px-4 py-3.5 w-48 !align-middle">Program Studi</th>
                        <th class="px-4 py-3.5 w-36 text-right !align-middle">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($mahasiswas as $idx => $m)
                    <tr class="hover:bg-canvas/30">
                        <td class="px-4 py-3.5 text-center text-muted !align-middle">{{ $mahasiswas->firstItem() + $idx }}</td>
                        <td class="px-4 py-3.5 font-mono font-bold text-ink !align-middle">{{ $m->nim_nidn ?? '—' }}</td>
                        <td class="px-4 py-3.5 font-semibold text-ink !align-middle">{{ $m->name }}</td>
                        <td class="px-4 py-3.5 text-muted !align-middle">{{ $m->email }}</td>
                        <td class="px-4 py-3.5 !align-middle">
                            <span class="font-medium text-ink text-xs">{{ $m->prodi?->name ?? 'Teknik Informatika' }}</span>
                        </td>
                        <td class="px-4 py-3.5 text-right !align-middle">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" 
                                        onclick="openEditUserModal({{ $m->id }}, '{{ addslashes($m->name) }}', '{{ addslashes($m->email) }}', '{{ addslashes($m->nim_nidn ?? '') }}', {{ $m->prodi_id ?? 'null' }}, 'mahasiswa')"
                                        class="button-secondary text-[11px] py-1 px-2.5">
                                    Ubah
                                </button>
                                <form action="{{ route('admin-prodi.users.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Hapus data mahasiswa {{ $m->name }}?');" class="inline">
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
                        <td colspan="6" class="p-8 text-center text-muted !align-middle">Belum ada mahasiswa terdaftar pada program studi ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($mahasiswas->hasPages())
            <div class="pt-4 border-t border-line">
                {{ $mahasiswas->links() }}
            </div>
        @endif
    </div>
    @endif
</div>

<!-- Modal Tambah Pengguna Manual -->
<div id="createUserModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 id="create_user_modal_title" class="text-base font-bold text-ink">Tambah Data Pengguna</h2>
            <button type="button" onclick="closeCreateUserModal()" class="text-muted hover:text-ink text-xl">&times;</button>
        </div>
        <form action="{{ route('admin-prodi.users.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="role_type" id="create_role_type" value="mahasiswa">
            <input type="hidden" name="prodi_id" value="{{ $activeProdi?->id }}">

            <div>
                <label for="create_id_num" id="create_id_label" class="block text-xs font-semibold text-ink mb-1">NIM / NIDN</label>
                <input type="text" name="nim_nidn" id="create_id_num" required maxlength="30" class="field text-xs font-semibold font-mono">
            </div>

            <div>
                <label for="create_name" class="block text-xs font-semibold text-ink mb-1">Nama Lengkap</label>
                <input type="text" name="name" id="create_name" required maxlength="150" class="field text-xs font-semibold">
            </div>

            <div>
                <label for="create_email" class="block text-xs font-semibold text-ink mb-1">Alamat Email</label>
                <input type="email" name="email" id="create_email" required maxlength="150" class="field text-xs font-semibold">
            </div>

            <div>
                <label for="create_password" class="block text-xs font-semibold text-ink mb-1">Kata Sandi (Opsional, Default: password123)</label>
                <input type="password" name="password" id="create_password" placeholder="password123" class="field text-xs font-semibold">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="button" onclick="closeCreateUserModal()" class="button-secondary text-xs">Batal</button>
                <button type="submit" class="button-primary text-xs">Simpan Data Pengguna</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ubah Pengguna -->
<div id="editUserModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 id="edit_user_modal_title" class="text-base font-bold text-ink">Ubah Data Pengguna</h2>
            <button type="button" onclick="closeEditUserModal()" class="text-muted hover:text-ink text-xl">&times;</button>
        </div>
        <form id="editUserForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="prodi_id" id="edit_prodi_id" value="{{ $activeProdi?->id }}">

            <div>
                <label for="edit_id_num" id="edit_id_label" class="block text-xs font-semibold text-ink mb-1">NIM / NIDN</label>
                <input type="text" name="nim_nidn" id="edit_id_num" required maxlength="30" class="field text-xs font-semibold font-mono">
            </div>

            <div>
                <label for="edit_name" class="block text-xs font-semibold text-ink mb-1">Nama Lengkap</label>
                <input type="text" name="name" id="edit_name" required maxlength="150" class="field text-xs font-semibold">
            </div>

            <div>
                <label for="edit_email" class="block text-xs font-semibold text-ink mb-1">Alamat Email</label>
                <input type="email" name="email" id="edit_email" required maxlength="150" class="field text-xs font-semibold">
            </div>

            <div>
                <label for="edit_password" class="block text-xs font-semibold text-ink mb-1">Ganti Password (Kosongkan jika tidak diubah)</label>
                <input type="password" name="password" id="edit_password" placeholder="••••••••" class="field text-xs font-semibold">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="button" onclick="closeEditUserModal()" class="button-secondary text-xs">Batal</button>
                <button type="submit" class="button-primary text-xs">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Impor Excel/CSV -->
<div id="importUserModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 id="import_modal_title" class="text-base font-bold text-ink">Impor Data via Excel / CSV</h2>
            <button type="button" onclick="closeImportModal()" class="text-muted hover:text-ink text-xl">&times;</button>
        </div>
        <form action="{{ route('admin-prodi.users.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="role_type" id="import_role_type" value="mahasiswa">
            <input type="hidden" name="prodi_id" value="{{ $activeProdi?->id }}">

            <div class="p-3 bg-canvas/60 rounded-lg border border-line text-xs space-y-1">
                <p class="font-bold text-ink">Format File Template Excel/CSV:</p>
                <p class="text-muted">Kolom 1: Nomor Identitas (NIM / NIDN)</p>
                <p class="text-muted">Kolom 2: Nama Lengkap</p>
                <p class="text-muted">Kolom 3: Email</p>
                <p class="text-muted">Kolom 4: Password (opsional)</p>
            </div>

            <div>
                <label for="import_file" class="block text-xs font-semibold text-ink mb-1">Pilih File (.csv, .xlsx, .xls)</label>
                <input type="file" name="file" id="import_file" required accept=".csv,.txt,.xlsx,.xls" class="field text-xs">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="button" onclick="closeImportModal()" class="button-secondary text-xs">Batal</button>
                <button type="submit" class="button-primary text-xs">Unggah &amp; Proses Impor</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchProdi(prodiId) {
        window.location.href = `{{ route('admin-prodi.users.index') }}?prodi_id=${prodiId}&tab={{ $tab }}`;
    }

    function openCreateUserModal(type) {
        document.getElementById('create_role_type').value = type;
        const isDosen = type === 'dosen';
        document.getElementById('create_user_modal_title').textContent = isDosen ? 'Tambah Dosen Manual' : 'Tambah Mahasiswa Manual';
        document.getElementById('create_id_label').textContent = isDosen ? 'NIDN / NIP' : 'NIM';
        document.getElementById('createUserModal').classList.remove('hidden');
        document.getElementById('createUserModal').classList.add('flex');
    }
    function closeCreateUserModal() {
        document.getElementById('createUserModal').classList.add('hidden');
        document.getElementById('createUserModal').classList.remove('flex');
    }

    function openEditUserModal(id, name, email, idNum, prodiId, type) {
        const form = document.getElementById('editUserForm');
        form.action = `/admin-prodi/pengguna/${id}`;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_id_num').value = idNum;
        if (prodiId) document.getElementById('edit_prodi_id').value = prodiId;
        document.getElementById('edit_id_label').textContent = (type === 'dosen') ? 'NIDN / NIP' : 'NIM';
        document.getElementById('editUserModal').classList.remove('hidden');
        document.getElementById('editUserModal').classList.add('flex');
    }
    function closeEditUserModal() {
        document.getElementById('editUserModal').classList.add('hidden');
        document.getElementById('editUserModal').classList.remove('flex');
    }

    function openImportModal(type) {
        document.getElementById('import_role_type').value = type;
        document.getElementById('import_modal_title').textContent = (type === 'dosen') ? 'Impor Data Dosen via Excel' : 'Impor Data Mahasiswa via Excel';
        document.getElementById('importUserModal').classList.remove('hidden');
        document.getElementById('importUserModal').classList.add('flex');
    }
    function closeImportModal() {
        document.getElementById('importUserModal').classList.add('hidden');
        document.getElementById('importUserModal').classList.remove('flex');
    }
</script>
@endsection
