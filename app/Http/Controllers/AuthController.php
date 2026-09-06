<?php

namespace App\Http\Controllers;

use App\Support\AdminPreview;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login()
    {
        $users = AdminPreview::users();
        $personas = [
            'mahasiswa' => collect($users)->firstWhere('role', 'mahasiswa') ?? [
                'id' => 1, 'name' => 'Ahmad Maulana', 'email' => 'ahmad@example.test', 'number' => '231011401234', 'role' => 'mahasiswa'
            ],
            'dosen' => collect($users)->firstWhere('role', 'dosen') ?? [
                'id' => 2, 'name' => 'Dr. Budi Santoso, M.Kom.', 'email' => 'budi@example.test', 'number' => 'DSN001', 'role' => 'dosen'
            ],
            'admin' => collect($users)->firstWhere('role', 'admin') ?? [
                'id' => 3, 'name' => 'Admin Akademik', 'email' => 'admin@example.test', 'number' => 'ADM001', 'role' => 'admin'
            ],
        ];

        return view('auth.login', compact('personas'));
    }

    public function authenticate(Request $request)
    {
        $users = AdminPreview::users();

        // Check if persona quick access is used
        if ($request->filled('persona_id')) {
            $user = $users[$request->integer('persona_id')] ?? null;
            if ($user) {
                session(['auth_user' => $user]);
                return $this->redirectForRole($user['role'], "Masuk sebagai {$user['name']}.");
            }
        }

        $loginId = trim((string) $request->input('login_id', $request->input('email', '')));
        if ($loginId === '') {
            return back()->withErrors(['login_id' => 'Email institusi atau NIM / NIDN wajib diisi.'])->withInput();
        }

        $user = collect($users)->first(function ($u) use ($loginId) {
            return strcasecmp($u['email'] ?? '', $loginId) === 0
                || strcasecmp((string)($u['number'] ?? ''), $loginId) === 0;
        });

        if (! $user) {
            return back()->withErrors(['login_id' => 'Kredensial akun tidak terdaftar pada sistem institusi.'])->withInput();
        }

        session(['auth_user' => $user]);

        return $this->redirectForRole($user['role'], "Selamat datang kembali, {$user['name']}!");
    }

    public function switchRole(Request $request, string $role)
    {
        abort_unless(in_array($role, ['mahasiswa', 'dosen', 'admin']), 404);

        $users = AdminPreview::users();
        $user = collect($users)->firstWhere('role', $role);

        if (! $user) {
            $user = [
                'id' => $role === 'admin' ? 3 : ($role === 'dosen' ? 2 : 1),
                'name' => $role === 'admin' ? 'Admin Akademik' : ($role === 'dosen' ? 'Dr. Budi Santoso, M.Kom.' : 'Ahmad Maulana'),
                'email' => "{$role}@example.test",
                'number' => $role === 'admin' ? 'ADM001' : ($role === 'dosen' ? 'DSN001' : '231011401234'),
                'role' => $role,
                'status' => 'aktif',
            ];
        }

        session(['auth_user' => $user]);

        return $this->redirectForRole($role, "Beralih ke peran {$role} ({$user['name']}).");
    }

    public function logout(Request $request)
    {
        session()->forget('auth_user');

        return redirect()->route('login')->with('notice', 'Anda telah berhasil keluar dari akun.');
    }

    private function redirectForRole(string $role, string $message)
    {
        return match ($role) {
            'dosen' => redirect()->route('dosen.dashboard')->with('notice', $message),
            'admin' => redirect()->route('admin.page', 'dashboard')->with('notice', $message),
            default => redirect()->route('mahasiswa.dashboard')->with('notice', $message),
        };
    }
}
