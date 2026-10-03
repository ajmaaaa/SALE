<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->guest(route('login'));
        }
        // Akun lama yang dibuat sebelum kolom is_active tersedia dapat bernilai
        // null. Hanya nilai false/0 yang merupakan penonaktifan eksplisit.
        abort_if($user->is_active === false, 403, 'Akun ini sedang dinonaktifkan.');

        if (SystemSetting::valueFor('maintenance_mode', '0') === '1' && ! $user->hasRole('admin')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login_id' => 'Sistem sedang dalam masa pemeliharaan. Hanya Administrator yang dapat masuk.',
            ]);
        }

        $hasRole = collect($roles)->contains(fn (string $role): bool => $user->hasRole($role));

        if (! $hasRole) {
            $isDosen = $user->hasRole('dosen');
            if ($isDosen && $request->routeIs('mahasiswa.course.show')) {
                $courseId = $request->route('course');
                if ($courseId) {
                    return redirect()->route('dosen.course.show', $courseId);
                }
            }

            if ($user->hasRole('admin_prodi') && $request->routeIs('mahasiswa.join-kelas*')) {
                return redirect()->route('admin-prodi.akademik.kelas')->with('notice', 'Tautan pendaftaran ini diperuntukkan bagi mahasiswa atau dosen.');
            }

            if ($user->hasRole('admin') && $request->routeIs('mahasiswa.join-kelas*')) {
                return redirect()->route('admin.page', 'dashboard')->with('notice', 'Tautan pendaftaran ini diperuntukkan bagi mahasiswa atau dosen.');
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
