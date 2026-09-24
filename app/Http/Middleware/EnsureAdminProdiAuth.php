<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminProdiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user
            && config('app.demo_mode')
            && app()->environment(['local', 'testing'])
            && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $user = User::with('role')
                ->where(function ($query) use ($sessionUser) {
                    $query->where('email', $sessionUser['email'] ?? '')
                        ->orWhere('nim_nidn', $sessionUser['number'] ?? '');
                })
                ->first();
            if ($user && ($user->hasRole(Role::ADMIN_PRODI) || $user->hasRole(Role::ADMIN))) {
                Auth::login($user);
            }
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
