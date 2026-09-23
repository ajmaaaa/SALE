<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMahasiswaAuth
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

                    if (! $user && ($sessionUser['role'] ?? '') === Role::MAHASISWA) {
                        $user = User::with('role')->whereHas('role', fn ($q) => $q->where('name', Role::MAHASISWA))->first();
                    }

                    if ($user && $user->hasRole(Role::MAHASISWA)) {
                        \Illuminate\Support\Facades\Auth::login($user);
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        if (! $user && ! is_array(session('auth_user'))) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $isMahasiswa = ($user && $user->hasRole(Role::MAHASISWA))
            || (is_array(session('auth_user')) && (session('auth_user')['role'] ?? '') === Role::MAHASISWA);

        if (! $isMahasiswa) {
            $isDosen = ($user && ($user->hasRole(Role::DOSEN) || $user->hasRole(Role::KAPRODI)))
                || (is_array(session('auth_user')) && in_array(session('auth_user')['role'] ?? '', [Role::DOSEN, Role::KAPRODI], true));

            if ($isDosen && $request->is('mahasiswa/course/*')) {
                $courseId = $request->route('course');
                if ($courseId) {
                    return redirect()->route('dosen.course.show', $courseId);
                }
            }

            abort(403, 'Akses ditolak. Halaman ini khusus Mahasiswa.');
        }

        return $next($request);
    }
}
