<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paksa pengguna yang memiliki must_change_password = true
 * untuk mengganti password sebelum mengakses halaman lain.
 */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        // Jika user wajib ganti password tapi belum di halaman ganti password
        if ($user->must_change_password && ! $request->routeIs(
            'password.change*',
            'login',
            'login.post',
            'logout',
            'ai.logout'
        )) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
