<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminProdiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        abort_if($user->is_active === false, 403, 'Akun ini sedang dinonaktifkan.');

        if (SystemSetting::valueFor('maintenance_mode', '0') === '1' && ! $user->hasRole(Role::ADMIN)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login_id' => 'Sistem sedang dalam masa pemeliharaan. Hanya Administrator yang dapat masuk.',
            ]);
        }

        if (! $user->hasRole(Role::ADMIN_PRODI) && ! $user->hasRole(Role::ADMIN)) {
            abort(403, 'Akses ditolak. Halaman ini khusus Admin Program Studi.');
        }

        if ($user->hasRole(Role::ADMIN_PRODI) && ! $user->hasRole(Role::ADMIN)) {
            $managedProdiId = $user->managing_prodi_id ?? $user->prodi_id;
            if (! $managedProdiId) {
                abort(403, 'Akun Admin Prodi belum ditugaskan ke Program Studi manapun. Silakan hubungi Administrator Sistem.');
            }
        }

        return $next($request);
    }
}
