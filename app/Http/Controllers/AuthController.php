<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\AdminPreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $users = $this->demoMode() ? AdminPreview::users() : [];
        $personas = $this->demoMode() ? [
            'mahasiswa' => collect($users)->first(fn ($user) => AdminPreview::hasRole($user, 'mahasiswa')) ?? [
                'id' => 1, 'name' => 'Ahmad Maulana', 'email' => 'ahmad@example.test', 'number' => '231011401234', 'role' => 'mahasiswa',
            ],
            'dosen' => collect($users)->first(fn ($user) => AdminPreview::hasRole($user, 'dosen')) ?? [
                'id' => 2, 'name' => 'Budi Santoso, M.Kom.', 'email' => 'budi@example.test', 'number' => '198501012010121001', 'role' => 'dosen',
            ],
            'admin_prodi' => collect($users)->first(fn ($user) => AdminPreview::hasRole($user, 'admin_prodi')) ?? [
                'id' => 4, 'name' => 'Admin Prodi TI', 'email' => 'adminprodi@example.test', 'number' => 'AP001', 'role' => 'admin_prodi',
            ],
            'admin' => collect($users)->first(fn ($user) => AdminPreview::hasRole($user, 'admin')) ?? [
                'id' => 3, 'name' => 'Admin Sistem Akademik', 'email' => 'admin@example.test', 'number' => 'ADM001', 'role' => 'admin',
            ],
        ] : [];

        $defaultRole = $request->query('role', request()->is('dosen*') ? 'dosen' : 'mahasiswa');
        if (! in_array($defaultRole, ['mahasiswa', 'dosen', 'admin_prodi', 'admin'])) {
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
        if (! $this->demoMode()) {
            return $this->authenticateDatabaseUser($request);
        }

        $users = AdminPreview::users();

        if ($request->filled('persona_id')) {
            $user = $users[$request->integer('persona_id')] ?? null;
            if ($user) {
                $user['role'] = $this->roleForUser($user, $request->input('role'));

                return $this->loginAsUser($user, "Masuk sebagai {$user['name']}.");
            }
        }

        $selectedRole = $request->input('role');
        if ($selectedRole !== null && ! in_array($selectedRole, ['mahasiswa', 'dosen', 'admin_prodi', 'admin'], true)) {
            $selectedRole = null;
        }
        $loginId = trim((string) $request->input('login_id', $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $normalizedLogin = strtolower($loginId);

        if ($loginId === '') {
            $defaultRole = $selectedRole ?? 'mahasiswa';
            $defaultUser = collect($users)->first(fn ($user) => AdminPreview::hasRole($user, $defaultRole));
            if ($defaultUser) {
                $defaultUser['role'] = $this->roleForUser($defaultUser, $selectedRole);

                return $this->loginAsUser($defaultUser, "Masuk sebagai {$defaultUser['name']}.");
            }

            return back()->withErrors(['login_id' => 'Email institusi atau NIM / NIDN wajib diisi.'])->withInput();
        }

        $user = collect($users)->first(function ($u) use ($loginId, $normalizedLogin) {
            return strcasecmp($u['email'] ?? '', $loginId) === 0
                || strcasecmp((string) ($u['number'] ?? ''), $loginId) === 0
                || (AdminPreview::hasRole($u, 'mahasiswa') && in_array($normalizedLogin, ['ahmad.maulana@student.test', 'ahmad@example.test', '231011401234']));
        });

        $previewUser = $user !== null;

        if ($user) {
            $user['role'] = $this->roleForUser($user, $selectedRole);
        }

        $dbUser = null;
        if (Schema::hasTable('users')) {
            try {
                $dbUser = User::with('role')
                    ->where(function ($query) use ($loginId) {
                        $query->whereRaw('LOWER(email) = ?', [mb_strtolower($loginId)])
                            ->orWhere('nim_nidn', $loginId);
                    })
                    ->first();

                if (! $user && $dbUser) {
                    $roleName = $dbUser->role?->name ?? ($dbUser->hasRole(Role::DOSEN) ? 'dosen' : 'mahasiswa');
                    $user = [
                        'id' => $dbUser->id,
                        'name' => $dbUser->name,
                        'email' => $dbUser->email,
                        'number' => $dbUser->nim_nidn,
                        'role' => $roleName,
                        'status' => 'aktif',
                    ];
                }
            } catch (\Throwable) {
            }
        }

        if (! $user) {
            return back()->withErrors(['login_id' => 'Kredensial akun tidak terdaftar pada sistem institusi.'])->withInput();
        }

        if (! $dbUser && Schema::hasTable('users')) {
            $dbUser = User::where(function ($query) use ($user) {
                $query->when(! empty($user['email']), fn ($q) => $q->whereRaw('LOWER(email) = ?', [mb_strtolower($user['email'])]))
                    ->when(! empty($user['number']), fn ($q) => $q->orWhere('nim_nidn', $user['number']));
            })->first();
        }

        $passwordValid = $dbUser
            ? ($password !== '' && Hash::check($password, (string) $dbUser->password))
            : ($previewUser && ($password === '' || in_array($password, ['password', 'secret'], true)));

        if (! $passwordValid) {
            return back()->withErrors(['password' => 'Kata sandi tidak sesuai.'])->withInput($request->only('login_id'));
        }

        return $this->loginAsUser($user, "Selamat datang kembali, {$user['name']}!");
    }

    public function switchRole(Request $request, string $role)
    {
        abort_unless($this->demoMode(), 404);
        abort_unless(in_array($role, ['mahasiswa', 'dosen', 'admin', 'admin_prodi']), 404);

        if (Schema::hasTable('users')) {
            try {
                if ($role === 'dosen') {
                    $dbUser = User::with('role')->whereHas('role', fn ($q) => $q->where('name', 'dosen'))->first();
                    if ($dbUser) {
                        Auth::login($dbUser);
                        session(['auth_user' => [
                            'id' => $dbUser->id,
                            'name' => $dbUser->name,
                            'email' => $dbUser->email,
                            'number' => $dbUser->nim_nidn,
                            'role' => 'dosen',
                            'status' => 'aktif',
                        ]]);

                        return redirect()->route('dosen.dashboard')->with('notice', 'Beralih ke peran Dosen.');
                    }
                }

                $dbUser = User::with('role')
                    ->whereHas('role', fn ($q) => $q->where('name', $role))
                    ->first();

                if ($dbUser) {
                    Auth::login($dbUser);
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
                        'dosen' => 'Dosen',
                        'admin_prodi' => 'Admin Prodi',
                        'admin' => 'Admin Sistem',
                        default => ucfirst($role),
                    };

                    return $this->redirectForRole($role, "Beralih ke peran {$roleLabel}.");
                }
            } catch (\Throwable) {
            }
        }

        $users = AdminPreview::users();
        $user = collect($users)->first(fn ($candidate) => AdminPreview::hasRole($candidate, $role));

        if (! $user) {
            $user = match ($role) {
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

        $user['role'] = $this->roleForUser($user, $role);

        session(['auth_user' => $user]);

        return $this->redirectForRole($role, "Beralih ke peran {$role} ({$user['name']}).");
    }

    public function logout(Request $request)
    {
        abort_if(! $this->demoMode() && ! $request->isMethod('post'), 405);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function loginAsUser(array $user, string $message)
    {
        session(['auth_user' => $user]);

        if (Schema::hasTable('users')) {
            try {
                $dbUser = User::with('role')
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
                    $dbUser = User::with('role')
                        ->whereHas('role', fn ($q) => $q->where('name', $user['role']))
                        ->first();
                }

                if ($dbUser) {
                    Auth::login($dbUser);
                }
            } catch (\Throwable) {
            }
        }

        request()->session()->regenerate();

        return $this->redirectForRole($user['role'], $message);
    }

    private function authenticateDatabaseUser(Request $request)
    {
        $credentials = $request->validate([
            'login_id' => 'required|string|max:254',
            'password' => 'required|string|max:1024',
        ]);
        $loginId = trim($credentials['login_id']);
        $user = User::with('role')
            ->where(function ($query) use ($loginId) {
                $query->whereRaw('LOWER(email) = ?', [mb_strtolower($loginId)])
                    ->orWhere('nim_nidn', $loginId);
            })
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['login_id' => 'Kredensial tidak sesuai.'])
                ->onlyInput('login_id');
        }

        $role = $user->role?->name;
        abort_unless(in_array($role, ['mahasiswa', 'dosen', 'admin_prodi', 'admin'], true), 403, 'Akun belum memiliki peran yang didukung.');

        Auth::login($user);
        session(['auth_user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'number' => $user->nim_nidn,
            'role' => $role,
            'status' => 'aktif',
        ]]);
        $request->session()->regenerate();

        return $this->redirectForRole($role, "Selamat datang kembali, {$user->name}!");
    }

    private function demoMode(): bool
    {
        return (bool) config('app.demo_mode') && app()->environment(['local', 'testing']);
    }

    private function roleForUser(array $user, ?string $requestedRole = null): string
    {
        $roles = array_values(array_unique($user['roles'] ?? [$user['role'] ?? 'mahasiswa']));

        if ($requestedRole && in_array($requestedRole, $roles, true)) {
            return $requestedRole;
        }

        return in_array($user['role'] ?? null, $roles, true) ? $user['role'] : ($roles[0] ?? 'mahasiswa');
    }

    private function redirectForRole(string $role, string $message)
    {
        return match ($role) {
            'admin_prodi' => redirect()->route('admin-prodi.dashboard')->with('notice', $message),
            'dosen' => redirect()->route('dosen.dashboard')->with('notice', $message),
            'admin' => redirect()->route('admin.page', 'dashboard')->with('notice', $message),
            default => redirect()->route('mahasiswa.dashboard')->with('notice', $message),
        };
    }
}
