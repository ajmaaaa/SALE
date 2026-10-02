<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Message;
use App\Models\Role;
use App\Models\StudentAssessmentScore;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserNotificationState;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DatabaseNotificationService
{
    public function forUser(User $user, ?string $workspaceRole = null): array
    {
        $workspaceRole ??= request()->is('dosen*') ? Role::DOSEN : Role::MAHASISWA;
        $notifications = $workspaceRole === Role::DOSEN && $user->hasRole(Role::DOSEN)
            ? $this->lecturerNotifications($user)
            : $this->studentNotifications($user);

        $states = UserNotificationState::query()
            ->where('user_id', $user->id)
            ->whereIn('notification_key', collect($notifications)->pluck('id'))
            ->get()
            ->keyBy('notification_key');

        $notifications = array_values(array_filter(array_map(function (array $notification) use ($states) {
            $state = $states->get($notification['id']);
            $eventAt = Carbon::createFromTimestamp((int) $notification['timestamp']);

            if ($state?->deleted_at && $state->deleted_at->greaterThanOrEqualTo($eventAt)) {
                return null;
            }

            $notification['is_read'] = (bool) ($state?->read_at && $state->read_at->greaterThanOrEqualTo($eventAt));

            return $notification;
        }, $notifications)));

        usort($notifications, fn (array $a, array $b) => $b['timestamp'] <=> $a['timestamp']);

        return $notifications;
    }

    public function unreadCount(User $user, ?string $workspaceRole = null): int
    {
        return count(array_filter($this->forUser($user, $workspaceRole), fn (array $notification) => ! $notification['is_read']));
    }

    public function pendingTaskCount(User $user): int
    {
        $sectionIds = $user->classSectionsEnrolled()->pluck('class_sections.id');
        if ($sectionIds->isEmpty()) {
            return 0;
        }

        $completedIds = Submission::query()
            ->where('mahasiswa_id', $user->id)
            ->pluck('assessment_id')
            ->merge(StudentAssessmentScore::query()
                ->where('mahasiswa_id', $user->id)
                ->whereNotNull('score')
                ->pluck('assessment_id'))
            ->unique();

        return Assessment::query()
            ->whereIn('class_section_id', $sectionIds)
            ->whereIn('type', ['tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case', 'project', 'lainnya'])
            ->where('status', Assessment::STATUS_PUBLISHED)
            ->whereNotIn('id', $completedIds)
            ->count();
    }

    public function markRead(User $user, array $keys): void
    {
        $keys = array_filter(array_map('strval', $keys));
        foreach ($keys as $key) {
            UserNotificationState::updateOrCreate(
                ['user_id' => $user->id, 'notification_key' => $key],
                ['read_at' => now(), 'deleted_at' => null]
            );
        }
    }

    public function markDiscussionRead(User $user, int $sectionId): void
    {
        $keys = collect($this->forUser($user))
            ->where('category', 'diskusi')
            ->filter(fn (array $notification) => (int) ($notification['class_section_id'] ?? 0) === $sectionId)
            ->pluck('id')
            ->all();

        $this->markRead($user, $keys);
    }

    public function delete(User $user, array $keys): void
    {
        $valid = collect($this->forUser($user))->pluck('id')->intersect($keys);
        foreach ($valid as $key) {
            UserNotificationState::updateOrCreate(
                ['user_id' => $user->id, 'notification_key' => $key],
                ['deleted_at' => now()]
            );
        }
    }

    private function studentNotifications(User $user): array
    {
        $sections = $user->classSectionsEnrolled()
            ->with(['mataKuliah', 'assessments' => fn ($query) => $query->where('status', Assessment::STATUS_PUBLISHED)])
            ->get();
        $assessmentIds = $sections->flatMap->assessments->pluck('id');
        $scores = StudentAssessmentScore::query()
            ->where('mahasiswa_id', $user->id)
            ->whereNotNull('score')
            ->whereIn('status', [StudentAssessmentScore::STATUS_FINAL, StudentAssessmentScore::STATUS_PUBLISHED])
            ->whereIn('assessment_id', $assessmentIds)
            ->get()
            ->keyBy('assessment_id');
        $submissions = Submission::query()
            ->whereIn('assessment_id', $assessmentIds)
            ->where(fn ($query) => $query->where('mahasiswa_id', $user->id)->orWhere('user_id', $user->id))
            ->latest('id')
            ->get()
            ->unique('assessment_id')
            ->keyBy('assessment_id');

        $notifications = [];
        foreach ($sections as $section) {
            foreach ($section->assessments as $assessment) {
                $score = $scores->get($assessment->id);
                $submission = $submissions->get($assessment->id);
                $type = $assessment->type;
                $payload = $assessment->learning_payload ?? [];
                $isCoding = ($type === 'coding')
                    || (($payload['task_mode'] ?? null) === 'coding')
                    || (($payload['question_type'] ?? null) === 'coding')
                    || !empty($payload['coding_steps']);
                $isCodingMaterial = ($type === 'materi') && (($payload['material_mode'] ?? null) === 'coding');

                $target = route('mahasiswa.course.item', [$section->id, $assessment->id], false);

                if ($score && $score->score !== null) {
                    $eventAt = $score->updated_at ?? $score->published_at ?? now();
                    $isQuiz = in_array($type, ['kuis', 'uts', 'uas'], true);

                    if ($isQuiz) {
                        $title = "Nilai Quiz Diperbarui: {$assessment->name} ({$section->display_code})";
                        $message = 'Nilai quiz sudah diperbarui oleh dosen dengan perolehan nilai '.number_format((float) $score->score, 0).'/100.'.($score->feedback ? ' Catatan dosen: "'.Str::limit($score->feedback, 80).'"' : '');
                    } elseif ($isCoding) {
                        $title = "Nilai Tugas Pemrograman Diperbarui: {$assessment->name} ({$section->display_code})";
                        $message = 'Nilai tugas pemrograman sudah dinilai oleh dosen dengan perolehan nilai '.number_format((float) $score->score, 0).'/100.'.($score->feedback ? ' Catatan dosen: "'.Str::limit($score->feedback, 80).'"' : '');
                    } else {
                        $title = "Nilai Tugas Diperbarui: {$assessment->name} ({$section->display_code})";
                        $message = 'Nilai tugas sudah dinilai dan diperbarui oleh dosen dengan perolehan nilai '.number_format((float) $score->score, 0).'/100.'.($score->feedback ? ' Catatan dosen: "'.Str::limit($score->feedback, 80).'"' : '');
                    }

                    $notifications[] = $this->notification(
                        "grade_{$assessment->id}",
                        $title,
                        $message,
                        $eventAt,
                        'grade',
                        $target,
                        'Lihat Hasil Nilai',
                        'nilai',
                        $section->id
                    );
                } elseif ($submission) {
                    $eventAt = $submission->submitted_at ?? $submission->created_at;
                    $notifications[] = $this->notification(
                        "submit_{$assessment->id}",
                        "Jawaban Terkirim: {$assessment->name} ({$section->display_code})",
                        'Jawaban tersimpan di sistem dan sedang menunggu penilaian dosen.',
                        $eventAt,
                        'check',
                        $target,
                        'Lihat Detail Submission',
                        'tugas',
                        $section->id
                    );
                } else {
                    $dueText = $assessment->due_at?->translatedFormat('d M Y, H:i') ?? 'Tanpa batas tenggat';
                    $isQuiz = in_array($type, ['kuis', 'uts', 'uas'], true);

                    if ($isQuiz) {
                        $notifications[] = $this->notification(
                            "pending_{$assessment->id}",
                            "Kuis Tersedia: {$assessment->name} ({$section->display_code})",
                            "Mata Kuliah {$section->mataKuliah->name}. Batas tenggat: {$dueText}.",
                            $assessment->created_at,
                            'quiz',
                            $target,
                            'Mulai Kerjakan Kuis',
                            'tugas',
                            $section->id
                        );
                    } elseif ($isCoding && $type !== 'materi') {
                        $notifications[] = $this->notification(
                            "pending_{$assessment->id}",
                            "Tugas Pemrograman Tersedia: {$assessment->name} ({$section->display_code})",
                            "Mata Kuliah {$section->mataKuliah->name}. Batas tenggat: {$dueText}.",
                            $assessment->created_at,
                            'quiz',
                            $target,
                            'Mulai Kerjakan Tugas Koding',
                            'tugas',
                            $section->id
                        );
                    } elseif ($type === 'materi') {
                        $notifications[] = $this->notification(
                            "material_{$assessment->id}",
                            ($isCodingMaterial ? 'Materi Pemrograman: ' : 'Materi Baru: ')."{$assessment->name} ({$section->display_code})",
                            "Mata Kuliah {$section->mataKuliah->name}. Pelajari materi pembelajaran yang telah diterbitkan dosen.",
                            $assessment->created_at,
                            'alert',
                            $target,
                            'Pelajari Materi',
                            'materi',
                            $section->id
                        );
                    } elseif ($type === 'pengumuman') {
                        $notifications[] = $this->notification(
                            "announcement_{$assessment->id}",
                            "Pengumuman: {$assessment->name} ({$section->display_code})",
                            "Mata Kuliah {$section->mataKuliah->name}. Pengumuman penting dari dosen pengampu.",
                            $assessment->created_at,
                            'alert',
                            $target,
                            'Lihat Pengumuman',
                            'sistem',
                            $section->id
                        );
                    } elseif ($type === 'lainnya') {
                        $notifications[] = $this->notification(
                            "pending_{$assessment->id}",
                            "Pengumpulan: {$assessment->name} ({$section->display_code})",
                            "Mata Kuliah {$section->mataKuliah->name}. Batas tenggat: {$dueText}.",
                            $assessment->created_at,
                            'alert',
                            $target,
                            'Buka Lembar Pengumpulan',
                            'tugas',
                            $section->id
                        );
                    } else {
                        $notifications[] = $this->notification(
                            "pending_{$assessment->id}",
                            "Penugasan: {$assessment->name} ({$section->display_code})",
                            "Mata Kuliah {$section->mataKuliah->name}. Batas tenggat: {$dueText}.",
                            $assessment->created_at,
                            'alert',
                            $target,
                            'Buka Lembar Tugas',
                            'tugas',
                            $section->id
                        );
                    }
                }
            }
            $this->appendDiscussionNotification($notifications, $user, $section);
        }

        $notifications = array_merge($notifications, $this->systemNotifications());

        return $notifications;
    }

    private function systemNotifications(): array
    {
        return [];
    }

    private function lecturerNotifications(User $user): array
    {
        $sections = ClassSection::query()
            ->where(fn ($query) => $query->where('dosen_id', $user->id)->orWhere('dosen_pendamping_id', $user->id))
            ->with('mataKuliah')
            ->get();
        $sectionIds = $sections->pluck('id');
        $submissions = Submission::query()
            ->whereHas('assessment', fn ($query) => $query->whereIn('class_section_id', $sectionIds))
            ->with(['assessment', 'mahasiswa', 'user'])
            ->latest('submitted_at')
            ->get();
        $notifications = [];

        foreach ($submissions as $submission) {
            $assessment = $submission->assessment;
            $section = $sections->firstWhere('id', $assessment?->class_section_id);
            if (! $section || ! $assessment) {
                continue;
            }
            $student = $submission->mahasiswa ?? $submission->user;
            $notifications[] = $this->notification(
                "submission_{$submission->id}",
                "Submission Baru: {$assessment->name} ({$section->display_code})",
                ($student?->name ?? 'Mahasiswa').' telah mengumpulkan jawaban dan menunggu penilaian.',
                $submission->submitted_at ?? $submission->created_at,
                'check',
                route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id], false),
                'Buka Input Nilai',
                'tugas',
                $section->id
            );
        }

        foreach ($sections as $section) {
            $this->appendDiscussionNotification($notifications, $user, $section);
        }

        return $notifications;
    }

    private function appendDiscussionNotification(array &$notifications, User $user, ClassSection $section): void
    {
        $message = Message::query()
            ->whereHas('room', fn ($query) => $query->where('class_section_id', $section->id)->orWhere('course_id', $section->id))
            ->where('user_id', '!=', $user->id)
            ->with('user')
            ->latest('created_at')
            ->first();

        if ($message) {
            $eventAt = $message->created_at;
            $author = $message->user?->name ?? 'Pengguna';
            $body = $message->content;
            $key = "discuss_{$section->id}_message_{$message->id}";
        } else {
            return;
        }

        $route = $user->hasRole(Role::DOSEN) ? 'dosen.course.show' : 'mahasiswa.course.show';
        $notifications[] = $this->notification(
            $key,
            "Diskusi Baru: {$section->display_code} - {$section->mataKuliah->name}",
            $author.': "'.Str::limit($body, 100).'"',
            $eventAt,
            'chat',
            route($route, $section->id, false).'#diskusi-kelas',
            'Buka Forum Diskusi',
            'diskusi',
            $section->id
        );
    }

    private function notification(string $id, string $title, string $message, $eventAt, string $icon, string $link, string $action, string $category, int $sectionId): array
    {
        $date = Carbon::parse($eventAt ?? now());

        return [
            'id' => $id,
            'title' => $title,
            'message' => $message,
            'time' => $this->formatTime($date),
            'timestamp' => $date->timestamp,
            'icon_type' => $icon,
            'link' => $link,
            'action_label' => $action,
            'category' => $category,
            'class_section_id' => $sectionId,
            'is_read' => false,
        ];
    }

    private function formatTime(Carbon $date): string
    {
        $now = Carbon::now();
        $diffMin = (int) floor(abs($date->diffInMinutes($now, false)));

        if ($diffMin < 60) {
            if ($diffMin < 1) {
                return 'Baru saja';
            }

            return "{$diffMin} menit lalu";
        }

        return $date->isToday() ? $date->format('H:i') : $date->translatedFormat('d M, H:i');
    }
}
