<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                try {
                    $user = User::with('role')
                        ->where(function ($query) use ($sessionUser) {
                            $query->where('email', $sessionUser['email'] ?? '')
                                ->orWhere('nim_nidn', $sessionUser['number'] ?? '');
                        })
                        ->first();

                    if (! $user && ($sessionUser['role'] ?? '') === Role::ADMIN) {
                        $user = User::with('role')->whereHas('role', fn ($q) => $q->where('name', Role::ADMIN))->first();
                    }

                    if ($user && $user->hasRole(Role::ADMIN)) {
                        \Illuminate\Support\Facades\Auth::login($user);
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        if (! $user && ! is_array(session('auth_user'))) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $isAdmin = ($user && $user->hasRole(Role::ADMIN))
            || (is_array(session('auth_user')) && (session('auth_user')['role'] ?? '') === Role::ADMIN);

        if (! $isAdmin) {
            abort(403, 'Akses ditolak. Halaman ini khusus Administrator Sistem.');
        }

        return $next($request);
    }
}
