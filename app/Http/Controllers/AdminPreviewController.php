<?php

namespace App\Http\Controllers;

use App\Support\AdminPreview;
use App\Support\LearningPreview;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPreviewController extends Controller
{
    public function page(Request $request, string $section = 'dashboard')
    {
        abort_unless(in_array($section, ['dashboard', 'akademik', 'pengguna', 'aktivitas', 'monitoring', 'laporan', 'pengaturan']), 404);
        $users = AdminPreview::users();
        $academic = AdminPreview::academic();
        $q = mb_strtolower((string) $request->query('q', ''));
        $visibleUsers = array_filter($users, fn ($u) => str_contains(mb_strtolower($u['name'].' '.$u['email'].' '.$u['number']), $q) && (! $request->filled('role') || $u['role'] === $request->query('role')));
        $visibleAcademic = array_filter($academic, fn ($a) => str_contains(mb_strtolower($a['name'].' '.$a['code']), $q) && (! $request->filled('type') || $a['type'] === $request->query('type')));
        $edit = $request->integer('edit');
        $record = $section === 'pengguna' ? ($users[$edit] ?? null) : ($academic[$edit] ?? null);

        return view('admin.'.$section, compact('users', 'academic', 'visibleUsers', 'visibleAcademic', 'record'));
    }

    public function user(Request $request)
    {
        $data = $request->validate(['id' => 'nullable|integer', 'name' => 'required|string|max:100', 'email' => 'required|email|max:150', 'number' => 'required|string|max:30', 'role' => ['required', Rule::in(['mahasiswa', 'dosen', 'admin'])], 'status' => ['required', Rule::in(['aktif', 'nonaktif'])]]);
        $users = AdminPreview::users();
        $id = (int) ($data['id'] ?? (max(array_keys($users)) + 1));
        abort_if(isset($data['id']) && ! isset($users[$id]), 404);
        $data['id'] = $id;
        foreach ($users as $user) {
            if ($user['id'] !== $id && (strcasecmp($user['email'], $data['email']) === 0 || $user['number'] === $data['number'])) {
                return back()->withErrors(['email' => 'Email atau nomor identitas sudah dipakai.'])->withInput();
            }
        }
        if (($users[$id]['role'] ?? '') === 'admin' && ($data['role'] !== 'admin' || $data['status'] !== 'aktif') && count(array_filter($users, fn ($u) => $u['role'] === 'admin' && $u['status'] === 'aktif' && $u['id'] !== $id)) === 0) {
            return back()->withErrors(['role' => 'Minimal satu administrator harus tetap aktif.'])->withInput();
        }
        if (($data['role'] !== 'mahasiswa' || $data['status'] !== 'aktif') && array_filter(AdminPreview::academic(), fn ($record) => in_array($id, $record['students'], true))) {
            return back()->withErrors(['status' => 'Keluarkan mahasiswa dari peserta kelas sebelum mengubah peran atau menonaktifkan akun.'])->withInput();
        }
        $users[$id] = $data + ['id' => $id];
        session(['admin.users' => $users]);
        AdminPreview::log('Menyimpan pengguna '.$data['name'].' sebagai '.$data['role'].' ('.$data['status'].').');

        return redirect('/admin/pengguna')->with('notice', 'Data pengguna disimpan dalam pratinjau.');
    }

    public function bulkUsers(Request $request)
    {
        $data = $request->validate([
            'raw_users' => 'required|string|max:50000',
        ]);

        $users = AdminPreview::users();
        $lines = preg_split('/\r\n|\r|\n/', trim($data['raw_users']));
        $added = 0;
        $errors = [];

        foreach ($lines as $lineIndex => $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $delimiter = str_contains($line, "\t") ? "\t" : (str_contains($line, ';') ? ';' : ',');
            $cols = array_map('trim', str_getcsv($line, $delimiter, '"', '\\'));

            if (count($cols) < 3) {
                $errors[] = "Baris " . ($lineIndex + 1) . ": Format tidak lengkap (harus NIM, Nama, Email).";
                continue;
            }

            $number = $cols[0];
            $name = $cols[1];
            $email = $cols[2];
            $role = isset($cols[3]) && in_array(strtolower($cols[3]), ['mahasiswa', 'dosen', 'admin']) ? strtolower($cols[3]) : 'mahasiswa';
            $status = isset($cols[4]) && in_array(strtolower($cols[4]), ['aktif', 'nonaktif']) ? strtolower($cols[4]) : 'aktif';

            $exists = false;
            foreach ($users as $existing) {
                if (strcasecmp($existing['email'], $email) === 0 || $existing['number'] === $number) {
                    $errors[] = "Baris " . ($lineIndex + 1) . ": Email/NIM '{$number}' atau '{$email}' sudah digunakan.";
                    $exists = true;
                    break;
                }
            }

            if ($exists) {
                continue;
            }

            $nextId = (max(array_keys($users) ?: [0])) + 1;
            $users[$nextId] = [
                'id' => $nextId,
                'number' => $number,
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'status' => $status,
            ];
            $added++;
        }

        if ($added > 0) {
            session(['admin.users' => $users]);
            AdminPreview::log("Mengimpor {$added} pengguna secara massal.");
        }

        if (count($errors) > 0) {
            return redirect('/admin/pengguna')->with('notice', "Berhasil menambahkan {$added} pengguna. " . count($errors) . " baris dilewati karena duplikat/format.");
        }

        return redirect('/admin/pengguna')->with('notice', "Berhasil mengimpor {$added} pengguna secara massal.");
    }

    public function academic(Request $request)
    {
        $data = $request->validate(['id' => 'nullable|integer', 'type' => ['required', Rule::in(['fakultas', 'prodi', 'semester', 'kelas'])], 'code' => 'required|string|max:30', 'name' => 'required|string|max:150', 'parent' => 'nullable|integer', 'course' => ['nullable', 'integer', Rule::in(array_keys(LearningPreview::courses()))], 'students' => 'nullable|array', 'students.*' => ['integer', Rule::in(array_keys(array_filter(AdminPreview::users(), fn ($u) => $u['role'] === 'mahasiswa' && $u['status'] === 'aktif')))], 'status' => ['required', Rule::in(['aktif', 'nonaktif'])]]);
        $records = AdminPreview::academic();
        $id = (int) ($data['id'] ?? (max(array_keys($records)) + 1));
        $editing = isset($data['id']);
        abort_if($editing && ! isset($records[$id]), 404);
        $data['id'] = $id;
        foreach ($records as $record) {
            if ($record['id'] !== $id && strcasecmp($record['code'], $data['code']) === 0) {
                return back()->withErrors(['code' => 'Kode akademik sudah digunakan.'])->withInput();
            }
        }
        $parentType = ['prodi' => 'fakultas', 'kelas' => 'prodi'][$data['type']] ?? null;
        if ($parentType && (($records[$data['parent'] ?? 0]['type'] ?? null) !== $parentType || ($data['parent'] ?? null) === $id)) {
            return back()->withErrors(['parent' => 'Pilih induk '.$parentType.' yang sesuai.'])->withInput();
        }
        if ($data['type'] === 'kelas' && empty($data['course'])) {
            return back()->withErrors(['course' => 'Pilih mata kuliah untuk kelas ini.'])->withInput();
        }
        if (isset($records[$id]) && $records[$id]['type'] !== $data['type'] && array_filter($records, fn ($r) => ($r['parent'] ?? null) === $id)) {
            return back()->withErrors(['type' => 'Jenis tidak dapat diubah selama masih memiliki data turunan.'])->withInput();
        }
        $data['parent'] = $parentType ? (int) $data['parent'] : null;
        $data['course'] = $data['type'] === 'kelas' ? (int) $data['course'] : null;
        $data['students'] = $data['type'] === 'kelas' ? array_map('intval', $data['students'] ?? []) : [];
        $records[$id] = $data + ['id' => $id];
        session(['admin.academic' => $records]);
        AdminPreview::log('Menyimpan '.$data['type'].' '.$data['name'].'.');

        return redirect('/admin/akademik')->with('notice', 'Data akademik disimpan dalam pratinjau.');
    }

    public function settings(Request $request)
    {
        $data = $request->validate(['institution' => 'required|string|max:150', 'semester' => 'required|string|max:80', 'support' => 'required|email|max:150']);
        session(['admin.settings' => $data]);
        AdminPreview::log('Memperbarui pengaturan institusi.');

        return back()->with('notice', 'Pengaturan pratinjau disimpan.');
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
