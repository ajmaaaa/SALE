<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMahasiswaAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // PROTOTYPE ONLY: Accept session-based auth_user for demo purposes
        // TODO: Remove this before production - require real authentication
        if (! $user && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            if (($sessionUser['role'] ?? '') === 'mahasiswa') {
                return $next($request);
            }
        }

        if (! $user) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (! $user->hasRole(Role::MAHASISWA)) {
            abort(403, 'Akses ditolak. Halaman ini khusus Mahasiswa.');
        }

        return $next($request);
    }
}
