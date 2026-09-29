<?php

namespace App\Http\Controllers;

use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\AdminPreview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\Ai\AiModelFetcher;
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

        $backupList = collect();
        $backupDir = self::getBackupDirectory();
        if (is_dir($backupDir)) {
            $files = glob($backupDir . '/*.sql');
            foreach ($files as $f) {
                $backupList->push([
                    'filename' => basename($f),
                    'size' => round(filesize($f) / 1024, 0) . ' KB',
                    'created_at' => date('d F Y, H:i', filemtime($f)) . ' WIB',
                    'timestamp' => filemtime($f),
                ]);
            }
        }
        $baseline = base_path('sale-2026-09-28.sql');
        if (file_exists($baseline)) {
            $backupList->push([
                'filename' => 'sale-2026-09-28.sql',
                'size' => round(filesize($baseline) / 1024, 0) . ' KB',
                'created_at' => '28 September 2026, 20:44 WIB',
                'timestamp' => strtotime('2026-09-28 20:44:00'),
            ]);
        }
        $backupList = $backupList->sortByDesc('timestamp')->values();
        $latestBackup = $backupList->first();

        return view('admin.'.$section, compact(
            'users', 'academic', 'settings', 'logs', 'visibleUsers', 'visibleAcademic', 'record', 'aiRequestRows', 'aiMetrics', 'aiByFeature', 'backupList', 'latestBackup'
        ));
    }

    public function user(Request $request)
    {
        if ($request->route('id') && ! $request->has('id')) {
            $request->merge(['id' => $request->route('id')]);
        }

        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($request->integer('id'))],
            'number' => ['required', 'string', 'max:30', Rule::unique('users', 'nim_nidn')->ignore($request->integer('id'))],
            'role' => ['nullable', Rule::in([Role::MAHASISWA, Role::DOSEN, Role::ADMIN, Role::ADMIN_PRODI])],
            'roles' => ['nullable', 'array', 'max:1'],
            'roles.*' => [Rule::in([Role::MAHASISWA, Role::DOSEN, Role::ADMIN, Role::ADMIN_PRODI])],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            'prodi_id' => ['nullable', 'exists:prodis,id'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);

        $roleNames = array_values(array_unique(array_filter(array_merge(
            (array) ($data['role'] ?? []),
            $data['roles'] ?? []
        ))));

        if (count($roleNames) > 1) {
            return back()->withErrors(['role' => 'Satu akun hanya boleh memiliki satu peran akses.'])->withInput();
        }

        $roleName = $roleNames[0] ?? null;
        if (! $roleName) {
            return back()->withErrors(['role' => 'Pilih satu peran akses.'])->withInput();
        }

        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            return back()->withErrors(['role' => 'Peran yang dipilih belum tersedia di database.'])->withInput();
        }

        if ($roleName === Role::ADMIN_PRODI && empty($data['prodi_id'])) {
            return back()->withErrors(['prodi_id' => 'Program studi wajib dipilih untuk peran Admin Prodi.'])->withInput();
        }
        $existing = isset($data['id']) ? User::findOrFail($data['id']) : null;

        if ($existing?->hasRole(Role::ADMIN)
            && ($roleName !== Role::ADMIN || $data['status'] !== 'aktif')
            && User::where('is_active', true)->where('id', '!=', $existing->id)
                ->where(fn ($query) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', Role::ADMIN))
                    ->orWhereHas('role', fn ($roleQuery) => $roleQuery->where('name', Role::ADMIN)))
                ->doesntExist()) {
            return back()->withErrors(['role' => 'Minimal satu administrator harus tetap aktif.'])->withInput();
        }

        $temporaryPassword = null;
        DB::transaction(function () use ($data, $role, $roleName, $existing, &$temporaryPassword) {
            $prodiId = ! empty($data['prodi_id']) ? (int) $data['prodi_id'] : null;
            $detectedAngkatan = null;
            if ($roleName === Role::MAHASISWA) {
                $num = trim($data['number']);
                if (preg_match('/^(20\d{2})/', $num, $m)) {
                    $detectedAngkatan = (int) $m[1];
                } elseif (preg_match('/^(\d{2})/', $num, $m)) {
                    $detectedAngkatan = 2000 + (int) $m[1];
                } else {
                    $activeYear = Semester::where('is_active', true)->value('academic_year');
                    $detectedAngkatan = $activeYear ? (int) explode('/', $activeYear)[0] : now()->year;
                }
            }

            $managingProdiId = $roleName === Role::ADMIN_PRODI ? $prodiId : null;

            if ($existing) {
                $updateData = [
                    'name' => trim($data['name']),
                    'email' => strtolower(trim($data['email'])),
                    'nim_nidn' => trim($data['number']),
                    'role_id' => $role->id,
                    'prodi_id' => $prodiId,
                    'managing_prodi_id' => $managingProdiId,
                    'is_active' => $data['status'] === 'aktif',
                    'angkatan' => $existing->angkatan ?? $detectedAngkatan,
                ];

                if (! empty($data['password'])) {
                    $updateData['password'] = Hash::make($data['password']);
                    $updateData['must_change_password'] = true;
                }

                $existing->update($updateData);
                $user = $existing;
            } else {
                $autoGenerated = empty($data['password']);
                $password = $autoGenerated ? Str::password(16) : $data['password'];
                if ($autoGenerated) {
                    $temporaryPassword = $password;
                }
                $user = User::create([
                    'name' => trim($data['name']),
                    'email' => strtolower(trim($data['email'])),
                    'nim_nidn' => trim($data['number']),
                    'role_id' => $role->id,
                    'prodi_id' => $prodiId,
                    'managing_prodi_id' => $managingProdiId,
                    'password' => Hash::make($password),
                    'must_change_password' => true,
                    'is_active' => $data['status'] === 'aktif',
                    'email_verified_at' => now(),
                    'angkatan' => $detectedAngkatan,
                ]);
            }
            $user->roles()->sync([$role->id]);
            AdminPreview::log('Menyimpan pengguna '.$user->name.' dengan peran '.$roleName.'.', ['user_id' => $user->id]);
        });

        $notice = 'Data pengguna berhasil disimpan ke database.';
        if ($temporaryPassword) {
            $notice .= ' Password sementara: '.$temporaryPassword.' (wajib diganti saat login pertama)';
        } elseif (! empty($data['password'])) {
            $notice .= ' Password berhasil diperbarui (pengguna wajib menggantinya saat login berikutnya).';
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

                $detectedAngkatan = null;
                if ($roleName === Role::MAHASISWA) {
                    if (preg_match('/^(20\d{2})/', $idNum, $m)) {
                        $detectedAngkatan = (int) $m[1];
                    } elseif (preg_match('/^(\d{2})/', $idNum, $m)) {
                        $detectedAngkatan = 2000 + (int) $m[1];
                    } else {
                        $activeYear = Semester::where('is_active', true)->value('academic_year');
                        $detectedAngkatan = $activeYear ? (int) explode('/', $activeYear)[0] : now()->year;
                    }
                }

                if ($existing) {
                    $updateData = [
                        'name' => $name,
                        'role_id' => $roleId,
                        'is_active' => $isActive,
                    ];
                    if ($roleName === Role::MAHASISWA && ! $existing->angkatan) {
                        $updateData['angkatan'] = $detectedAngkatan;
                    }
                    $existing->update($updateData);
                    $existing->roles()->sync([$roleId]);
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
                        'angkatan' => $detectedAngkatan,
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
                $academicYear = null;
                if (preg_match('/(\d{4}\/\d{4})/', $data['name'], $m)) {
                    $academicYear = $m[1];
                } elseif (preg_match('/(\d{4})/', $data['name'], $m)) {
                    $academicYear = $m[1] . '/' . ((int) $m[1] + 1);
                }
                $term = str_contains(strtolower($data['name']), 'genap') ? 2 : 1;

                Semester::updateOrCreate(['id' => $modelId], [
                    'code' => trim($data['code']),
                    'name' => trim($data['name']),
                    'academic_year' => $academicYear,
                    'term' => $term,
                    'is_active' => $data['status'] === 'aktif',
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
        if ($request->input('action') === 'update_maintenance') {
            $data = $request->validate([
                'maintenance_mode' => ['required', Rule::in(['0', '1'])],
            ]);
            SystemSetting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => (string) $data['maintenance_mode']]);
            $isMaint = $data['maintenance_mode'] === '1';
            $statusLabel = $isMaint ? 'Mode Pemeliharaan (Maintenance)' : 'Aktif Normal';
            AdminPreview::log("Mengubah status operasional sistem menjadi {$statusLabel}.");

            return back()->with('notice', "Status operasional sistem berhasil diperbarui: {$statusLabel}.");
        }

        $data = $request->validate([
            'institution' => ['required', 'string', 'max:150'], 'institution_code' => ['nullable', 'string', 'max:20'],
            'semester' => ['required', 'string', 'max:80'], 'support' => ['required', 'email', 'max:150'],
            'ai_token_quota' => ['nullable', 'integer', 'min:10000'],
            'ai_provider' => ['nullable', 'string', 'max:100'],
            'ai_model' => ['nullable', 'string', 'max:100'],
            'ai_api_key' => ['nullable', 'string', 'max:255'],
            'maintenance_mode' => ['nullable', Rule::in(['0', '1'])],
            'session_lifetime' => ['nullable', 'integer', 'min:5', 'max:10080'],
        ]);
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                SystemSetting::updateOrCreate(['key' => $key], ['value' => (string) ($value ?? '')]);
            }
            Semester::query()->update(['is_active' => false]);
            Semester::where('name', $data['semester'])->update(['is_active' => true]);
            if (isset($data['session_lifetime']) && (int) $data['session_lifetime'] > 0) {
                config(['session.lifetime' => (int) $data['session_lifetime']]);
            }
            AdminPreview::log('Memperbarui pengaturan institusi di database.');
        });
        return back()->with('notice', 'Pengaturan sistem berhasil disimpan ke database.');
    }

    public function testAiConnection(Request $request)
    {
        $providerInput = (string) $request->input('ai_provider', '');
        $model = (string) $request->input('ai_model', '');
        $hasKeyInput = $request->has('ai_api_key');
        $inputKey = trim((string) $request->input('ai_api_key', ''));

        $target = $providerInput ?: $model ?: 'Google AI';
        $lowerTarget = strtolower($target);
        if (str_contains($lowerTarget, 'open')) {
            $provider = 'Open AI';
            $envVar = 'OPENAI_API_KEY';
        } elseif (str_contains($lowerTarget, 'deep')) {
            $provider = 'DeepSeek';
            $envVar = 'DEEPSEEK_API_KEY';
        } else {
            $provider = 'Google AI';
            $envVar = 'GEMINI_API_KEY';
        }

        // Jika form mengirimkan input API Key kosong, simpan status kosong ke database dan kembalikan status tidak terhubung
        if ($hasKeyInput && $inputKey === '') {
            SystemSetting::updateOrCreate(['key' => 'ai_api_key'], ['value' => '']);
            if ($provider) {
                SystemSetting::updateOrCreate(['key' => 'ai_provider'], ['value' => $provider]);
            }
            if ($model) {
                SystemSetting::updateOrCreate(['key' => 'ai_model'], ['value' => $model]);
            }
            AdminPreview::log("Mengosongkan kunci API model {$provider} di pengaturan database.");

            return response()->json([
                'success' => false,
                'disconnected' => true,
                'provider' => $provider,
                'source' => 'Input Form',
                'message' => 'Kunci API dikosongkan. Tidak terhubung ke model AI.',
                'models' => AiModelFetcher::getModels($provider, ''),
                'flow' => [
                    ['step' => 'Frontend', 'status' => 'ok', 'detail' => 'Permintaan pengosongan kunci API dikirim dari browser.'],
                    ['step' => 'Backend', 'status' => 'ok', 'detail' => "Controller memperbarui konfigurasi untuk model {$provider}."],
                    ['step' => 'Database', 'status' => 'ok', 'detail' => 'Kunci API berhasil dikosongkan dari database sistem.'],
                    ['step' => 'AI API', 'status' => 'skipped', 'detail' => 'Koneksi ke gateway AI dinonaktifkan karena kunci API kosong.']
                ]
            ]);
        }

        $key = $inputKey;
        if (empty($key)) {
            $savedKey = trim((string) SystemSetting::valueFor('ai_api_key', ''));
            $key = $savedKey;
        }

        $source = $inputKey ? 'Input Form' : 'Database Sistem';

        if (empty($key)) {
            return response()->json([
                'success' => false,
                'disconnected' => true,
                'provider' => $provider,
                'source' => $source,
                'failed_at' => 'Kredensial API',
                'message' => "Kunci API belum diisi. Silakan masukkan API Key {$provider} terlebih dahulu.",
                'models' => AiModelFetcher::getModels($provider, ''),
                'flow' => [
                    ['step' => 'Frontend', 'status' => 'ok', 'detail' => 'Permintaan pengujian dikirim dari antarmuka browser.'],
                    ['step' => 'Backend', 'status' => 'ok', 'detail' => "Controller memproses permintaan untuk model {$provider}."],
                    ['step' => 'Database', 'status' => 'failed', 'detail' => "Kunci API belum diisi di form maupun database."],
                    ['step' => 'AI API', 'status' => 'skipped', 'detail' => "Permintaan dibatalkan sebelum menghubungi gateway penyedia AI."]
                ]
            ], 422);
        }

        // Simpan kunci API, provider, & model ke SystemSetting (database)
        SystemSetting::updateOrCreate(['key' => 'ai_api_key'], ['value' => $key]);
        if ($provider) {
            SystemSetting::updateOrCreate(['key' => 'ai_provider'], ['value' => $provider]);
        }
        if ($model) {
            SystemSetting::updateOrCreate(['key' => 'ai_model'], ['value' => $model]);
        }

        try {
            if ($provider === 'Google AI') {
                $response = Http::withoutVerifying()->timeout(12)
                    ->withHeaders(['x-goog-api-key' => $key])
                    ->get('https://generativelanguage.googleapis.com/v1beta/models');
            } elseif ($provider === 'Open AI') {
                $response = Http::withoutVerifying()->timeout(12)
                    ->withToken($key)
                    ->get('https://api.openai.com/v1/models');
            } else {
                $response = Http::withoutVerifying()->timeout(12)
                    ->withToken($key)
                    ->get('https://api.deepseek.com/models');
            }

            if ($response->successful()) {
                AdminPreview::log("Uji koneksi ke {$provider} API berhasil (HTTP {$response->status()}).");

                // Sinkronkan daftar model langsung dari provider API dan simpan ke cache
                $liveModels = AiModelFetcher::getModels($provider, $key, true);

                return response()->json([
                    'success' => true,
                    'provider' => $provider,
                    'models' => $liveModels,
                    'source' => $source,
                    'status_code' => $response->status(),
                    'message' => "Koneksi ke {$provider} API berhasil terhubung aktif (HTTP {$response->status()} OK).",
                    'flow' => [
                        ['step' => 'Frontend', 'status' => 'ok', 'detail' => 'Permintaan pengujian berhasil diinisiasi.'],
                        ['step' => 'Backend', 'status' => 'ok', 'detail' => 'Controller memproses autentikasi dan rute sistem.'],
                        ['step' => 'Database', 'status' => 'ok', 'detail' => "Kredensial berhasil dimuat dari database sistem."],
                        ['step' => 'AI API', 'status' => 'ok', 'detail' => "Respons 200 OK diterima dari gateway resmi {$provider}."]
                    ]
                ]);
            }

            $errorMsg = $response->json('error.message')
                ?? $response->json('message')
                ?? "Gagal autentikasi ke server penyedia {$provider} (HTTP {$response->status()}).";

            AdminPreview::log("Uji koneksi ke {$provider} API gagal (HTTP {$response->status()}).");
            return response()->json([
                'success' => false,
                'provider' => $provider,
                'source' => $source,
                'status_code' => $response->status(),
                'failed_at' => 'AI API',
                'message' => "Penyedia {$provider} menolak autentikasi (HTTP {$response->status()}): {$errorMsg}",
                'flow' => [
                    ['step' => 'Frontend', 'status' => 'ok', 'detail' => 'Permintaan pengujian dikirim dari browser.'],
                    ['step' => 'Backend', 'status' => 'ok', 'detail' => 'Controller memproses permintaan dan rute sistem.'],
                    ['step' => 'Database', 'status' => 'ok', 'detail' => "Kredensial berhasil dimuat dari database sistem."],
                    ['step' => 'AI API', 'status' => 'failed', 'detail' => "Gateway {$provider} menolak akses: {$errorMsg}"]
                ]
            ], 400);
        } catch (\Throwable $e) {
            AdminPreview::log("Uji koneksi ke {$provider} API error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'provider' => $provider,
                'source' => $source,
                'status_code' => 504,
                'failed_at' => 'AI API',
                'message' => "Gagal terhubung ke jaringan endpoint {$provider}: " . $e->getMessage(),
                'flow' => [
                    ['step' => 'Frontend', 'status' => 'ok', 'detail' => 'Permintaan pengujian dikirim dari browser.'],
                    ['step' => 'Backend', 'status' => 'ok', 'detail' => 'Controller memproses permintaan dan rute sistem.'],
                    ['step' => 'Database', 'status' => 'ok', 'detail' => "Kredensial berhasil dimuat dari database sistem."],
                    ['step' => 'AI API', 'status' => 'failed', 'detail' => "Koneksi timeout/terputus saat menghubungi gateway {$provider}."]
                ]
            ], 504);
        }
    }

    public function getAiModels(Request $request)
    {
        $provider = (string) $request->input('ai_provider', 'Google AI');
        $key = trim((string) $request->input('ai_api_key', ''));

        $models = AiModelFetcher::getModels($provider, $key);

        return response()->json([
            'success' => true,
            'provider' => $provider,
            'models' => $models,
        ]);
    }

    public static function setEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            return;
        }
        $content = file_get_contents($envPath);
        if (preg_match("/^{$key}=/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= "\n{$key}={$value}";
        }
        file_put_contents($envPath, $content);
    }

    public function export()
    {
        AdminPreview::log('Mengunduh rekap data akademik Excel.');
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Akademik');

        // 1. Judul Dokumen
        $sheet->setCellValue('A1', 'REKAPITULASI DATA STRUKTUR AKADEMIK');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '102F50']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // 2. Info Dokumen
        $sheet->setCellValue('A2', 'Sistem Informasi Akademik & OBE (SALE)');
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '475569']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        $sheet->setCellValue('A3', 'Tanggal Unduh: ' . now()->translatedFormat('d F Y, H:i'));
        $sheet->mergeCells('A3:E3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '64748B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(18);

        // 3. Header Tabel
        $tableHeaderRow = 5;
        $headers = ['Jenis', 'Kode', 'Nama', 'Status', 'Jumlah Peserta'];
        $sheet->fromArray([$headers], null, 'A' . $tableHeaderRow);
        $sheet->getStyle("A{$tableHeaderRow}:E{$tableHeaderRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        $sheet->getRowDimension($tableHeaderRow)->setRowHeight(24);

        // 4. Data rows
        $rowIdx = 6;
        $borderThin = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        foreach (AdminPreview::academic() as $record) {
            $row = [$record['type'], $record['code'], $record['name'], $record['status'], count($record['students'])];
            $sheet->fromArray([$row], null, 'A'.$rowIdx);
            $bgZebra = ($rowIdx % 2 === 0) ? 'F8FAFC' : 'FFFFFF';
            $sheet->getStyle("A{$rowIdx}:E{$rowIdx}")->applyFromArray($borderThin);
            $sheet->getStyle("A{$rowIdx}:E{$rowIdx}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bgZebra);

            // Alignment
            $sheet->getStyle("A{$rowIdx}:B{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("D{$rowIdx}:E{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($rowIdx)->setRowHeight(20);
            $rowIdx++;
        }

        // Auto-width columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A6');

        $fileName = 'sale-rekap-akademik.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportAi(): StreamedResponse
    {
        AdminPreview::log('Mengunduh rekap pemakaian token AI Excel.');
        $spreadsheet = new Spreadsheet();

        // Sheet 1: Rincian Log Panggilan AI
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Log Panggilan AI');

        // 1. Judul Dokumen
        $sheet->setCellValue('A1', 'REKAPITULASI PENGGUNAAN LAYANAN & TOKEN AI');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '102F50']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // 2. Info Dokumen
        $sheet->setCellValue('A2', 'Sistem Informasi Akademik & OBE (SALE) — Observabilitas AI');
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '475569']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        $sheet->setCellValue('A3', 'Tanggal Unduh: ' . now()->translatedFormat('d F Y, H:i') . ' | Filter: Seluruh Riwayat Pemakaian');
        $sheet->mergeCells('A3:J3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '64748B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(18);

        // 3. Header Tabel
        $tableHeaderRow = 5;
        $headers = [
            'No',
            'Waktu Panggilan',
            'Modul / Fitur',
            'Tahap',
            'Model AI',
            'Status',
            'Token Input',
            'Token Output',
            'Total Token',
            'Latensi (ms)',
        ];
        $sheet->fromArray([$headers], null, 'A' . $tableHeaderRow);
        $sheet->getStyle("A{$tableHeaderRow}:J{$tableHeaderRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);
        $sheet->getRowDimension($tableHeaderRow)->setRowHeight(24);

        // 4. Data rows
        $rowIdx = 6;
        $borderThin = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $calls = Schema::hasTable('ai_api_calls')
            ? DB::table('ai_api_calls')->orderByDesc('id')->limit(1000)->get()
            : collect();

        $num = 1;
        foreach ($calls as $call) {
            $row = [
                $num++,
                \Carbon\Carbon::parse($call->created_at)->format('Y-m-d H:i:s'),
                ucwords(str_replace('_', ' ', $call->feature ?? '-')),
                $call->stage ?? '-',
                $call->model ?? '-',
                $call->status ?? 'success',
                (int) $call->input_tokens,
                (int) $call->output_tokens,
                (int) $call->total_tokens,
                (int) $call->latency_ms,
            ];
            $sheet->fromArray([$row], null, 'A' . $rowIdx);
            $bgZebra = ($rowIdx % 2 === 0) ? 'F8FAFC' : 'FFFFFF';
            $sheet->getStyle("A{$rowIdx}:J{$rowIdx}")->applyFromArray($borderThin);
            $sheet->getStyle("A{$rowIdx}:J{$rowIdx}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bgZebra);

            $sheet->getStyle("A{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$rowIdx}:E{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("F{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$rowIdx}:J{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getRowDimension($rowIdx)->setRowHeight(20);
            $rowIdx++;
        }

        if ($calls->isNotEmpty()) {
            $sheet->setCellValue("A{$rowIdx}", 'TOTAL');
            $sheet->mergeCells("A{$rowIdx}:F{$rowIdx}");
            $sheet->setCellValue("G{$rowIdx}", "=SUM(G6:G" . ($rowIdx - 1) . ")");
            $sheet->setCellValue("H{$rowIdx}", "=SUM(H6:H" . ($rowIdx - 1) . ")");
            $sheet->setCellValue("I{$rowIdx}", "=SUM(I6:I" . ($rowIdx - 1) . ")");
            $sheet->setCellValue("J{$rowIdx}", "=AVERAGE(J6:J" . ($rowIdx - 1) . ")");
            $sheet->getStyle("A{$rowIdx}:J{$rowIdx}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("A{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$rowIdx}:J{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getRowDimension($rowIdx)->setRowHeight(22);
        } else {
            $sheet->setCellValue("A6", "Belum ada catatan log pemakaian AI pada sistem.");
            $sheet->mergeCells("A6:J6");
            $sheet->getStyle("A6:J6")->applyFromArray([
                'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
            ]);
            $sheet->getRowDimension(6)->setRowHeight(24);
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A6');

        // Sheet 2: Ringkasan Per Modul / Fitur
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Ringkasan Per Modul');
        $sheet2->setCellValue('A1', 'RINGKASAN PEMAKAIAN AI BERDASARKAN MODUL');
        $sheet2->mergeCells('A1:E1');
        $sheet2->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '102F50']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet2->getRowDimension(1)->setRowHeight(26);

        $headers2 = ['Modul / Fitur', 'Jumlah Permintaan', 'Total Token', 'Rata-rata Token', 'Persentase'];
        $sheet2->fromArray([$headers2], null, 'A3');
        $sheet2->getStyle('A3:E3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);
        $sheet2->getRowDimension(3)->setRowHeight(22);

        $featureRows = Schema::hasTable('ai_api_calls')
            ? DB::table('ai_api_calls')
                ->selectRaw('feature, COUNT(*) as requests, COALESCE(SUM(total_tokens), 0) as total_tokens, AVG(total_tokens) as avg_tokens')
                ->groupBy('feature')
                ->orderByDesc('total_tokens')
                ->get()
            : collect();

        $allTotalTokens = $featureRows->sum('total_tokens');
        $rIdx = 4;
        foreach ($featureRows as $f) {
            $pct = $allTotalTokens > 0 ? round(($f->total_tokens / $allTotalTokens) * 100, 1) . '%' : '0%';
            $sheet2->fromArray([[
                ucwords(str_replace('_', ' ', $f->feature)),
                (int) $f->requests,
                (int) $f->total_tokens,
                round((float) $f->avg_tokens, 0),
                $pct,
            ]], null, 'A' . $rIdx);
            $sheet2->getStyle("A{$rIdx}:E{$rIdx}")->applyFromArray($borderThin);
            $sheet2->getStyle("B{$rIdx}:E{$rIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet2->getRowDimension($rIdx)->setRowHeight(20);
            $rIdx++;
        }

        if ($featureRows->isEmpty()) {
            $sheet2->setCellValue("A4", "Belum ada data modul AI.");
            $sheet2->mergeCells("A4:E4");
            $sheet2->getStyle("A4:E4")->applyFromArray([
                'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
            ]);
        }

        foreach (range('A', 'E') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $fileName = 'sale-rekap-penggunaan-ai.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public static function getBackupDirectory(): string
    {
        $custom = SystemSetting::where('key', 'backup_path')->value('value');
        if ($custom && is_string($custom) && trim($custom) !== '') {
            $trimmed = trim($custom);
            if (str_starts_with($trimmed, '/')) {
                return $trimmed;
            }
            return base_path($trimmed);
        }
        return storage_path('app/backups');
    }

    public function downloadBackupSql(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $requested = basename($request->query('file', ''));
        if ($requested) {
            $path = self::getBackupDirectory() . '/' . $requested;
            if (! file_exists($path) && $requested === 'sale-2026-09-28.sql') {
                $path = base_path('sale-2026-09-28.sql');
            }
            if (file_exists($path)) {
                AdminPreview::log("Mengunduh berkas cadangan database: {$requested}");
                return response()->download($path, $requested, [
                    'Content-Type' => 'application/sql',
                ]);
            }
        }

        AdminPreview::log('Mengunduh cadangan database .sql lengkap.');

        $fileName = 'sale-database-backup-' . now()->format('Y-m-d_His') . '.sql';

        return response()->streamDownload(function () {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql') {
                $dbConfig = config('database.connections.mysql');
                $host = $dbConfig['host'] ?? '127.0.0.1';
                $port = $dbConfig['port'] ?? 3306;
                $database = $dbConfig['database'] ?? 'sale';
                $username = $dbConfig['username'] ?? 'root';
                $password = $dbConfig['password'] ?? '';

                $binary = is_executable('/usr/bin/mariadb-dump')
                    ? '/usr/bin/mariadb-dump'
                    : (is_executable('/usr/bin/mysqldump') ? '/usr/bin/mysqldump' : null);

                if ($binary) {
                    $cmd = sprintf(
                        '%s --user=%s --password=%s --host=%s --port=%s --single-transaction --quick --skip-lock-tables %s 2>/dev/null',
                        $binary,
                        escapeshellarg($username),
                        escapeshellarg($password),
                        escapeshellarg($host),
                        escapeshellarg($port),
                        escapeshellarg($database)
                    );
                    $proc = popen($cmd, 'r');
                    if ($proc) {
                        while (! feof($proc)) {
                            echo fread($proc, 8192);
                            flush();
                        }
                        pclose($proc);
                        return;
                    }
                }
            }

            $fallbackFile = base_path('sale-2026-09-28.sql');
            if (file_exists($fallbackFile)) {
                readfile($fallbackFile);
                return;
            }

            echo "-- SALE Database Backup Dump\n";
            echo "-- Waktu Ekspor: " . now()->toIso8601String() . "\n";
            echo "-- Driver: " . $driver . "\n";
        }, $fileName, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    public function createBackup(): RedirectResponse
    {
        $dir = self::getBackupDirectory();
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = 'sale-backup-' . now()->format('Y-m-d_His') . '.sql';
        $path = $dir . '/' . $filename;

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            $dbConfig = config('database.connections.mysql');
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = $dbConfig['port'] ?? 3306;
            $database = $dbConfig['database'] ?? 'sale';
            $username = $dbConfig['username'] ?? 'root';
            $password = $dbConfig['password'] ?? '';

            $binary = is_executable('/usr/bin/mariadb-dump')
                ? '/usr/bin/mariadb-dump'
                : (is_executable('/usr/bin/mysqldump') ? '/usr/bin/mysqldump' : null);

            if ($binary) {
                $cmd = sprintf(
                    '%s --user=%s --password=%s --host=%s --port=%s --single-transaction --quick --skip-lock-tables %s > %s 2>/dev/null',
                    $binary,
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($database),
                    escapeshellarg($path)
                );
                exec($cmd, $out, $code);
            }
        }

        if (! file_exists($path) || filesize($path) === 0) {
            $fallbackFile = base_path('sale-2026-09-28.sql');
            if (file_exists($fallbackFile)) {
                copy($fallbackFile, $path);
            } else {
                file_put_contents($path, "-- SALE Database Backup\n-- " . now()->toIso8601String() . "\n");
            }
        }

        AdminPreview::log("Membuat cadangan database server: {$filename}");

        return redirect()->route('admin.page', ['section' => 'monitoring', 'detail' => 'backup'])
            ->with('status', "Cadangan database server berhasil dibuat dan tersimpan: {$filename}");
    }

    public function restoreBackup(Request $request): RedirectResponse
    {
        $targetPath = null;
        $displayName = '';

        if ($request->hasFile('sql_file')) {
            $file = $request->file('sql_file');
            $displayName = $file->getClientOriginalName();
            $targetPath = $file->getRealPath();
        } elseif ($request->filled('filename')) {
            $filename = basename($request->string('filename'));
            $displayName = $filename;
            $candidate = self::getBackupDirectory() . '/' . $filename;
            if (! file_exists($candidate) && $filename === 'sale-2026-09-28.sql') {
                $candidate = base_path('sale-2026-09-28.sql');
            }
            if (file_exists($candidate)) {
                $targetPath = $candidate;
            }
        }

        if (! $targetPath || ! file_exists($targetPath)) {
            return redirect()->route('admin.page', ['section' => 'monitoring', 'detail' => 'backup'])
                ->with('status', 'Berkas cadangan tidak ditemukan untuk dipulihkan.');
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            $dbConfig = config('database.connections.mysql');
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = $dbConfig['port'] ?? 3306;
            $database = $dbConfig['database'] ?? 'sale';
            $username = $dbConfig['username'] ?? 'root';
            $password = $dbConfig['password'] ?? '';

            $binary = is_executable('/usr/bin/mariadb')
                ? '/usr/bin/mariadb'
                : (is_executable('/usr/bin/mysql') ? '/usr/bin/mysql' : null);

            if ($binary) {
                $cmd = sprintf(
                    '%s --user=%s --password=%s --host=%s --port=%s %s < %s 2>/dev/null',
                    $binary,
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($database),
                    escapeshellarg($targetPath)
                );
                exec($cmd, $out, $code);
            }
        }

        AdminPreview::log("Memulihkan basis data dari berkas cadangan: {$displayName}");

        return redirect()->route('admin.page', ['section' => 'monitoring', 'detail' => 'backup'])
            ->with('status', "Basis data berhasil dipulihkan dari cadangan: {$displayName}");
    }

    public function deleteBackup(Request $request): RedirectResponse
    {
        $filename = basename($request->string('filename'));
        if (! $filename) {
            return redirect()->route('admin.page', ['section' => 'monitoring', 'detail' => 'backup'])
                ->with('status', 'Nama berkas tidak valid.');
        }

        $dir = self::getBackupDirectory();
        $target = $dir . '/' . $filename;
        if (! file_exists($target) && $filename === 'sale-2026-09-28.sql') {
            $target = base_path('sale-2026-09-28.sql');
        }

        if (file_exists($target)) {
            @unlink($target);
            AdminPreview::log("Menghapus berkas cadangan database: {$filename}");
            return redirect()->route('admin.page', ['section' => 'monitoring', 'detail' => 'backup'])
                ->with('status', "Berkas cadangan {$filename} berhasil dihapus dari server.");
        }

        return redirect()->route('admin.page', ['section' => 'monitoring', 'detail' => 'backup'])
            ->with('status', "Berkas cadangan {$filename} tidak ditemukan di server.");
    }

    public function saveBackupSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'backup_path' => ['required', 'string', 'max:255'],
            'backup_schedule' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'manual'])],
            'backup_time' => ['required', 'string', 'max:10'],
        ]);

        foreach ($data as $key => $val) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => (string) $val]);
        }

        $dir = self::getBackupDirectory();
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        AdminPreview::log('Memperbarui konfigurasi jadwal dan direktori backup database.');

        return redirect()->route('admin.page', ['section' => 'monitoring', 'detail' => 'backup'])
            ->with('status', 'Pengaturan path penyimpanan dan jadwal backup otomatis berhasil disimpan.');
    }
}
