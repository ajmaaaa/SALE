<?php

namespace App\Http\Controllers;

use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\AdminPreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPreviewController extends Controller
{
    public function page(Request $request, string $section = 'dashboard')
    {
        abort_unless(in_array($section, ['dashboard', 'akademik', 'pengguna', 'aktivitas', 'monitoring', 'laporan', 'pengaturan'], true), 404);
        $users = AdminPreview::users();
        $academic = AdminPreview::academic();
        $settings = AdminPreview::settings();
        $logs = AdminPreview::logs();
        $q = mb_strtolower((string) $request->query('q', ''));
        $visibleUsers = array_filter($users, fn ($user) => str_contains(mb_strtolower($user['name'].' '.$user['email'].' '.$user['number']), $q)
            && (! $request->filled('role') || AdminPreview::hasRole($user, $request->query('role')))
            && (! $request->filled('prodi_id') || (int) ($user['prodi_id'] ?? 0) === $request->integer('prodi_id')));
        $visibleAcademic = array_filter($academic, fn ($record) => str_contains(mb_strtolower($record['name'].' '.$record['code']), $q)
            && (! $request->filled('type') || $record['type'] === $request->query('type')));
        $edit = $request->integer('edit');
        $record = $section === 'pengguna' ? ($users[$edit] ?? null) : ($academic[$edit] ?? null);
        $aiRequestRows = collect();

        if ($section === 'monitoring' && Schema::hasTable('ai_api_calls')) {
            $aiRequestRows = DB::table('ai_api_calls')->latest('created_at')->latest('id')->limit(50)->get();
        }

        $aiMetrics = ['requests' => 0, 'input_tokens' => 0, 'output_tokens' => 0, 'total_tokens' => 0, 'average_latency_ms' => null];
        $aiByFeature = collect();
        if (Schema::hasTable('ai_api_calls')) {
            $row = DB::table('ai_api_calls')->where('created_at', '>=', now()->startOfMonth())
                ->selectRaw('COUNT(*) requests, COALESCE(SUM(input_tokens), 0) input_tokens, COALESCE(SUM(output_tokens), 0) output_tokens, COALESCE(SUM(total_tokens), 0) total_tokens, AVG(latency_ms) average_latency_ms')
                ->first();
            $aiMetrics = (array) $row;
            $aiByFeature = DB::table('ai_api_calls')->where('created_at', '>=', now()->startOfMonth())
                ->selectRaw('feature, COUNT(*) requests, COALESCE(SUM(total_tokens), 0) total_tokens, AVG(total_tokens) average_tokens')
                ->groupBy('feature')->orderByDesc('total_tokens')->get();
        }

        return view('admin.'.$section, compact(
            'users', 'academic', 'settings', 'logs', 'visibleUsers', 'visibleAcademic', 'record', 'aiRequestRows', 'aiMetrics', 'aiByFeature'
        ));
    }

    public function user(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($request->integer('id'))],
            'number' => ['required', 'string', 'max:30', Rule::unique('users', 'nim_nidn')->ignore($request->integer('id'))],
            'role' => ['nullable', Rule::in([Role::MAHASISWA, Role::DOSEN, Role::ADMIN, Role::ADMIN_PRODI])],
            'roles' => ['nullable', 'array', 'min:1'],
            'roles.*' => [Rule::in([Role::MAHASISWA, Role::DOSEN, Role::ADMIN, Role::ADMIN_PRODI])],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            'prodi_id' => ['nullable', 'exists:prodis,id'],
        ]);

        $roleNames = array_values(array_unique($data['roles'] ?? array_filter([$data['role'] ?? null])));
        if (! $roleNames) {
            return back()->withErrors(['roles' => 'Pilih minimal satu peran akses.'])->withInput();
        }
        $roles = Role::whereIn('name', $roleNames)->get()->keyBy('name');
        if ($roles->count() !== count($roleNames)) {
            return back()->withErrors(['roles' => 'Peran yang dipilih belum tersedia di database.'])->withInput();
        }
        $existing = isset($data['id']) ? User::findOrFail($data['id']) : null;

        if ($existing?->hasRole(Role::ADMIN)
            && (! in_array(Role::ADMIN, $roleNames, true) || $data['status'] !== 'aktif')
            && User::where('is_active', true)->where('id', '!=', $existing->id)
                ->where(fn ($query) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', Role::ADMIN))
                    ->orWhereHas('role', fn ($roleQuery) => $roleQuery->where('name', Role::ADMIN)))
                ->doesntExist()) {
            return back()->withErrors(['role' => 'Minimal satu administrator harus tetap aktif.'])->withInput();
        }

        $temporaryPassword = null;
        DB::transaction(function () use ($data, $roles, $roleNames, $existing, &$temporaryPassword) {
            $primaryRoleName = $existing?->role && in_array($existing->role->name, $roleNames, true)
                ? $existing->role->name
                : $roleNames[0];
            $primaryRole = $roles[$primaryRoleName];
            $prodiId = ! empty($data['prodi_id']) ? (int) $data['prodi_id'] : null;
            $managingProdiId = in_array(Role::ADMIN_PRODI, $roleNames, true) ? $prodiId : null;

            if ($existing) {
                $existing->update([
                    'name' => trim($data['name']),
                    'email' => strtolower(trim($data['email'])),
                    'nim_nidn' => trim($data['number']),
                    'role_id' => $primaryRole->id,
                    'prodi_id' => $prodiId,
                    'managing_prodi_id' => $managingProdiId,
                    'is_active' => $data['status'] === 'aktif',
                ]);
                $user = $existing;
            } else {
                $temporaryPassword = Str::password(16);
                $user = User::create([
                    'name' => trim($data['name']),
                    'email' => strtolower(trim($data['email'])),
                    'nim_nidn' => trim($data['number']),
                    'role_id' => $primaryRole->id,
                    'prodi_id' => $prodiId,
                    'managing_prodi_id' => $managingProdiId,
                    'password' => Hash::make($temporaryPassword),
                    'must_change_password' => true,
                    'is_active' => $data['status'] === 'aktif',
                    'email_verified_at' => now(),
                ]);
            }
            $user->roles()->sync($roles->pluck('id')->all());
            AdminPreview::log('Menyimpan pengguna '.$user->name.' dengan peran '.implode(', ', $roleNames).'.', ['user_id' => $user->id]);
        });

        $notice = 'Data pengguna berhasil disimpan ke database.';
        if ($temporaryPassword) {
            $notice .= ' Password sementara: '.$temporaryPassword;
        }

        return redirect('/admin/pengguna')->with('notice', $notice);
    }

    public function downloadUserTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Pengguna');

        // Header
        $headers = ['NIM / NIDN', 'Nama Lengkap', 'Email', 'Peran', 'Status'];
        $sheet->fromArray([$headers], null, 'A1');

        // Sample Data
        $data = [
            ['231011401235', 'Siti Rahma', 'siti.rahma@student.test', 'mahasiswa', 'aktif'],
            ['231011401236', 'Dimas Pratama', 'dimas.pratama@student.test', 'mahasiswa', 'aktif'],
            ['198502022010121002', 'Dr. Budi Santoso, M.Kom.', 'budi.santoso@kampus.ac.id', 'dosen', 'aktif'],
            ['ADM002', 'Admin Akademik Pusat', 'admin.pusat@kampus.ac.id', 'admin', 'aktif'],
        ];
        $sheet->fromArray($data, null, 'A2');

        // Styling
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '102F50'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Auto size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'template-import-pengguna.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function bulkUsers(Request $request)
    {
        $hasFile = $request->hasFile('file');

        if ($hasFile) {
            $request->validate([
                'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
            ], [
                'file.required' => 'Pilih file Excel atau CSV yang akan diimpor.',
                'file.mimes' => 'File harus berformat .xlsx, .xls, atau .csv.',
            ]);
        } else {
            $request->validate([
                'raw_users' => ['required', 'string', 'max:50000'],
            ], [
                'raw_users.required' => 'Unggah file Excel atau masukkan data teks pengguna.',
            ]);
        }

        $rowsToProcess = [];

        if ($hasFile) {
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());

            try {
                if (in_array($ext, ['csv', 'txt'], true)) {
                    $raw = file_get_contents($file->getRealPath());
                    $clean = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
                    $lines = preg_split('/\r\n|\r|\n/', trim($clean));
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line === '' || str_starts_with($line, '#')) {
                            continue;
                        }
                        $delimiter = str_contains($line, "\t") ? "\t" : (str_contains($line, ';') ? ';' : ',');
                        $rowsToProcess[] = array_map('trim', str_getcsv($line, $delimiter, '"', '\\'));
                    }
                } else {
                    $spreadsheet = IOFactory::load($file->getRealPath());
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheetRows = $sheet->toArray(null, true, true, false);
                    foreach ($sheetRows as $r) {
                        if (empty($r) || ! is_array($r)) {
                            continue;
                        }
                        $trimmed = array_map(fn ($val) => trim((string) $val), $r);
                        if (count(array_filter($trimmed)) === 0) {
                            continue;
                        }
                        $rowsToProcess[] = $trimmed;
                    }
                }
            } catch (\Throwable $e) {
                return back()->withErrors(['file' => 'Gagal membaca file Excel: '.$e->getMessage()]);
            }
        } else {
            $lines = preg_split('/\r\n|\r|\n/', trim($request->input('raw_users', '')));
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                $delimiter = str_contains($line, "\t") ? "\t" : (str_contains($line, ';') ? ';' : ',');
                $rowsToProcess[] = array_map('trim', str_getcsv($line, $delimiter, '"', '\\'));
            }
        }

        // If the first row looks like a header, skip it
        if (! empty($rowsToProcess)) {
            $firstRowStr = strtolower(implode(' ', $rowsToProcess[0]));
            if (str_contains($firstRowStr, 'nim') || str_contains($firstRowStr, 'nidn') || str_contains($firstRowStr, 'email') || str_contains($firstRowStr, 'nama') || str_contains($firstRowStr, 'peran')) {
                array_shift($rowsToProcess);
            }
        }

        if (empty($rowsToProcess)) {
            return back()->withErrors(['file' => 'Tidak ada data pengguna yang valid untuk diimpor.']);
        }

        $saved = 0;
        $skipped = 0;

        DB::transaction(function () use ($rowsToProcess, &$saved, &$skipped) {
            foreach ($rowsToProcess as $cols) {
                $idNum = $cols[0] ?? '';
                $name = $cols[1] ?? '';
                $email = $cols[2] ?? '';
                $roleRaw = $cols[3] ?? 'mahasiswa';
                $statusRaw = $cols[4] ?? 'aktif';

                if ($idNum === '' || $name === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    continue;
                }

                $roleName = in_array(strtolower($roleRaw), [Role::MAHASISWA, Role::DOSEN, Role::ADMIN, Role::ADMIN_PRODI], true)
                    ? strtolower($roleRaw)
                    : Role::MAHASISWA;
                $roleId = Role::where('name', $roleName)->value('id');

                $existing = User::where('email', strtolower($email))->orWhere('nim_nidn', $idNum)->first();
                if ($existing && (strcasecmp($existing->email, $email) !== 0 || $existing->nim_nidn !== $idNum)) {
                    $skipped++;
                    continue;
                }

                $isActive = strtolower($statusRaw) === 'aktif';

                if ($existing) {
                    $existing->update([
                        'name' => $name,
                        'role_id' => $roleId,
                        'is_active' => $isActive,
                    ]);
                    $existing->roles()->syncWithoutDetaching([$roleId]);
                } else {
                    $created = User::create([
                        'nim_nidn' => $idNum,
                        'name' => $name,
                        'email' => strtolower($email),
                        'role_id' => $roleId,
                        'password' => Hash::make(Str::password(16)),
                        'must_change_password' => true,
                        'is_active' => $isActive,
                        'email_verified_at' => now(),
                    ]);
                    $created->roles()->sync([$roleId]);
                }
                $saved++;
            }
            AdminPreview::log("Mengimpor {$saved} pengguna ke database.", ['skipped' => $skipped]);
        });

        return redirect('/admin/pengguna')->with('notice', "Berhasil menyimpan {$saved} pengguna; {$skipped} baris dilewati.");
    }

    public function deleteUser(Request $request, int $id)
    {
        $user = User::with('role')->findOrFail($id);
        if ($user->hasRole(Role::ADMIN) && User::where('is_active', true)->where('id', '!=', $user->id)
            ->where(fn ($query) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', Role::ADMIN))
                ->orWhereHas('role', fn ($roleQuery) => $roleQuery->where('name', Role::ADMIN)))
            ->doesntExist()) {
            return back()->withErrors(['role' => 'Minimal satu administrator harus tetap aktif.']);
        }
        if ($user->classSectionsTeaching()->exists() || $user->classSectionsAssisting()->exists() || $user->classSectionsEnrolled()->exists()) {
            return back()->withErrors(['user' => 'Pengguna masih terhubung dengan kelas. Lepaskan relasinya sebelum menghapus akun.']);
        }
        $name = $user->name;
        DB::transaction(function () use ($user, $name) {
            $user->delete();
            AdminPreview::log('Menghapus pengguna '.$name.'.');
        });
        return redirect('/admin/pengguna')->with('notice', 'Pengguna '.$name.' berhasil dihapus dari database.');
    }

    public function academic(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'type' => ['required', Rule::in(['fakultas', 'prodi', 'semester'])],
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:150'],
            'parent' => ['nullable', 'integer'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        DB::transaction(function () use ($data) {
            if ($data['type'] === 'fakultas') {
                SystemSetting::updateOrCreate(['key' => 'faculty_code'], ['value' => strtoupper(trim($data['code']))]);
                SystemSetting::updateOrCreate(['key' => 'faculty_name'], ['value' => trim($data['name'])]);
                SystemSetting::updateOrCreate(['key' => 'faculty_status'], ['value' => $data['status']]);
            } elseif ($data['type'] === 'prodi') {
                if ((int) ($data['parent'] ?? 0) !== AdminPreview::FACULTY_ID || ! SystemSetting::valueFor('faculty_name')) {
                    abort(422, 'Program studi harus memiliki fakultas induk yang tersimpan.');
                }
                $modelId = isset($data['id']) ? (int) $data['id'] - AdminPreview::PRODI_OFFSET : null;
                Prodi::updateOrCreate(['id' => $modelId], ['code' => strtoupper(trim($data['code'])), 'name' => trim($data['name'])]);
            } else {
                $modelId = isset($data['id']) ? (int) $data['id'] - AdminPreview::SEMESTER_OFFSET : null;
                if ($data['status'] === 'aktif') {
                    Semester::query()->update(['is_active' => false]);
                }
                Semester::updateOrCreate(['id' => $modelId], [
                    'code' => trim($data['code']), 'name' => trim($data['name']), 'is_active' => $data['status'] === 'aktif',
                ]);
            }
            AdminPreview::log('Menyimpan '.$data['type'].' '.$data['name'].' ke database.');
        });

        return redirect('/admin/akademik')->with('notice', 'Data akademik berhasil disimpan ke database.');
    }

    public function deleteAcademic(Request $request, int $id)
    {
        if ($id === AdminPreview::FACULTY_ID) {
            if (Prodi::exists()) {
                return back()->withErrors(['parent' => 'Fakultas tidak dapat dihapus selama masih memiliki program studi.']);
            }
            SystemSetting::whereIn('key', ['faculty_code', 'faculty_name', 'faculty_status'])->delete();
        } elseif ($id >= AdminPreview::SEMESTER_OFFSET) {
            $semester = Semester::findOrFail($id - AdminPreview::SEMESTER_OFFSET);
            if ($semester->classSections()->exists()) {
                return back()->withErrors(['semester' => 'Semester masih digunakan oleh kelas.']);
            }
            $semester->delete();
        } elseif ($id >= AdminPreview::PRODI_OFFSET) {
            $prodi = Prodi::findOrFail($id - AdminPreview::PRODI_OFFSET);
            if ($prodi->mataKuliahs()->exists() || $prodi->users()->exists()) {
                return back()->withErrors(['prodi' => 'Program studi masih terhubung dengan mata kuliah atau pengguna.']);
            }
            $prodi->delete();
        } else {
            abort(404);
        }
        AdminPreview::log('Menghapus data akademik dari database.', ['encoded_id' => $id]);
        return redirect('/admin/akademik')->with('notice', 'Data akademik berhasil dihapus dari database.');
    }

    public function settings(Request $request)
    {
        $data = $request->validate([
            'institution' => ['required', 'string', 'max:150'], 'institution_code' => ['nullable', 'string', 'max:20'],
            'semester' => ['required', 'string', 'max:80'], 'support' => ['required', 'email', 'max:150'],
            'ai_token_quota' => ['nullable', 'integer', 'min:10000'], 'ai_model' => ['nullable', 'string', 'max:100'],
            'maintenance_mode' => ['nullable', Rule::in(['0', '1'])],
        ]);
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                SystemSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
            }
            Semester::query()->update(['is_active' => false]);
            Semester::where('name', $data['semester'])->update(['is_active' => true]);
            AdminPreview::log('Memperbarui pengaturan institusi di database.');
        });
        return back()->with('notice', 'Pengaturan sistem berhasil disimpan ke database.');
    }

    public function export()
    {
        AdminPreview::log('Mengunduh rekap data akademik CSV.');
        return response()->streamDownload(function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Jenis', 'Kode', 'Nama', 'Status', 'Jumlah peserta'], ',', '"', '');
            foreach (AdminPreview::academic() as $record) {
                $row = [$record['type'], $record['code'], $record['name'], $record['status'], count($record['students'])];
                fputcsv($file, array_map(fn ($value) => preg_match('/^[=+@\-\t\r\n]/', (string) $value) ? "'".$value : $value, $row), ',', '"', '');
            }
            fclose($file);
        }, 'sale-rekap-akademik.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
