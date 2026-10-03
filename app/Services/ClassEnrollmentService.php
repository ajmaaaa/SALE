<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\ClassEnrollmentAppeal;
use App\Models\ClassSection;
use App\Models\StudentAssessmentCpmkScore;
use App\Models\StudentAssessmentScore;
use App\Models\Submission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aturan siklus hidup peserta kelas sesuai PRD-CLASS-LIFECYCLE-MANAGEMENT.
 *
 * Status pivot class_section_student:
 *  - enrolled     : peserta aktif (satu-satunya status yang dilihat relasi students()).
 *  - dropped_self : keluar atas kehendak sendiri (riwayat nilai tetap tersimpan).
 *  - kicked       : dikeluarkan dosen / admin prodi.
 */
class ClassEnrollmentService
{
    public const STATUS_ENROLLED = 'enrolled';
    public const STATUS_DROPPED_SELF = 'dropped_self';
    public const STATUS_KICKED = 'kicked';

    /** Setelah dikeluarkan sebanyak ini, mahasiswa tidak dapat bergabung lagi via kode. */
    public const MAX_KICKS_BEFORE_LOCK = 2;

    private const TABLE = 'class_section_student';

    public function record(int $sectionId, int $studentId, bool $lock = false): ?object
    {
        return DB::table(self::TABLE)
            ->where('class_section_id', $sectionId)
            ->where('mahasiswa_id', $studentId)
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->first();
    }

    public function isBlockedFromJoining(ClassSection $section, int $studentId): bool
    {
        $row = $this->record($section->id, $studentId);

        return $row !== null
            && $row->status === self::STATUS_KICKED
            && (int) $row->kick_count >= self::MAX_KICKS_BEFORE_LOCK;
    }

    /**
     * Mahasiswa dianggap "sudah memiliki nilai/riwayat" bila ada submisi,
     * percobaan kuis, skor asesmen, atau skor CPMK pada asesmen kelas ini.
     */
    public function hasAcademicRecords(int $sectionId, int $studentId): bool
    {
        return $this->studentIdsWithAcademicRecords($sectionId, [$studentId])->isNotEmpty();
    }

    /**
     * @param  iterable<int>|null  $studentIds  Batasi pencarian ke mahasiswa tertentu (null = semua).
     * @return Collection<int, int>
     */
    public function studentIdsWithAcademicRecords(int $sectionId, ?iterable $studentIds = null): Collection
    {
        $assessmentIds = Assessment::where('class_section_id', $sectionId)->pluck('id');
        if ($assessmentIds->isEmpty()) {
            return collect();
        }

        $filter = $studentIds === null ? null : collect($studentIds)->map(fn ($id) => (int) $id)->values()->all();
        if ($filter !== null && $filter === []) {
            return collect();
        }

        $ids = collect();

        $ids = $ids->merge(Submission::query()
            ->whereIn('assessment_id', $assessmentIds)
            ->when($filter !== null, fn ($query) => $query->where(fn ($sub) => $sub->whereIn('mahasiswa_id', $filter)->orWhereIn('user_id', $filter)))
            ->get(['mahasiswa_id', 'user_id'])
            ->map(fn ($submission) => (int) ($submission->mahasiswa_id ?: $submission->user_id)));

        $ids = $ids->merge(StudentAssessmentScore::query()
            ->whereIn('assessment_id', $assessmentIds)
            ->whereNotNull('score')
            ->when($filter !== null, fn ($query) => $query->whereIn('mahasiswa_id', $filter))
            ->pluck('mahasiswa_id'));

        $ids = $ids->merge(AssessmentAttempt::query()
            ->whereIn('assessment_id', $assessmentIds)
            ->when($filter !== null, fn ($query) => $query->whereIn('mahasiswa_id', $filter))
            ->pluck('mahasiswa_id'));

        $ids = $ids->merge(StudentAssessmentCpmkScore::query()
            ->whereIn('assessment_id', $assessmentIds)
            ->when($filter !== null, fn ($query) => $query->whereIn('mahasiswa_id', $filter))
            ->pluck('mahasiswa_id'));

        return $ids->map(fn ($id) => (int) $id)
            ->filter()
            ->when($filter !== null, fn ($collection) => $collection->intersect($filter))
            ->unique()
            ->values();
    }

    /**
     * Kondisi 1 — pendaftaran via kode. Wajib dipanggil di dalam transaksi
     * yang sudah mengunci baris kelas (lockForUpdate).
     *
     * @return string success|rejoined|rejoined_after_kick|already_enrolled|full|archived|blocked
     */
    public function join(ClassSection $section, User $student): string
    {
        return DB::transaction(function () use ($section, $student): string {
            $lockedSection = ClassSection::whereKey($section->id)->lockForUpdate()->firstOrFail();

            if ($lockedSection->isArchived()) {
                return 'archived';
            }

            $row = $this->record($lockedSection->id, $student->id, true);

            if ($row && $row->status === self::STATUS_ENROLLED) {
                return 'already_enrolled';
            }

            if ($row && $row->status === self::STATUS_KICKED && (int) $row->kick_count >= self::MAX_KICKS_BEFORE_LOCK) {
                return 'blocked';
            }

            if ($lockedSection->capacity && $lockedSection->students()->count() >= $lockedSection->capacity) {
                return 'full';
            }

            $now = now();

            if (! $row) {
                DB::table(self::TABLE)->insert([
                    'class_section_id' => $lockedSection->id,
                    'mahasiswa_id' => $student->id,
                    'status' => self::STATUS_ENROLLED,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                return 'success';
            }

            $wasKicked = $row->status === self::STATUS_KICKED;

            DB::table(self::TABLE)->where('id', $row->id)->update([
                'status' => self::STATUS_ENROLLED,
                'dropped_at' => null,
                'updated_at' => $now,
            ]);

            return $wasKicked ? 'rejoined_after_kick' : 'rejoined';
        }, 3);
    }

    /**
     * Kondisi 2 — mahasiswa keluar sendiri.
     *
     * @return string removed|dropped|not_enrolled|archived
     */
    public function leave(ClassSection $section, User $student): string
    {
        return DB::transaction(function () use ($section, $student) {
            $section = ClassSection::whereKey($section->id)->lockForUpdate()->firstOrFail();
            if ($section->isArchived()) {
                return 'archived';
            }

            $row = $this->record($section->id, $student->id, true);
            if (! $row || $row->status !== self::STATUS_ENROLLED) {
                return 'not_enrolled';
            }

            // 2.1: belum ada riwayat → hapus bersih. Riwayat kick tetap dipertahankan
            // agar penghitung kesempatan tidak dapat di-reset lewat keluar-masuk.
            if (! $this->hasAcademicRecords($section->id, $student->id) && (int) $row->kick_count === 0 && ! $row->is_locked) {
                DB::table(self::TABLE)->where('id', $row->id)->delete();

                return 'removed';
            }

            // 2.2: sudah ada riwayat → soft drop, nilai tetap tersimpan.
            DB::table(self::TABLE)->where('id', $row->id)->update([
                'status' => self::STATUS_DROPPED_SELF,
                'dropped_at' => now(),
                'updated_at' => now(),
            ]);

            return 'dropped';
        }, 3);
    }

    /**
     * Kondisi 3 — dosen / admin prodi mengeluarkan mahasiswa (alasan wajib).
     *
     * @return string kicked|locked|not_enrolled|archived
     */
    public function kick(ClassSection $section, User $student, User $actor, string $reason, bool $asAdmin = false): string
    {
        return DB::transaction(function () use ($section, $student, $actor, $reason, $asAdmin) {
            $section = ClassSection::whereKey($section->id)->lockForUpdate()->firstOrFail();
            if ($section->isArchived()) {
                return 'archived';
            }

            $row = $this->record($section->id, $student->id, true);
            if (! $row || $row->status !== self::STATUS_ENROLLED) {
                return 'not_enrolled';
            }

            // 3.3: mahasiswa terverifikasi Admin Prodi tidak dapat dikeluarkan dosen.
            if ($row->is_locked && ! $asAdmin) {
                return 'locked';
            }

            DB::table(self::TABLE)->where('id', $row->id)->update([
                'status' => self::STATUS_KICKED,
                'kick_count' => min(255, (int) $row->kick_count + 1),
                'kicked_at' => now(),
                'kicked_by' => $actor->id,
                'kick_reason' => mb_substr(trim($reason), 0, 255),
                'dropped_at' => null,
                'is_locked' => false,
                'updated_at' => now(),
            ]);

            return 'kicked';
        }, 3);
    }

    public function pendingAppeal(int $sectionId, int $studentId): ?ClassEnrollmentAppeal
    {
        return ClassEnrollmentAppeal::where('class_section_id', $sectionId)
            ->where('mahasiswa_id', $studentId)
            ->where('status', ClassEnrollmentAppeal::STATUS_PENDING)
            ->latest('id')
            ->first();
    }

    public function latestAppeal(int $sectionId, int $studentId): ?ClassEnrollmentAppeal
    {
        return ClassEnrollmentAppeal::where('class_section_id', $sectionId)
            ->where('mahasiswa_id', $studentId)
            ->latest('id')
            ->first();
    }

    public function canAppeal(ClassSection $section, int $studentId): bool
    {
        return ! $section->isArchived()
            && $this->isBlockedFromJoining($section, $studentId)
            && $this->pendingAppeal($section->id, $studentId) === null;
    }

    /**
     * Kondisi 5 — Admin Prodi menerima permohonan: mahasiswa langsung aktif
     * kembali dan dikunci dari pengeluaran sepihak oleh dosen.
     */
    public function approveAppeal(ClassEnrollmentAppeal $appeal, User $admin, ?string $notes = null): void
    {
        DB::transaction(function () use ($appeal, $admin, $notes) {
            $appeal->update([
                'status' => ClassEnrollmentAppeal::STATUS_APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'admin_notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
            ]);

            $row = $this->record($appeal->class_section_id, $appeal->mahasiswa_id, true);
            $values = [
                'status' => self::STATUS_ENROLLED,
                'dropped_at' => null,
                'is_locked' => true,
                'updated_at' => now(),
            ];

            if ($row) {
                DB::table(self::TABLE)->where('id', $row->id)->update($values);
            } else {
                DB::table(self::TABLE)->insert($values + [
                    'class_section_id' => $appeal->class_section_id,
                    'mahasiswa_id' => $appeal->mahasiswa_id,
                    'created_at' => now(),
                ]);
            }
        });
    }

    public function rejectAppeal(ClassEnrollmentAppeal $appeal, User $admin, string $notes): void
    {
        $appeal->update([
            'status' => ClassEnrollmentAppeal::STATUS_REJECTED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_notes' => trim($notes),
        ]);
    }

    /**
     * Mahasiswa non-aktif (keluar sendiri / dikeluarkan) yang memiliki riwayat
     * nilai — ditampilkan terpisah dari peserta aktif pada rekap OBE (PRD 5.6).
     *
     * @return Collection<int, array{student: User, status: string, status_label: string, left_at: ?Carbon, reason: ?string, kicked_by_name: ?string}>
     */
    public function inactiveStudentsWithRecords(ClassSection $section): Collection
    {
        $rows = DB::table(self::TABLE)
            ->where('class_section_id', $section->id)
            ->whereIn('status', [self::STATUS_DROPPED_SELF, self::STATUS_KICKED])
            ->get()
            ->keyBy('mahasiswa_id');

        if ($rows->isEmpty()) {
            return collect();
        }

        $withRecords = $this->studentIdsWithAcademicRecords($section->id, $rows->keys());
        if ($withRecords->isEmpty()) {
            return collect();
        }

        $kickerNames = User::whereIn('id', $rows->pluck('kicked_by')->filter()->unique())->pluck('name', 'id');

        return User::whereIn('id', $withRecords)
            ->orderBy('name')
            ->get()
            ->map(function (User $student) use ($rows, $kickerNames) {
                $row = $rows->get($student->id);
                $isKicked = $row->status === self::STATUS_KICKED;
                $leftAt = $isKicked ? $row->kicked_at : $row->dropped_at;

                return [
                    'student' => $student,
                    'status' => $row->status,
                    'status_label' => $isKicked ? 'Dikeluarkan' : 'Keluar Sendiri',
                    'left_at' => $leftAt ? Carbon::parse($leftAt) : null,
                    'reason' => $isKicked ? $row->kick_reason : null,
                    'kicked_by_name' => $isKicked ? ($kickerNames[$row->kicked_by] ?? null) : null,
                ];
            })
            ->values();
    }

    /**
     * Kondisi 7 — kembalikan alasan penolakan hapus kelas, atau null jika boleh dihapus.
     */
    public function deletionBlocker(ClassSection $section): ?string
    {
        if ($this->studentIdsWithAcademicRecords($section->id)->isNotEmpty()) {
            return 'Kelas ini memiliki riwayat akademik dan tidak dapat dihapus karena akan merusak rekapitulasi OBE. Silakan gunakan fitur Arsipkan Kelas.';
        }

        if ($section->students()->exists()) {
            return "Kelas {$section->display_code} tidak dapat dihapus karena sudah memiliki mahasiswa terdaftar.";
        }

        return null;
    }

    public function archive(ClassSection $section, User $actor): void
    {
        if ($section->isArchived()) {
            return;
        }

        $section->forceFill(['archived_at' => now(), 'archived_by' => $actor->id])->save();
    }

    public function unarchive(ClassSection $section): void
    {
        $section->forceFill(['archived_at' => null, 'archived_by' => null])->save();
    }

    /**
     * Kondisi 6.1 — arsipkan massal seluruh kelas pada satu semester.
     */
    public function archiveSemester(int $semesterId, int $prodiId, User $actor): int
    {
        return ClassSection::query()
            ->where('semester_id', $semesterId)
            ->whereNull('archived_at')
            ->whereHas('mataKuliah', fn ($query) => $query->where('prodi_id', $prodiId))
            ->update(['archived_at' => now(), 'archived_by' => $actor->id, 'updated_at' => now()]);
    }
}
