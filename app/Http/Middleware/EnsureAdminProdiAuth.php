<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminProdiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('users')) {
            return $next($request);
        }

        $user = auth()->user();

        if (! $user && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $user = User::with('role')
                ->where(function ($query) use ($sessionUser) {
                    $query->where('email', $sessionUser['email'] ?? '')
                        ->orWhere('nim_nidn', $sessionUser['number'] ?? '');
                })
                ->first();
        }

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->hasRole(Role::ADMIN_PRODI) && ! $user->hasRole(Role::ADMIN)) {
            abort(403, 'Akses ditolak. Halaman ini khusus Admin Program Studi.');
        }

        return $next($request);
    }
}
