@extends('layouts.mahasiswa')

@section('title', 'Data Dosen & Mahasiswa | SALE')
@section('header', 'Data Dosen & Mahasiswa Program Studi')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0 flex-1">
            <nav class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('admin-prodi.dashboard') }}" class="hover:text-brand">Admin Prodi</a>
                <span>/</span>
                <span class="text-ink font-semibold">Pengguna</span>
            </nav>
            <h1 class="page-heading">Data Dosen &amp; Mahasiswa</h1>
            <p class="page-description">Input data dosen dan mahasiswa secara manual atau impor massal melalui file template Excel.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0 w-full sm:w-auto">
            <label for="select_prodi" class="text-xs font-semibold text-muted whitespace-nowrap">Program Studi:</label>
            <select id="select_prodi" onchange="switchProdi(this.value)" class="field text-xs font-semibold w-full sm:w-56 max-w-full">
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

    @if(session('temporary_credentials') && count(session('temporary_credentials')) > 0)
    <div class="rounded-xl border border-sky-300 bg-sky-50 p-4 text-xs text-sky-950">
        <p class="font-bold mb-1">Password sementara hasil impor (hanya ditampilkan sekali):</p>
        <p class="mb-2 text-sky-800">Salin dan kirimkan masing-masing password melalui kanal yang aman. Pengguna wajib menggantinya saat login pertama.</p>
        <ul class="space-y-1 max-h-40 overflow-y-auto font-mono">
            @foreach(session('temporary_credentials') as $credential)
                <li>{{ $credential['identity'] }} · {{ $credential['email'] }} · <strong>{{ $credential['password'] }}</strong></li>
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
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-bold text-ink">Daftar Dosen Pengampu: {{ $activeProdi?->name }}</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <a href="{{ route('admin-prodi.users.template', 'dosen') }}" class="button-primary text-xs flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs flex-1 sm:flex-initial" title="Unduh Template Excel Dosen">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line></svg>
                    <span>Template Excel Dosen</span>
                </a>
                <button type="button" onclick="openImportModal('dosen')" class="button-secondary text-xs flex-1 sm:flex-initial justify-center">
                    Impor Excel Dosen
                </button>
                <button type="button" onclick="openCreateUserModal('dosen')" class="button-primary text-xs w-full sm:w-auto justify-center">
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
                        <td class="px-4 py-3.5 font-mono font-bold text-ink !align-middle">{{ $d->nim_nidn ?? '' }}</td>
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
                                <form action="{{ route('admin-prodi.users.destroy', $d->id) }}" method="POST" data-confirm="Hapus data dosen {{ $d->name }}? Data yang terhubung akan ikut terdampak." data-confirm-title="Hapus dosen" data-confirm-label="Hapus" class="inline">
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
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-bold text-ink">Daftar Mahasiswa: {{ $activeProdi?->name }}</h2>
                <p class="text-xs text-muted">Mahasiswa terdaftar dapat bergabung ke kelas mata kuliah via Link / Barcode yang dibagikan dosen.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <a href="{{ route('admin-prodi.users.template', 'mahasiswa') }}" class="button-primary text-xs flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs flex-1 sm:flex-initial" title="Unduh Template Excel Mahasiswa">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line></svg>
                    <span>Template Excel Mahasiswa</span>
                </a>
                <button type="button" onclick="openImportModal('mahasiswa')" class="button-secondary text-xs flex-1 sm:flex-initial justify-center">
                    Impor Excel Mahasiswa
                </button>
                <button type="button" onclick="openCreateUserModal('mahasiswa')" class="button-primary text-xs w-full sm:w-auto justify-center">
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
                        <td class="px-4 py-3.5 font-mono font-bold text-ink !align-middle">
                            {{ $m->nim_nidn ?? '' }}
                            @if($m->angkatan)
                                <span class="block text-[11px] font-sans font-normal text-muted mt-0.5">Angkatan {{ $m->angkatan }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 font-semibold text-ink !align-middle">{{ $m->name }}</td>
                        <td class="px-4 py-3.5 text-muted !align-middle">{{ $m->email }}</td>
                        <td class="px-4 py-3.5 !align-middle">
                            <span class="font-medium text-ink text-xs">{{ $m->prodi?->name ?? 'Teknik Informatika' }}</span>
                        </td>
                        <td class="px-4 py-3.5 text-right !align-middle">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" 
                                        onclick="openEditUserModal({{ $m->id }}, '{{ addslashes($m->name) }}', '{{ addslashes($m->email) }}', '{{ addslashes($m->nim_nidn ?? '') }}', {{ $m->prodi_id ?? 'null' }}, 'mahasiswa', {{ $m->angkatan ?? 'null' }})"
                                        class="button-secondary text-[11px] py-1 px-2.5">
                                    Ubah
                                </button>
                                <form action="{{ route('admin-prodi.users.destroy', $m->id) }}" method="POST" data-confirm="Hapus data mahasiswa {{ $m->name }}? Data yang terhubung akan ikut terdampak." data-confirm-title="Hapus mahasiswa" data-confirm-label="Hapus" class="inline">
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
<div id="createUserModal" onclick="if(event.target === this) closeCreateUserModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl rounded-2xl border border-line">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 id="create_user_modal_title" class="text-base font-bold text-ink">Tambah Data Pengguna</h2>
            <button type="button" onclick="closeCreateUserModal()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="{{ route('admin-prodi.users.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="role_type" id="create_role_type" value="mahasiswa">
            <input type="hidden" name="prodi_id" value="{{ $activeProdi?->id }}">

            <div>
                <label for="create_id_num" id="create_id_label" class="block text-xs font-semibold text-ink mb-1">NIM / NIDN</label>
                <input type="text" name="nim_nidn" id="create_id_num" required maxlength="30" class="field text-xs font-semibold font-mono">
            </div>

            <div id="create_angkatan_group">
                <label for="create_angkatan" class="block text-xs font-semibold text-ink mb-1">Tahun Masuk (Angkatan)</label>
                <input type="number" name="angkatan" id="create_angkatan" min="2000" max="2099" placeholder="Contoh: {{ date('Y') }}" class="field text-xs font-semibold font-mono">
                <p class="mt-1 text-[11px] text-muted">Bisa dikosongkan jika format NIM diawali tahun (misal 2024xxx) atau memakai tahun ajaran aktif.</p>
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
                <label for="create_password" class="block text-xs font-semibold text-ink mb-1">Kata Sandi (Opsional, minimal 8 karakter)</label>
                <input type="password" name="password" id="create_password" minlength="8" autocomplete="new-password" placeholder="Kosongkan untuk membuat password acak" class="field text-xs font-semibold">
                <p class="mt-1 text-[11px] text-muted">Password sementara acak akan ditampilkan sekali setelah akun dibuat.</p>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="submit" class="button-primary text-xs">Simpan Data Pengguna</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ubah Pengguna -->
<div id="editUserModal" onclick="if(event.target === this) closeEditUserModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl rounded-2xl border border-line">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 id="edit_user_modal_title" class="text-base font-bold text-ink">Ubah Data Pengguna</h2>
            <button type="button" onclick="closeEditUserModal()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="editUserForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="prodi_id" id="edit_prodi_id" value="{{ $activeProdi?->id }}">

            <div>
                <label for="edit_id_num" id="edit_id_label" class="block text-xs font-semibold text-ink mb-1">NIM / NIDN</label>
                <input type="text" name="nim_nidn" id="edit_id_num" required maxlength="30" class="field text-xs font-semibold font-mono">
            </div>

            <div id="edit_angkatan_group">
                <label for="edit_angkatan" class="block text-xs font-semibold text-ink mb-1">Tahun Masuk (Angkatan)</label>
                <input type="number" name="angkatan" id="edit_angkatan" min="2000" max="2099" placeholder="Contoh: {{ date('Y') }}" class="field text-xs font-semibold font-mono">
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
                <label for="edit_password" class="block text-xs font-semibold text-ink mb-1">Ganti Password (Kosongkan jika tidak diubah, minimal 8 karakter)</label>
                <input type="password" name="password" id="edit_password" minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter" class="field text-xs font-semibold">
                <p class="mt-1 text-[11px] text-muted">Jika diubah, pengguna wajib menggantinya kembali saat login berikutnya.</p>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="submit" class="button-primary text-xs">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Impor Excel -->
<div id="importUserModal" onclick="if(event.target === this) closeImportModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl rounded-2xl border border-line">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 id="import_modal_title" class="text-base font-bold text-ink">Impor Data via Excel</h2>
            <button type="button" onclick="closeImportModal()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="{{ route('admin-prodi.users.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="role_type" id="import_role_type" value="mahasiswa">
            <input type="hidden" name="prodi_id" value="{{ $activeProdi?->id }}">

            <div class="p-3 bg-canvas/60 rounded-lg border border-line text-xs space-y-1">
                <p class="font-bold text-ink">Format File Template Excel (.xlsx):</p>
                <p class="text-muted">Kolom 1: Nomor Identitas (NIM / NIDN)</p>
                <p class="text-muted">Kolom 2: Nama Lengkap</p>
                <p class="text-muted">Kolom 3: Email</p>
                <p class="text-muted">Kolom 4: Password (opsional, minimal 8 karakter; kosong = dibuat acak)</p>
                <p class="text-muted" id="import_col5_note">Kolom 5: Tahun Masuk / Angkatan (opsional untuk mahasiswa; otomatis jika kosong)</p>
            </div>

            <div>
                <label for="import_file" class="block text-xs font-semibold text-ink mb-1">Pilih File Excel (.xlsx, .xls)</label>
                <input type="file" name="file" id="import_file" required accept=".xlsx,.xls,.csv" class="field text-xs">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-line">
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
        const angkatanGroup = document.getElementById('create_angkatan_group');
        if (angkatanGroup) {
            angkatanGroup.style.display = isDosen ? 'none' : 'block';
            document.getElementById('create_angkatan').value = '';
        }
        document.getElementById('createUserModal').classList.remove('hidden');
        document.getElementById('createUserModal').classList.add('flex');
    }
    function closeCreateUserModal() {
        document.getElementById('createUserModal').classList.add('hidden');
        document.getElementById('createUserModal').classList.remove('flex');
    }

    function openEditUserModal(id, name, email, idNum, prodiId, type, angkatan = null) {
        const form = document.getElementById('editUserForm');
        form.action = `/admin-prodi/pengguna/${id}`;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_id_num').value = idNum;
        if (prodiId) document.getElementById('edit_prodi_id').value = prodiId;
        const isDosen = type === 'dosen';
        document.getElementById('edit_id_label').textContent = isDosen ? 'NIDN / NIP' : 'NIM';
        const angkatanGroup = document.getElementById('edit_angkatan_group');
        if (angkatanGroup) {
            angkatanGroup.style.display = isDosen ? 'none' : 'block';
            document.getElementById('edit_angkatan').value = angkatan || '';
        }
        document.getElementById('editUserModal').classList.remove('hidden');
        document.getElementById('editUserModal').classList.add('flex');
    }
    function closeEditUserModal() {
        document.getElementById('editUserModal').classList.add('hidden');
        document.getElementById('editUserModal').classList.remove('flex');
    }

    function openImportModal(type) {
        document.getElementById('import_role_type').value = type;
        const isDosen = type === 'dosen';
        document.getElementById('import_modal_title').textContent = isDosen ? 'Impor Data Dosen via Excel' : 'Impor Data Mahasiswa via Excel';
        const col5 = document.getElementById('import_col5_note');
        if (col5) {
            col5.style.display = isDosen ? 'none' : 'block';
        }
        document.getElementById('importUserModal').classList.remove('hidden');
        document.getElementById('importUserModal').classList.add('flex');
    }
    function closeImportModal() {
        document.getElementById('importUserModal').classList.add('hidden');
        document.getElementById('importUserModal').classList.remove('flex');
    }
</script>
@endsection
