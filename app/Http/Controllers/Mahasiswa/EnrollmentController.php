<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    /**
     * Mahasiswa join kelas via Link / Barcode QR Code.
     * Route: GET /join-kelas/{code}
     */
    public function join(Request $request, string $code)
    {
        $code = strtoupper(trim($code));
        $section = ClassSection::where('enrollment_code', $code)
            ->with(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping'])
            ->withCount('students')
            ->first();

        if (! $section) {
            abort(404, 'Kode kelas tidak valid atau kelas tidak ditemukan.');
        }

        // Resolusi user aktif (Auth / Session Persona)
        $user = Auth::guard('web')->user();
        if (! $user && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

        if (! $user) {
            session(['url.intended' => route('mahasiswa.join-kelas', $code)]);

            return redirect()->route('login')
                ->with('notice', 'Silakan masuk terlebih dahulu untuk bergabung ke kelas perkuliahan ini.');
        }

        // Integrasi jika yang membuka adalah Dosen
        if ($user->hasRole(Role::DOSEN)) {
            $isKetua = ($section->dosen_id === $user->id);
            $isPendamping = ($section->dosen_pendamping_id === $user->id);

            if ($isKetua || $isPendamping) {
                $roleLabel = $isKetua ? 'Dosen Ketua (Koordinator)' : 'Dosen Wakil (Pendamping)';

                return view('mahasiswa.join-kelas-result', [
                    'section' => $section,
                    'status' => 'already_enrolled',
                    'is_dosen' => true,
                    'message' => "Anda sudah terdaftar sebagai {$roleLabel} di kelas {$section->display_code} - {$section->mataKuliah->name}.",
                ]);
            }

            // Daftarkan dosen ke slot yang masih kosong
            if (empty($section->dosen_id)) {
                $section->dosen_id = $user->id;
                $section->save();

                return view('mahasiswa.join-kelas-result', [
                    'section' => $section->fresh(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping']),
                    'status' => 'success',
                    'is_dosen' => true,
                    'message' => "Selamat! Anda berhasil bergabung sebagai Dosen Ketua di kelas {$section->display_code} ({$section->mataKuliah->name}).",
                ]);
            }

            if (empty($section->dosen_pendamping_id)) {
                $section->dosen_pendamping_id = $user->id;
                $section->save();

                return view('mahasiswa.join-kelas-result', [
                    'section' => $section->fresh(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping']),
                    'status' => 'success',
                    'is_dosen' => true,
                    'message' => "Selamat! Anda berhasil bergabung sebagai Dosen Pendamping di kelas {$section->display_code} ({$section->mataKuliah->name}).",
                ]);
            }

            // Jika kedua slot dosen sudah terisi oleh dosen lain
            return view('mahasiswa.join-kelas-result', [
                'section' => $section,
                'status' => 'full',
                'is_dosen' => true,
                'message' => "Kelas {$section->display_code} telah memiliki Dosen Ketua (" . ($section->dosen?->name ?? '-') . ") dan Dosen Pendamping (" . ($section->dosenPendamping?->name ?? '-') . ").",
            ]);
        }

        // Jika yang membuka bukan mahasiswa dan bukan dosen (misal admin/kaprodi)
        if (! $user->hasRole(Role::MAHASISWA)) {
            return view('mahasiswa.join-kelas-result', [
                'section' => $section,
                'status' => 'not_student',
                'message' => 'Anda saat ini masuk dengan peran ' . ($user->role?->label ?? $user->role?->name ?? 'Staf') . '. Tautan pendaftaran ini diperuntukkan bagi akun Mahasiswa dan Dosen.',
            ]);
        }

        // Cek apakah mahasiswa sudah terdaftar di kelas ini
        $alreadyEnrolled = $section->students()->where('users.id', $user->id)->exists();

        if ($alreadyEnrolled) {
            return view('mahasiswa.join-kelas-result', [
                'section' => $section,
                'status' => 'already_enrolled',
                'message' => 'Anda sudah terdaftar di kelas ' . $section->display_code . ' - ' . $section->mataKuliah->name . '.',
            ]);
        }

        // Cek kuota kapasitas kelas
        if ($section->capacity && $section->students_count >= $section->capacity) {
            return view('mahasiswa.join-kelas-result', [
                'section' => $section,
                'status' => 'full',
                'message' => 'Kapasitas kelas telah penuh (' . $section->capacity . ' mahasiswa). Hubungi dosen pengampu atau admin prodi.',
            ]);
        }

        // Daftarkan mahasiswa ke kelas (auto-enroll)
        $section->students()->attach($user->id);

        return view('mahasiswa.join-kelas-result', [
            'section' => $section->fresh(['mataKuliah', 'semester', 'dosen']),
            'status' => 'success',
            'message' => 'Selamat! Anda berhasil bergabung ke kelas ' . $section->display_code . ' (' . $section->mataKuliah->name . ').',
        ]);
    }
}
