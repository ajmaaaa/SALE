<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $demoMode = config('app.demo_mode') && app()->environment(['local', 'testing']);
        $sessionRole = $demoMode ? session('auth_user.role') : null;

        if (! $user && ! is_string($sessionRole)) {
            return redirect()->guest(route('login'));
        }

        $hasRole = is_string($sessionRole)
            ? in_array($sessionRole, $roles, true)
            : collect($roles)->contains(fn (string $role): bool => $user->hasRole($role));

        if (! $hasRole) {
            $isDosen = ($user && ($user->hasRole('dosen') || $user->hasRole('kaprodi')))
                || in_array($sessionRole, ['dosen', 'kaprodi'], true);
            if ($isDosen && $request->is('mahasiswa/course/*')) {
                $courseId = $request->route('course');
                if ($courseId) {
                    return redirect()->route('dosen.course.show', $courseId);
                }
            }
        }

        abort_unless(
            $hasRole,
            403,
            'Akses ditolak untuk peran akun ini.'
        );

        return $next($request);
    }
}
