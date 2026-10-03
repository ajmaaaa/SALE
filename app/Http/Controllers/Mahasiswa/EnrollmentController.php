<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\ClassEnrollmentAppeal;
use App\Models\ClassSection;
use App\Models\Role;
use App\Models\User;
use App\Services\ClassEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function __construct(
        private ClassEnrollmentService $enrollment,
    ) {}

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

        if (! $isDosen && $this->enrollment->isBlockedFromJoining($section, $user->id)) {
            return $this->blockedView($section, $user);
        }

        if ($section->isArchived() && ! ($isDosen ? $user->can('manage', $section) : $section->students()->where('users.id', $user->id)->exists())) {
            return $this->result($section, 'archived', $this->archivedMessage($section), ['isDosen' => $isDosen]);
        }

        $enrollmentRecord = ! $isDosen ? $this->enrollment->record($section->id, $user->id) : null;
        $isKicked = $enrollmentRecord && $enrollmentRecord->status === ClassEnrollmentService::STATUS_KICKED;

        $alreadyEnrolled = $isDosen
            ? $user->can('manage', $section)
            : $section->students()->where('users.id', $user->id)->exists();

        $isFull = ! $isDosen
            && ! $alreadyEnrolled
            && $section->capacity
            && $section->students()->count() >= $section->capacity;

        return view('mahasiswa.join-kelas-confirm', [
            'section' => $section,
            'code' => $section->enrollment_code,
            'isDosen' => $isDosen,
            'alreadyEnrolled' => $alreadyEnrolled,
            'isFull' => $isFull,
            'enrollmentRecord' => $enrollmentRecord,
            'isKicked' => $isKicked,
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

        // Mahasiswa: delegasi ke ClassEnrollmentService yang menerapkan aturan 2x kick.
        if (! $isDosen) {
            $section = ClassSection::where('enrollment_code', strtoupper(trim($code)))->firstOrFail();

            if ($this->enrollment->isBlockedFromJoining($section, $user->id)) {
                return $this->blockedView($section, $user);
            }

            $result = $this->enrollment->join($section, $user);

            $section->load(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping', 'dosenAnggota'])->loadCount('students');

            $message = match ($result) {
                'success' => 'Selamat! Anda berhasil bergabung ke kelas '.$section->display_code.' ('.$section->mataKuliah->name.').',
                'rejoined', 'rejoined_after_kick' => 'Anda berhasil bergabung kembali ke kelas '.$section->display_code.'.',
                'already_enrolled' => 'Anda sudah terdaftar di kelas '.$section->display_code.' - '.$section->mataKuliah->name.'.',
                'full' => 'Kapasitas kelas telah penuh ('.$section->capacity.' mahasiswa). Hubungi dosen pengampu atau admin prodi.',
                'archived' => $this->archivedMessage($section),
                default => 'Terjadi kesalahan. Silakan coba lagi.',
            };

            if (in_array($result, ['success', 'rejoined', 'rejoined_after_kick', 'already_enrolled'], true)) {
                return redirect()->route('mahasiswa.course.show', $section->id)->with('notice', $message);
            }

            return $this->result($section, $result, $message, ['isDosen' => false]);
        }

        // Dosen: logika asli (slot ketua / pendamping)
        [$section, $status] = DB::transaction(function () use ($code, $user): array {
            $section = ClassSection::where('enrollment_code', strtoupper(trim($code)))
                ->lockForUpdate()
                ->firstOrFail();

            if ($user->can('manage', $section)) {
                return [$section, 'already_enrolled'];
            }

            if (! $section->dosen_id) {
                $section->update(['dosen_id' => $user->id]);

                return [$section, 'joined_as_lead'];
            }

            if (! $section->dosen_pendamping_id) {
                $section->update(['dosen_pendamping_id' => $user->id]);
                $section->dosenAnggota()->syncWithoutDetaching([$user->id]);

                return [$section, 'joined_as_assistant'];
            }

            return [$section, 'lecturer_slots_full'];
        }, 3);

        $section->load(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping', 'dosenAnggota'])->loadCount('students');

        $message = match ($status) {
            'joined_as_lead' => 'Anda berhasil bergabung sebagai Dosen Ketua di kelas '.$section->display_code.'.',
            'joined_as_assistant' => 'Anda berhasil bergabung sebagai Dosen Pendamping di kelas '.$section->display_code.'.',
            'already_enrolled' => 'Anda sudah terdaftar di kelas '.$section->display_code.' - '.$section->mataKuliah->name.'.',
            default => 'Kelas ini sudah memiliki Dosen Ketua dan Dosen Pendamping.',
        };

        if (in_array($status, ['joined_as_lead', 'joined_as_assistant', 'already_enrolled'], true)) {
            return redirect()->route('dosen.course.show', $section->id)->with('notice', $message);
        }

        $resultStatus = $status === 'lecturer_slots_full' ? 'full' : $status;

        return $this->result($section, $resultStatus, $message, ['isDosen' => true]);
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

        if (! $isDosen) {
            $section = ClassSection::where('enrollment_code', $code)->firstOrFail();

            if ($this->enrollment->isBlockedFromJoining($section, $user->id)) {
                // Redirect ke halaman konfirmasi agar mahasiswa bisa lihat info banding.
                return redirect()->route('mahasiswa.join-kelas', $code);
            }

            // Jika mahasiswa berstatus dikeluarkan (kicked), arahkan ke halaman konfirmasi
            // agar mahasiswa melihat card alasan pengeluaran dari dosen terlebih dahulu sebelum mendaftar ulang.
            $record = $this->enrollment->record($section->id, $user->id);
            if ($record && $record->status === ClassEnrollmentService::STATUS_KICKED) {
                return redirect()->route('mahasiswa.join-kelas', $code);
            }

            $result = $this->enrollment->join($section, $user);
            $section->load(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping', 'dosenAnggota']);

            // TODO: notifikasi dosen saat mahasiswa rejoin (sistem notifikasi push belum tersedia).

            if (in_array($result, ['full', 'archived'])) {
                return back()->with('join_error', match ($result) {
                    'full' => 'Kapasitas kelas telah penuh ('.$section->capacity.' mahasiswa). Hubungi dosen pengampu atau admin prodi.',
                    'archived' => $this->archivedMessage($section),
                    default => 'Tidak dapat bergabung ke kelas ini.',
                })->withInput();
            }

            $message = match ($result) {
                'success' => 'Selamat! Anda berhasil bergabung ke kelas '.$section->display_code.' ('.$section->mataKuliah->name.').',
                'rejoined', 'rejoined_after_kick' => 'Anda berhasil bergabung kembali ke kelas '.$section->display_code.'.',
                'already_enrolled' => 'Anda sudah terdaftar di kelas '.$section->display_code.' - '.$section->mataKuliah->name.'.',
                default => 'Terjadi kesalahan. Silakan coba lagi.',
            };

            return redirect()->route('mahasiswa.course.show', $section->id)->with('notice', $message);
        }

        // Dosen
        [$section, $status] = DB::transaction(function () use ($code, $user): array {
            $section = ClassSection::where('enrollment_code', $code)
                ->lockForUpdate()
                ->firstOrFail();

            if ($user->can('manage', $section)) {
                return [$section, 'already_enrolled'];
            }

            if (! $section->dosen_id) {
                $section->update(['dosen_id' => $user->id]);

                return [$section, 'joined_as_lead'];
            }

            if (! $section->dosen_pendamping_id) {
                $section->update(['dosen_pendamping_id' => $user->id]);
                $section->dosenAnggota()->syncWithoutDetaching([$user->id]);

                return [$section, 'joined_as_assistant'];
            }

            return [$section, 'lecturer_slots_full'];
        }, 3);

        $section->load(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping', 'dosenAnggota']);

        if ($status === 'lecturer_slots_full') {
            return back()->with('join_error', 'Kelas ini sudah memiliki Dosen Ketua dan Dosen Pendamping.')->withInput();
        }

        $message = match ($status) {
            'joined_as_lead' => 'Berhasil bergabung sebagai Dosen Ketua di kelas '.$section->display_code.' ('.$section->mataKuliah->name.').',
            'joined_as_assistant' => 'Berhasil bergabung sebagai Dosen Pendamping di kelas '.$section->display_code.' ('.$section->mataKuliah->name.').',
            'already_enrolled' => 'Anda sudah terdaftar di kelas '.$section->display_code.' - '.$section->mataKuliah->name.'.',
            default => 'Selamat! Anda berhasil bergabung ke kelas '.$section->display_code.' ('.$section->mataKuliah->name.').',
        };

        $targetRoute = 'dosen.course.show';

        return redirect()->route($targetRoute, $section->id)->with('notice', $message);
    }

    /**
     * Tahap 3 — Mahasiswa keluar dari kelas (PRD Kondisi 2).
     */
    public function leave(Request $request, int $course): RedirectResponse
    {
        $user = $this->activeUser();
        abort_unless($user?->hasRole(Role::MAHASISWA), 403);

        $section = ClassSection::findOrFail($course);

        $result = $this->enrollment->leave($section, $user);

        $message = match ($result) {
            'removed', 'dropped' => 'Anda telah keluar dari kelas '.$section->display_code.'.',
            'not_enrolled' => 'Anda tidak terdaftar di kelas ini.',
            'archived' => 'Kelas sudah diarsipkan, tidak dapat melakukan perubahan.',
            default => 'Tidak dapat memproses permintaan. Coba lagi.',
        };

        if (in_array($result, ['removed', 'dropped'])) {
            return redirect()->route('mahasiswa.course.index')->with('notice', $message);
        }

        return back()->withErrors(['leave' => $message]);
    }

    /**
     * Tahap 3 — Mahasiswa ajukan Verifikasi Peserta setelah 2x kick (PRD Kondisi 5).
     */
    public function submitAppeal(Request $request, int $course): RedirectResponse
    {
        $user = $this->activeUser();
        abort_unless($user?->hasRole(Role::MAHASISWA), 403);

        $section = ClassSection::findOrFail($course);

        if (! $this->enrollment->canAppeal($section, $user->id)) {
            return back()->withErrors(['appeal' => 'Tidak dapat mengajukan verifikasi saat ini.']);
        }

        $request->validate([
            'student_notes' => ['required', 'string', 'min:20', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ], [
            'student_notes.required' => 'Penjelasan / alasan sanggahan wajib diisi.',
            'student_notes.min' => 'Penjelasan sanggahan minimal 20 karakter.',
            'attachment.max' => 'Ukuran berkas pendukung maksimal 2MB.',
            'attachment.mimes' => 'Format berkas pendukung harus PDF, JPG, JPEG, atau PNG.',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('appeals', 'local');
        }

        ClassEnrollmentAppeal::create([
            'class_section_id' => $section->id,
            'mahasiswa_id' => $user->id,
            'status' => ClassEnrollmentAppeal::STATUS_PENDING,
            'student_notes' => trim($request->input('student_notes')),
            'attachment_path' => $attachmentPath,
        ]);

        // TODO: notifikasi push ke Admin Prodi saat sistem push tersedia.

        return back()->with('notice', 'Permohonan verifikasi peserta berhasil diajukan. Admin Prodi akan segera memproses permohonan Anda.');
    }

    private function section(string $code): ?ClassSection
    {
        return ClassSection::where('enrollment_code', strtoupper(trim($code)))
            ->with(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping', 'dosenAnggota'])
            ->withCount('students')
            ->first();
    }

    private function activeUser(): ?User
    {
        return Auth::guard('web')->user();
    }

    private function result(ClassSection $section, string $status, string $message, array $extra = []): View
    {
        return view('mahasiswa.join-kelas-result', array_merge(compact('section', 'status', 'message'), $extra));
    }

    /**
     * Halaman peringatan mahasiswa yang diblokir karena 2x kick.
     * Menampilkan informasi banding dan status permohonan terakhir.
     */
    private function blockedView(ClassSection $section, User $user): View
    {
        $latestAppeal = $this->enrollment->latestAppeal($section->id, $user->id);
        $canAppeal = $this->enrollment->canAppeal($section, $user->id);

        return view('mahasiswa.join-kelas-result', [
            'section' => $section,
            'status' => 'blocked',
            'message' => 'Anda telah dikeluarkan dua kali dari kelas ini. Ajukan Verifikasi Peserta kepada Admin Prodi untuk dapat bergabung kembali.',
            'isDosen' => false,
            'latestAppeal' => $latestAppeal,
            'canAppeal' => $canAppeal,
        ]);
    }

    private function archivedMessage(ClassSection $section): string
    {
        return "Kelas {$section->display_code} sudah diarsipkan dan tidak menerima pendaftaran baru.";
    }
}
