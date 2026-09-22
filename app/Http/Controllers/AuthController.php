<?php

namespace App\Http\Controllers;

use App\Support\AdminPreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $users = AdminPreview::users();
        $personas = [
            'mahasiswa' => collect($users)->firstWhere('role', 'mahasiswa') ?? [
                'id' => 1, 'name' => 'Ahmad Maulana', 'email' => 'ahmad@example.test', 'number' => '231011401234', 'role' => 'mahasiswa'
            ],
            'dosen' => collect($users)->firstWhere('role', 'dosen') ?? [
                'id' => 2, 'name' => 'Budi Santoso, M.Kom.', 'email' => 'budi@example.test', 'number' => '198501012010121001', 'role' => 'dosen'
            ],
            'kaprodi' => collect($users)->firstWhere('role', 'kaprodi') ?? [
                'id' => 5, 'name' => 'Budi Santoso, M.Kom.', 'email' => 'kaprodi@example.test', 'number' => '197501012000031001', 'role' => 'kaprodi'
            ],
            'admin_prodi' => collect($users)->firstWhere('role', 'admin_prodi') ?? [
                'id' => 4, 'name' => 'Admin Prodi TI', 'email' => 'adminprodi@example.test', 'number' => 'AP001', 'role' => 'admin_prodi'
            ],
            'admin' => collect($users)->firstWhere('role', 'admin') ?? [
                'id' => 3, 'name' => 'Admin Sistem Akademik', 'email' => 'admin@example.test', 'number' => 'ADM001', 'role' => 'admin'
            ],
        ];

        $defaultRole = $request->query('role', request()->is('dosen*') ? 'dosen' : 'mahasiswa');
        if (!in_array($defaultRole, ['mahasiswa', 'dosen', 'admin'])) {
            $defaultRole = 'mahasiswa';
        }

        return view('auth.login', compact('personas', 'defaultRole'));
    }

    public function showLogin(Request $request)
    {
        return $this->login($request);
    }

    public function authenticate(Request $request)
    {
        $users = AdminPreview::users();

        // 1. Check if persona quick access is used
        if ($request->filled('persona_id')) {
            $user = $users[$request->integer('persona_id')] ?? null;
            if ($user) {
                session(['auth_user' => $user]);
                return $this->redirectForRole($user['role'], "Masuk sebagai {$user['name']}.");
            }
        }

        $selectedRole = $request->input('role', 'mahasiswa');
        $loginId = trim((string) $request->input('login_id', $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $normalizedLogin = strtolower($loginId);

        if ($loginId === '') {
            $defaultUser = collect($users)->firstWhere('role', $selectedRole);
            if ($defaultUser) {
                return $this->loginAsUser($defaultUser, "Masuk sebagai {$defaultUser['name']}.");
            }
            return back()->withErrors(['login_id' => 'Email institusi atau NIM / NIDN wajib diisi.'])->withInput();
        }

        $user = collect($users)->first(function ($u) use ($loginId, $normalizedLogin) {
            return strcasecmp($u['email'] ?? '', $loginId) === 0
                || strcasecmp((string)($u['number'] ?? ''), $loginId) === 0
                || ($u['role'] === 'mahasiswa' && in_array($normalizedLogin, ['ahmad.maulana@student.test', 'ahmad@example.test', '231011401234']));
        });

        if (! $user && \Illuminate\Support\Facades\Schema::hasTable('users')) {
            try {
                $dbUser = \App\Models\User::with('role')
                    ->where(function ($query) use ($loginId) {
                        $query->where('email', $loginId)->orWhere('nim_nidn', $loginId);
                    })
                    ->first();

                if ($dbUser) {
                    $roleName = $dbUser->role?->name ?? ($dbUser->hasRole(\App\Models\Role::DOSEN) ? 'dosen' : 'mahasiswa');
                    $user = [
                        'id' => $dbUser->id,
                        'name' => $dbUser->name,
                        'email' => $dbUser->email,
                        'number' => $dbUser->nim_nidn,
                        'role' => $roleName,
                        'status' => 'aktif',
                    ];
                }
            } catch (\Throwable $e) {
                // Table doesn't exist or database not migrated in test
            }
        }

        if (! $user) {
            $user = collect($users)->firstWhere('role', $selectedRole);
        }

        if (! $user) {
            return back()->withErrors(['login_id' => 'Kredensial akun tidak terdaftar pada sistem institusi.'])->withInput();
        }

        if ($password !== '' && $password !== 'password' && $password !== 'secret') {
            return back()->withErrors(['password' => 'Kata sandi tidak sesuai.'])->withInput($request->only('login_id'));
        }

        return $this->loginAsUser($user, "Selamat datang kembali, {$user['name']}!");
    }

    public function switchRole(Request $request, string $role)
    {
        abort_unless(in_array($role, ['mahasiswa', 'dosen', 'admin', 'admin_prodi', 'kaprodi']), 404);

        if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
            try {
                if ($role === 'dosen') {
                    $dbUser = \App\Models\User::with('role')->whereHas('role', fn ($q) => $q->where('name', 'dosen'))->first();
                    if ($dbUser) {
                        \Illuminate\Support\Facades\Auth::login($dbUser);
                        session(['auth_user' => [
                            'id' => $dbUser->id,
                            'name' => $dbUser->name,
                            'email' => $dbUser->email,
                            'number' => $dbUser->nim_nidn,
                            'role' => 'dosen',
                            'status' => 'aktif',
                        ]]);
                    }
                    return redirect()->route('dosen.dashboard')->with('notice', 'Beralih ke peran Dosen.');
                }

                // Look up authentic database User first so Auth::user() is populated
                $dbUser = \App\Models\User::with('role')
                    ->whereHas('role', fn ($q) => $q->where('name', $role))
                    ->first();

                if ($dbUser) {
                    \Illuminate\Support\Facades\Auth::login($dbUser);
                    $user = [
                        'id' => $dbUser->id,
                        'name' => $dbUser->name,
                        'email' => $dbUser->email,
                        'number' => $dbUser->nim_nidn,
                        'role' => $role,
                        'status' => 'aktif',
                    ];
                    session(['auth_user' => $user]);
                    $roleLabel = match ($role) {
                        'kaprodi' => 'Kaprodi',
                        'dosen' => 'Dosen',
                        'admin_prodi' => 'Admin Prodi',
                        'admin' => 'Admin Sistem',
                        default => ucfirst($role),
                    };
                    return $this->redirectForRole($role, "Beralih ke peran {$roleLabel}.");
                }
            } catch (\Throwable $e) {
                // Table doesn't exist or database not migrated in test
            }
        }

        $users = AdminPreview::users();
        $user = collect($users)->firstWhere('role', $role);

        if (! $user) {
            $user = match ($role) {
                'kaprodi' => [
                    'id' => 5,
                    'name' => 'Budi Santoso, M.Kom.',
                    'email' => 'kaprodi@example.test',
                    'number' => '197501012000031001',
                    'role' => 'kaprodi',
                    'status' => 'aktif',
                ],
                'admin_prodi' => [
                    'id' => 4,
                    'name' => 'Admin Prodi TI',
                    'email' => 'adminprodi@example.test',
                    'number' => 'AP001',
                    'role' => 'admin_prodi',
                    'status' => 'aktif',
                ],
                'admin' => [
                    'id' => 3,
                    'name' => 'Admin Sistem Akademik',
                    'email' => 'admin@example.test',
                    'number' => 'ADM001',
                    'role' => 'admin',
                    'status' => 'aktif',
                ],
                'dosen' => [
                    'id' => 2,
                    'name' => 'Budi Santoso, M.Kom.',
                    'email' => 'budi@example.test',
                    'number' => '198501012010121001',
                    'role' => 'dosen',
                    'status' => 'aktif',
                ],
                default => [
                    'id' => 1,
                    'name' => 'Ahmad Maulana',
                    'email' => 'ahmad.maulana@student.test',
                    'number' => '231011401234',
                    'role' => 'mahasiswa',
                    'status' => 'aktif',
                ],
            };
        }

        session(['auth_user' => $user]);

        return $this->redirectForRole($role, "Beralih ke peran {$role} ({$user['name']}).");
    }

    public function logout(Request $request)
    {
        Auth::logout();
        session()->forget('auth_user');

        return redirect()->route('login');
    }

    private function loginAsUser(array $user, string $message)
    {
        session(['auth_user' => $user]);

        if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
            try {
                $dbUser = \App\Models\User::with('role')
                    ->where(function ($q) use ($user) {
                        if (! empty($user['email'])) {
                            $q->where('email', $user['email']);
                        }
                        if (! empty($user['number'])) {
                            $q->orWhere('nim_nidn', $user['number']);
                        }
                    })
                    ->first();

                if (! $dbUser && ! empty($user['role'])) {
                    $dbUser = \App\Models\User::with('role')
                        ->whereHas('role', fn ($q) => $q->where('name', $user['role']))
                        ->first();
                }

                if ($dbUser) {
                    Auth::login($dbUser);
                }
            } catch (\Throwable $e) {
                // Table doesn't exist or not migrated in test
            }
        }

        return $this->redirectForRole($user['role'], $message);
    }

    private function redirectForRole(string $role, string $message)
    {
        return match ($role) {
            'kaprodi' => redirect()->route('kaprodi.monitoring.cpmk')->with('notice', $message),
            'admin_prodi' => redirect()->route('admin-prodi.dashboard')->with('notice', $message),
            'dosen' => redirect()->route('dosen.dashboard')->with('notice', $message),
            'admin' => redirect()->route('admin.page', 'dashboard')->with('notice', $message),
            default => redirect()->route('mahasiswa.dashboard')->with('notice', $message),
        };
    }
}