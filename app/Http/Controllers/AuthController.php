<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $personas = [];
        if ($this->demoMode() && $this->hasUsersTable()) {
            $personas = User::query()
                ->with('role')
                ->where('is_active', true)
                ->whereHas('role', fn ($query) => $query->whereIn('name', [Role::MAHASISWA, Role::DOSEN, Role::ADMIN_PRODI, Role::ADMIN]))
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'number' => $user->nim_nidn,
                    'role' => $user->role->name,
                ])
                ->keyBy('role')
                ->all();
        }

        $defaultRole = $request->query('role', request()->is('dosen*') ? 'dosen' : 'mahasiswa');
        if (! in_array($defaultRole, ['mahasiswa', 'dosen', 'admin_prodi', 'admin'])) {
            $defaultRole = 'mahasiswa';
        }

        $isMaintenance = SystemSetting::valueFor('maintenance_mode', '0') === '1';

        return view('auth.login', compact('personas', 'defaultRole', 'isMaintenance'));
    }

    public function showLogin(Request $request)
    {
        return $this->login($request);
    }

    public function authenticate(Request $request)
    {
        if ($this->demoMode() && $request->filled('persona_id')) {
            $user = User::with('role')->where('is_active', true)->find($request->integer('persona_id'));
            abort_unless($user && in_array($user->role?->name, [Role::MAHASISWA, Role::DOSEN, Role::ADMIN_PRODI, Role::ADMIN], true), 404);

            if (SystemSetting::valueFor('maintenance_mode', '0') === '1' && ! $user->hasRole('admin')) {
                return back()->withErrors(['login_id' => 'Sistem sedang dalam masa pemeliharaan. Hanya Administrator yang dapat masuk.']);
            }

            return $this->authenticateUser($request, $user, "Masuk sebagai {$user->name}.");
        }

        return $this->authenticateDatabaseUser($request);
    }

    public function logout(Request $request)
    {
        abort_if(! $this->demoMode() && ! $request->isMethod('post'), 405);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function authenticateDatabaseUser(Request $request)
    {
        $credentials = $request->validate([
            'login_id' => 'required|string|max:254',
            'password' => 'required|string|max:1024',
        ]);
        $loginId = trim($credentials['login_id']);
        $normalizedLoginId = mb_strtolower($loginId);
        $ipAddress = (string) $request->ip();
        $identifierKey = 'login:attempt:'.hash('sha256', $normalizedLoginId.'|'.$ipAddress);
        $ipKey = 'login:ip:'.hash('sha256', $ipAddress);

        if (RateLimiter::tooManyAttempts($identifierKey, 5) || RateLimiter::tooManyAttempts($ipKey, 20)) {
            return $this->invalidLoginResponse();
        }

        $user = User::with('role')
            ->where(function ($query) use ($loginId) {
                $query->whereRaw('LOWER(email) = ?', [mb_strtolower($loginId)])
                    ->orWhere('nim_nidn', $loginId);
            })
            ->first();

        // Perform a password hash check for unknown users as well so the
        // response does not reveal account existence through timing.
        static $dummyHash;
        $passwordHash = $user?->password ?? ($dummyHash ??= Hash::make('sale-invalid-login-placeholder'));
        $passwordMatches = Hash::check($credentials['password'], $passwordHash);

        if (! $user || ! $user->is_active || ! $passwordMatches) {
            RateLimiter::hit($identifierKey, 60);
            RateLimiter::hit($ipKey, 60);

            return $this->invalidLoginResponse();
        }

        RateLimiter::clear($identifierKey);

        $role = $user->role?->name;
        abort_unless(in_array($role, ['mahasiswa', 'dosen', 'admin_prodi', 'admin'], true), 403, 'Akun belum memiliki peran yang didukung.');

        if (SystemSetting::valueFor('maintenance_mode', '0') === '1' && ! $user->hasRole('admin')) {
            return back()->withErrors(['login_id' => 'Sistem sedang dalam masa pemeliharaan. Hanya Administrator yang dapat masuk.'])->onlyInput('login_id');
        }

        return $this->authenticateUser($request, $user, "Selamat datang kembali, {$user->name}!");
    }

    private function invalidLoginResponse()
    {
        $message = 'Email/NIM/NIDN atau kata sandi tidak sesuai. Silakan coba lagi beberapa saat kemudian.';

        return back()
            ->withErrors(['login_id' => $message, 'password' => $message])
            ->onlyInput('login_id');
    }

    private function authenticateUser(Request $request, User $user, string $message)
    {
        $role = $user->role?->name;
        $intended = $request->session()->pull('url.intended') ?: session('url.intended');
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('last_user_activity', time());

        if ($intended && $intended !== route('login') && $intended !== url('/') && $intended !== url('/login')) {
            return redirect()->to($intended)->with('notice', $message);
        }

        return $this->redirectForRole($role, $message);
    }

    private function demoMode(): bool
    {
        return (bool) config('app.demo_mode') && app()->environment(['local', 'testing']);
    }

    private function hasUsersTable(): bool
    {
        try {
            return Schema::hasTable('users');
        } catch (\Throwable) {
            // Preview login must remain usable when the local database is
            // temporarily unavailable. Production authentication never uses
            // this fallback because it goes through authenticateDatabaseUser().
            return false;
        }
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
