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

            $isDiscussion = Str::startsWith($notification['id'], ['discussion_', 'discuss_']);
            $notification['is_read'] = (bool) ($state?->read_at && (! $isDiscussion || $state->read_at->greaterThanOrEqualTo($eventAt)));

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
            ->whereIn('type', ['tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case', 'project'])
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
            ->with(['mataKuliah', 'assessments' => fn ($query) => $query->gradable()->where('status', Assessment::STATUS_PUBLISHED)])
            ->get();
        $assessmentIds = $sections->flatMap->assessments->pluck('id');
        $scores = StudentAssessmentScore::query()
            ->where('mahasiswa_id', $user->id)
            ->where('status', StudentAssessmentScore::STATUS_PUBLISHED)
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
                $target = $type === 'coding'
                    ? route('course.assignment.code', [$section->id, $assessment->id], false)
                    : route('mahasiswa.course.item', [$section->id, $assessment->id], false);

                if ($score && $score->score !== null) {
                    $eventAt = $score->published_at ?? $score->updated_at;
                    $notifications[] = $this->notification(
                        "grade_{$assessment->id}",
                        "Nilai Terbit: {$assessment->name} ({$section->display_code})",
                        'Hasil evaluasi telah diterbitkan dengan nilai '.number_format((float) $score->score, 0).'/100.'.($score->feedback ? ' Catatan dosen: "'.Str::limit($score->feedback, 80).'"' : ''),
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
                    $notifications[] = $this->notification(
                        "pending_{$assessment->id}",
                        ($isQuiz ? 'Kuis Tersedia: ' : 'Penugasan: ')."{$assessment->name} ({$section->display_code})",
                        "Mata Kuliah {$section->mataKuliah->name}. Batas tenggat: {$dueText}.",
                        $assessment->created_at,
                        $isQuiz ? 'quiz' : 'alert',
                        $target,
                        $isQuiz ? 'Mulai Kerjakan Kuis' : 'Buka Lembar Tugas',
                        'tugas',
                        $section->id
                    );
                }
            }
            $this->appendDiscussionNotification($notifications, $user, $section);
        }

        return $notifications;
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
        if ($date->diffInMinutes(now()) < 60) {
            return $date->diffForHumans();
        }

        return $date->isToday() ? $date->format('H:i') : $date->translatedFormat('d M, H:i');
    }
}
