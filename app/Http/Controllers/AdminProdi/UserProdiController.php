<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserProdiController extends Controller
{
    public function index(Request $request): View
    {
        $prodis = Prodi::orderBy('name')->get();
        $selectedProdiId = $request->integer('prodi_id') ?: ($prodis->first()?->id ?? 0);
        $activeProdi = $prodis->firstWhere('id', $selectedProdiId) ?? $prodis->first();

        $dosenRoleId = Role::where('name', Role::DOSEN)->value('id');
        $mahasiswaRoleId = Role::where('name', Role::MAHASISWA)->value('id');

        $tab = $request->query('tab', 'dosen');

        $dosens = User::query()
            ->where('role_id', $dosenRoleId)
            ->when($activeProdi, fn ($q) => $q->where(fn ($sub) => $sub->where('prodi_id', $activeProdi->id)->orWhereNull('prodi_id')))
            ->with('prodi')
            ->orderBy('name')
            ->get();

        $mahasiswas = User::query()
            ->where('role_id', $mahasiswaRoleId)
            ->when($activeProdi, fn ($q) => $q->where('prodi_id', $activeProdi->id))
            ->with('prodi')
            ->orderBy('nim_nidn')
            ->paginate(25)
            ->withQueryString();

        return view('admin-prodi.users.index', compact(
            'prodis',
            'activeProdi',
            'tab',
            'dosens',
            'mahasiswas'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $roleType = $request->input('role_type', 'mahasiswa');
        $roleName = $roleType === 'dosen' ? Role::DOSEN : Role::MAHASISWA;
        $role = Role::where('name', $roleName)->firstOrFail();

        $idLabel = $roleType === 'dosen' ? 'NIDN / NIP' : 'NIM';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'nim_nidn' => ['required', 'string', 'max:30', 'unique:users,nim_nidn'],
            'prodi_id' => ['required', 'exists:prodis,id'],
            'password' => ['nullable', 'string', 'min:6'],
        ], [
            'nim_nidn.required' => "{$idLabel} wajib diisi.",
            'nim_nidn.unique' => "{$idLabel} sudah terdaftar.",
            'email.unique' => 'Email sudah terdaftar pada sistem.',
        ]);

        $password = ! empty($validated['password']) ? $validated['password'] : 'password123';

        User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'nim_nidn' => trim($validated['nim_nidn']),
            'prodi_id' => $validated['prodi_id'],
            'role_id' => $role->id,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $label = $roleType === 'dosen' ? 'Dosen' : 'Mahasiswa';

        return redirect()->route('admin-prodi.users.index', ['prodi_id' => $validated['prodi_id'], 'tab' => $roleType])
            ->with('notice', "Data {$label} \"{$validated['name']}\" berhasil ditambahkan.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $roleType = $user->hasRole(Role::DOSEN) ? 'dosen' : 'mahasiswa';
        $idLabel = $roleType === 'dosen' ? 'NIDN / NIP' : 'NIM';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'nim_nidn' => ['required', 'string', 'max:30', Rule::unique('users', 'nim_nidn')->ignore($user->id)],
            'prodi_id' => ['required', 'exists:prodis,id'],
            'password' => ['nullable', 'string', 'min:6'],
        ], [
            'nim_nidn.unique' => "{$idLabel} sudah terdaftar.",
            'email.unique' => 'Email sudah terdaftar.',
        ]);

        $updateData = [
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'nim_nidn' => trim($validated['nim_nidn']),
            'prodi_id' => $validated['prodi_id'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('admin-prodi.users.index', ['prodi_id' => $validated['prodi_id'], 'tab' => $roleType])
            ->with('notice', "Data pengguna \"{$user->name}\" berhasil diperbarui.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $roleType = $user->hasRole(Role::DOSEN) ? 'dosen' : 'mahasiswa';
        $name = $user->name;
        $prodiId = $user->prodi_id;

        if ($user->classSectionsTeaching()->exists() || $user->classSectionsEnrolled()->exists()) {
            return back()->withErrors([
                'user' => "Pengguna {$name} tidak dapat dihapus karena sudah memiliki keterkaitan dengan kelas perkuliahan aktif.",
            ]);
        }

        $user->delete();

        return redirect()->route('admin-prodi.users.index', ['prodi_id' => $prodiId, 'tab' => $roleType])
            ->with('notice', "Pengguna {$name} berhasil dihapus.");
    }

    public function downloadTemplate(string $type): StreamedResponse
    {
        $type = in_array($type, ['dosen', 'mahasiswa']) ? $type : 'mahasiswa';
        $fileName = "template-import-{$type}.csv";

        return response()->streamDownload(function () use ($type) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM

            if ($type === 'dosen') {
                fputcsv($file, ['NIDN_NIP', 'Nama Lengkap', 'Email Institusi', 'Password']);
                fputcsv($file, ['198502022010121002', 'Dr. Hendra Wijaya, M.Kom.', 'hendra@example.test', 'password123']);
                fputcsv($file, ['199003032015042001', 'Nurul Hidayah, S.Kom., M.T.', 'nurul@example.test', 'password123']);
            } else {
                fputcsv($file, ['NIM', 'Nama Mahasiswa', 'Email Mahasiswa', 'Password']);
                fputcsv($file, ['231011409001', 'Muhammad Zaky Pratama', 'zaky@student.test', 'password123']);
                fputcsv($file, ['231011409002', 'Aisyah Putri Rahmadhani', 'aisyah@student.test', 'password123']);
            }

            fclose($file);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'prodi_id' => ['required', 'exists:prodis,id'],
            'role_type' => ['required', Rule::in(['dosen', 'mahasiswa'])],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ], [
            'file.required' => 'Pilih file Excel/CSV yang akan diimpor.',
            'file.mimes' => 'File yang diunggah harus berformat CSV atau TXT.',
        ]);

        $prodiId = $request->integer('prodi_id');
        $roleType = $request->input('role_type');
        $roleName = $roleType === 'dosen' ? Role::DOSEN : Role::MAHASISWA;
        $role = Role::where('name', $roleName)->firstOrFail();

        $uploadedFile = $request->file('file');
        $raw = file_get_contents($uploadedFile->getRealPath());
        $clean = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $lines = preg_split('/\r\n|\r|\n/', trim($clean));

        if (empty($lines) || count($lines) < 2) {
            return back()->withErrors(['file' => 'File CSV/Excel kosong atau tidak berisi baris data.']);
        }

        $headerLine = $lines[0];
        $delimiter = str_contains($headerLine, "\t") ? "\t" : (str_contains($headerLine, ';') ? ';' : ',');

        $successCount = 0;
        $skippedCount = 0;
        $errors = [];

        DB::transaction(function () use ($lines, $delimiter, $prodiId, $role, &$successCount, &$skippedCount, &$errors) {
            for ($i = 1; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                $cols = array_map('trim', str_getcsv($line, $delimiter, '"', '\\'));
                $rowNum = $i + 1;

                $idNum = isset($cols[0]) ? trim($cols[0], "'\" \t\n\r\0\x0B") : '';
                $name = isset($cols[1]) ? trim($cols[1], "'\" \t\n\r\0\x0B") : '';
                $email = isset($cols[2]) ? trim($cols[2], "'\" \t\n\r\0\x0B") : '';
                $pass = (isset($cols[3]) && trim($cols[3]) !== '') ? trim($cols[3]) : 'password123';

                if ($idNum === '' || $name === '' || $email === '') {
                    $skippedCount++;
                    $errors[] = "Baris {$rowNum}: Kolom nomor identitas, nama, atau email tidak boleh kosong.";
                    continue;
                }

                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skippedCount++;
                    $errors[] = "Baris {$rowNum}: Format email '{$email}' tidak valid.";
                    continue;
                }

                // Cek duplikasi di DB
                $exists = User::where('email', strtolower($email))
                    ->orWhere('nim_nidn', $idNum)
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    $errors[] = "Baris {$rowNum}: Nomor '{$idNum}' atau email '{$email}' sudah terdaftar dalam sistem.";
                    continue;
                }

                User::create([
                    'name' => $name,
                    'email' => strtolower($email),
                    'nim_nidn' => $idNum,
                    'prodi_id' => $prodiId,
                    'role_id' => $role->id,
                    'password' => Hash::make($pass),
                    'email_verified_at' => now(),
                ]);

                $successCount++;
            }
        });

        $typeLabel = $roleType === 'dosen' ? 'Dosen' : 'Mahasiswa';
        $msg = "Impor data {$typeLabel} selesai: {$successCount} berhasil ditambahkan.";
        if ($skippedCount > 0) {
            $msg .= " ({$skippedCount} baris diabaikan/duplikat).";
        }

        return redirect()->route('admin-prodi.users.index', ['prodi_id' => $prodiId, 'tab' => $roleType])
            ->with('notice', $msg)
            ->with('import_errors', $errors);
    }
}
