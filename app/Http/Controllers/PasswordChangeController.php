<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    /**
     * Tampilkan form ganti password wajib (login pertama kali).
     */
    public function show(): View
    {
        abort_unless(auth()->check(), 403);

        return view('auth.password-change');
    }

    /**
     * Proses penggantian password wajib.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $request->validate([
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
                // Password baru tidak boleh sama dengan password lama
                function ($attribute, $value, $fail) use ($user) {
                    if (Hash::check($value, $user->password)) {
                        $fail('Password baru tidak boleh sama dengan password sementara. Pilih password yang berbeda.');
                    }
                },
            ],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 12 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user->update([
            'password' => Hash::make($request->input('password')),
            'must_change_password' => false,
        ]);

        $role = $user->role?->name ?? 'mahasiswa';

        $destination = match ($role) {
            'dosen' => route('dosen.dashboard'),
            'admin_prodi' => route('admin-prodi.dashboard'),
            'admin' => route('admin.page', 'dashboard'),
            default => route('mahasiswa.dashboard'),
        };

        return redirect($destination)
            ->with('notice', 'Password berhasil diperbarui. Selamat datang!');
    }
}
