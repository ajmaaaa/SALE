<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function confirm(Request $request, string $code): View|RedirectResponse
    {
        $user = $this->activeUser();

        if (! $user) {
            session(['url.intended' => route('mahasiswa.join-kelas', strtoupper(trim($code)))]);

            return redirect()->route('login')
                ->with('notice', 'Silakan masuk terlebih dahulu untuk bergabung ke kelas perkuliahan ini.');
        }

        $isDosen = $user->hasRole(Role::DOSEN);
        if (! $user->hasRole(Role::MAHASISWA) && ! $isDosen) {
            abort(403, 'Pendaftaran kelas hanya untuk mahasiswa dan dosen.');
        }

        $section = $this->section($code);
        if (! $section) {
            return redirect()->route($isDosen ? 'dosen.course.index' : 'mahasiswa.course.index')
                ->with('join_error', 'Kelas tidak ditemukan. Periksa kembali kode kelas yang dimasukkan.');
        }

        return view('mahasiswa.join-kelas-confirm', [
            'section' => $section,
            'code' => $section->enrollment_code,
            'isDosen' => $isDosen,
            'alreadyEnrolled' => $isDosen
                ? in_array($user->id, [$section->dosen_id, $section->dosen_pendamping_id], true)
                : $section->students()->where('users.id', $user->id)->exists(),
        ]);
    }

    public function join(Request $request, string $code): View|RedirectResponse
    {
        $user = $this->activeUser();
        if (! $user) {
            session(['url.intended' => route('mahasiswa.join-kelas', strtoupper(trim($code)))]);

            return redirect()->route('login');
        }

        abort_unless($user->hasRole(Role::MAHASISWA) || $user->hasRole(Role::DOSEN), 403, 'Pendaftaran kelas hanya untuk mahasiswa dan dosen.');

        $isDosen = $user->hasRole(Role::DOSEN);
        if (! ClassSection::where('enrollment_code', strtoupper(trim($code)))->exists()) {
            return redirect()->route($isDosen ? 'dosen.course.index' : 'mahasiswa.course.index')
                ->with('join_error', 'Kelas tidak ditemukan. Periksa kembali kode kelas yang dimasukkan.');
        }

        [$section, $status] = DB::transaction(function () use ($code, $user): array {
            $section = ClassSection::where('enrollment_code', strtoupper(trim($code)))
                ->lockForUpdate()
                ->firstOrFail();

            if ($user->hasRole(Role::DOSEN)) {
                if (in_array($user->id, [$section->dosen_id, $section->dosen_pendamping_id], true)) {
                    return [$section, 'already_enrolled'];
                }

                if (! $section->dosen_id) {
                    $section->update(['dosen_id' => $user->id]);

                    return [$section, 'joined_as_lead'];
                }

                if (! $section->dosen_pendamping_id) {
                    $section->update(['dosen_pendamping_id' => $user->id]);

                    return [$section, 'joined_as_assistant'];
                }

                return [$section, 'lecturer_slots_full'];
            }

            if ($section->students()->where('users.id', $user->id)->exists()) {
                return [$section, 'already_enrolled'];
            }

            if ($section->capacity && $section->students()->count() >= $section->capacity) {
                return [$section, 'full'];
            }

            $section->students()->syncWithoutDetaching([$user->id]);

            return [$section, 'success'];
        }, 3);

        $section->load(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping'])->loadCount('students');

        $message = match ($status) {
            'success' => 'Selamat! Anda berhasil bergabung ke kelas '.$section->display_code.' ('.$section->mataKuliah->name.').',
            'joined_as_lead' => 'Anda berhasil bergabung sebagai Dosen Ketua di kelas '.$section->display_code.'.',
            'joined_as_assistant' => 'Anda berhasil bergabung sebagai Dosen Pendamping di kelas '.$section->display_code.'.',
            'already_enrolled' => 'Anda sudah terdaftar di kelas '.$section->display_code.' - '.$section->mataKuliah->name.'.',
            'lecturer_slots_full' => 'Kelas ini sudah memiliki Dosen Ketua dan Dosen Pendamping.',
            default => 'Kapasitas kelas telah penuh ('.$section->capacity.' mahasiswa). Hubungi dosen pengampu atau admin prodi.',
        };

        $resultStatus = in_array($status, ['joined_as_lead', 'joined_as_assistant'], true)
            ? 'success'
            : ($status === 'lecturer_slots_full' ? 'full' : $status);

        return $this->result($section, $resultStatus, $message, ['isDosen' => $isDosen]);
    }

    public function joinDirect(Request $request): RedirectResponse
    {
        $user = $this->activeUser();
        if (! $user) {
            return redirect()->route('login')
                ->with('notice', 'Silakan masuk terlebih dahulu untuk bergabung ke kelas perkuliahan ini.');
        }

        $isDosen = $user->hasRole(Role::DOSEN);
        if (! $user->hasRole(Role::MAHASISWA) && ! $isDosen) {
            abort(403, 'Pendaftaran kelas hanya untuk mahasiswa dan dosen.');
        }

        $rawCode = (string) $request->input('code', '');
        $code = trim($rawCode);
        if (str_contains($code, '/join-kelas/')) {
            $code = explode('?', explode('#', array_reverse(explode('/join-kelas/', $code))[0])[0])[0];
        }
        $code = strtoupper(trim($code));

        if ($code === '' || ! ClassSection::where('enrollment_code', $code)->exists()) {
            return back()
                ->with('join_error', 'Kelas tidak ditemukan. Periksa kembali kode kelas yang dimasukkan.')
                ->withInput();
        }

        [$section, $status] = DB::transaction(function () use ($code, $user): array {
            $section = ClassSection::where('enrollment_code', $code)
                ->lockForUpdate()
                ->firstOrFail();

            if ($user->hasRole(Role::DOSEN)) {
                if (in_array($user->id, [$section->dosen_id, $section->dosen_pendamping_id], true)) {
                    return [$section, 'already_enrolled'];
                }

                if (! $section->dosen_id) {
                    $section->update(['dosen_id' => $user->id]);

                    return [$section, 'joined_as_lead'];
                }

                if (! $section->dosen_pendamping_id) {
                    $section->update(['dosen_pendamping_id' => $user->id]);

                    return [$section, 'joined_as_assistant'];
                }

                return [$section, 'lecturer_slots_full'];
            }

            if ($section->students()->where('users.id', $user->id)->exists()) {
                return [$section, 'already_enrolled'];
            }

            if ($section->capacity && $section->students()->count() >= $section->capacity) {
                return [$section, 'full'];
            }

            $section->students()->syncWithoutDetaching([$user->id]);

            return [$section, 'success'];
        }, 3);

        $section->load(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping']);

        if ($status === 'lecturer_slots_full') {
            return back()->with('join_error', 'Kelas ini sudah memiliki Dosen Ketua dan Dosen Pendamping.')->withInput();
        }

        if ($status === 'full') {
            return back()->with('join_error', 'Kapasitas kelas telah penuh ('.$section->capacity.' mahasiswa). Hubungi dosen pengampu atau admin prodi.')->withInput();
        }

        $message = match ($status) {
            'joined_as_lead' => 'Berhasil bergabung sebagai Dosen Ketua di kelas '.$section->display_code.' ('.$section->mataKuliah->name.').',
            'joined_as_assistant' => 'Berhasil bergabung sebagai Dosen Pendamping di kelas '.$section->display_code.' ('.$section->mataKuliah->name.').',
            'already_enrolled' => 'Anda sudah terdaftar di kelas '.$section->display_code.' - '.$section->mataKuliah->name.'.',
            default => 'Selamat! Anda berhasil bergabung ke kelas '.$section->display_code.' ('.$section->mataKuliah->name.').',
        };

        $targetRoute = $isDosen ? 'dosen.course.show' : 'mahasiswa.course.show';

        return redirect()->route($targetRoute, $section->id)->with('notice', $message);
    }

    private function section(string $code): ?ClassSection
    {
        return ClassSection::where('enrollment_code', strtoupper(trim($code)))
            ->with(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping'])
            ->withCount('students')
            ->first();
    }

    private function activeUser(): ?User
    {
        $user = Auth::guard('web')->user();
        if ($user || ! config('app.demo_mode') || ! app()->environment(['local', 'testing'])) {
            return $user;
        }

        $sessionUser = session('auth_user');
        if (! is_array($sessionUser)) {
            return null;
        }

        $user = User::where('email', $sessionUser['email'] ?? '')
            ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
            ->first();

        if ($user) {
            Auth::guard('web')->setUser($user);
        }

        return $user;
    }

    private function result(ClassSection $section, string $status, string $message, array $extra = []): View
    {
        return view('mahasiswa.join-kelas-result', array_merge(compact('section', 'status', 'message'), $extra));
    }
}
