<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;
use App\Models\User;

class EnsureDosenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Use the real authenticated user when available, with a verified
        // session fallback for the prototype's role switcher.
        $user = auth()->user();

        if (! $user && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $user = User::with('role')
                ->where(function ($query) use ($sessionUser) {
                    $query->where('email', $sessionUser['email'] ?? '')
                        ->orWhere('nim_nidn', $sessionUser['number'] ?? '');
                })
                ->first();

            if ($user) {
                \Illuminate\Support\Facades\Auth::login($user);
            }
        }

        if (! $user) {
            return redirect()->route('dosen.login');
        }

        if (! $user->hasRole(Role::DOSEN)) {
            abort(403, 'Akses ditolak. Halaman ini khusus Dosen.');
        }

        return $next($request);
    }
}