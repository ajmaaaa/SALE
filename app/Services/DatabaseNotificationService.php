<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\ClassEnrollmentAppeal;
use App\Models\ClassSection;
use App\Models\ChatNotification;
use App\Models\Message;
use App\Models\MessageMention;
use App\Models\Role;
use App\Models\Room;
use App\Models\StudentAssessmentScore;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserNotificationState;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseNotificationService
{
    public function forUser(User $user, ?string $workspaceRole = null): array
    {
        $workspaceRole ??= request()->is('dosen*') ? Role::DOSEN : ((request()->is('admin*') || request()->is('admin-prodi*')) ? Role::ADMIN_PRODI : Role::MAHASISWA);
        if ($workspaceRole === Role::DOSEN && $user->hasRole(Role::DOSEN)) {
            $notifications = $this->lecturerNotifications($user);
        } elseif (($workspaceRole === Role::ADMIN_PRODI || $workspaceRole === Role::ADMIN) && ($user->hasRole(Role::ADMIN_PRODI) || $user->hasRole(Role::ADMIN))) {
            $notifications = $this->adminProdiNotifications($user);
        } else {
            $notifications = $this->studentNotifications($user);
        }

        $discussSectionKeys = collect($notifications)
            ->where('category', 'diskusi')
            ->map(fn ($n) => 'discuss_section_' . ($n['class_section_id'] ?? 0))
            ->unique()
            ->all();

        $allKeysToQuery = collect($notifications)->pluck('id')->merge($discussSectionKeys)->all();

        $states = UserNotificationState::query()
            ->where('user_id', $user->id)
            ->whereIn('notification_key', $allKeysToQuery)
            ->get()
            ->keyBy('notification_key');

        $notifications = array_values(array_filter(array_map(function (array $notification) use ($states) {
            $state = $states->get($notification['id']);
            $secState = ($notification['category'] ?? '') === 'diskusi'
                ? $states->get('discuss_section_' . ($notification['class_section_id'] ?? 0))
                : null;

            $eventAt = Carbon::createFromTimestamp((int) $notification['timestamp']);

            $isDeleted = ($state?->deleted_at && $state->deleted_at->greaterThanOrEqualTo($eventAt))
                || ($secState?->deleted_at && $secState->deleted_at->greaterThanOrEqualTo($eventAt));

            if ($isDeleted) {
                return null;
            }

            $isRead = ($state?->read_at && $state->read_at->greaterThanOrEqualTo($eventAt))
                || ($secState?->read_at && $secState->read_at->greaterThanOrEqualTo($eventAt))
                || (! empty($notification['is_read']));

            $notification['is_read'] = (bool) $isRead;

            if ($notification['is_read'] && ($notification['category'] ?? '') === 'diskusi') {
                $notification['unread_count'] = 0;
                $notification['mention_count'] = 0;
            }

            return $notification;
        }, $notifications)));

        usort($notifications, fn (array $a, array $b) => $b['timestamp'] <=> $a['timestamp']);

        return $this->filterByPreferences($notifications, $user, $workspaceRole);
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

            if (preg_match('/^discuss_(\d+)/', $key, $matches)) {
                $sectionId = (int) $matches[1];
                UserNotificationState::updateOrCreate(
                    ['user_id' => $user->id, 'notification_key' => "discuss_section_{$sectionId}"],
                    ['read_at' => now(), 'deleted_at' => null]
                );
                $this->markChatNotificationsReadForSection($user, $sectionId);
            }
        }
    }

    public function markDiscussionRead(User $user, int $sectionId): void
    {
        $keys = collect($this->forUser($user))
            ->where('category', 'diskusi')
            ->filter(fn (array $notification) => (int) ($notification['class_section_id'] ?? 0) === $sectionId)
            ->pluck('id')
            ->all();

        $keys[] = "discuss_section_{$sectionId}";

        $this->markRead($user, $keys);
        $this->markChatNotificationsReadForSection($user, $sectionId);
    }

    public function delete(User $user, array $keys): void
    {
        $valid = collect($this->forUser($user))->pluck('id')->intersect($keys);
        foreach ($valid as $key) {
            UserNotificationState::updateOrCreate(
                ['user_id' => $user->id, 'notification_key' => $key],
                ['deleted_at' => now()]
            );

            if (preg_match('/^discuss_(\d+)/', $key, $matches)) {
                $sectionId = (int) $matches[1];
                UserNotificationState::updateOrCreate(
                    ['user_id' => $user->id, 'notification_key' => "discuss_section_{$sectionId}"],
                    ['deleted_at' => now()]
                );
                $this->markChatNotificationsReadForSection($user, $sectionId);
            }
        }
    }

    public function markChatNotificationsReadForSection(User $user, int $sectionId): void
    {
        if (! Schema::hasTable('chat_notifications') || ! Schema::hasTable('rooms') || ! Schema::hasTable('messages')) {
            return;
        }

        $room = Room::where('class_section_id', $sectionId)->orWhere('course_id', $sectionId)->first();
        if ($room) {
            ChatNotification::where('user_id', $user->id)
                ->whereHas('message', fn ($q) => $q->where('room_id', $room->id))
                ->update(['is_read' => true]);
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

        // PRD-CLASS-LIFECYCLE-MANAGEMENT §7: Dosen Kick Mahasiswa
        $kickedRows = DB::table('class_section_student')
            ->where('mahasiswa_id', $user->id)
            ->where('status', 'kicked')
            ->get();

        if ($kickedRows->isNotEmpty()) {
            $kickedSections = ClassSection::with(['mataKuliah', 'dosen'])
                ->whereIn('id', $kickedRows->pluck('class_section_id')->unique())
                ->get()
                ->keyBy('id');

            foreach ($kickedRows as $row) {
                $section = $kickedSections->get($row->class_section_id);
                if (! $section) {
                    continue;
                }
                $kickerName = $section->dosen?->name ?? 'Dosen Pengampu';
                $reason = $row->kick_reason ? " Alasan: {$row->kick_reason}." : '';
                $eventAt = $row->kicked_at ?? $row->updated_at;
                $joinUrl = $section->enrollment_code
                    ? route('mahasiswa.join-kelas', $section->enrollment_code, false)
                    : route('mahasiswa.course.index', [], false);
                $actionLabel = $row->kick_count >= 2 ? 'Ajukan Verifikasi' : 'Daftar Ulang';

                $notifications[] = $this->notification(
                    "kicked_{$section->id}_{$row->kick_count}",
                    "Dikeluarkan dari Kelas: {$section->display_code}",
                    "Anda telah dikeluarkan dari kelas {$section->mataKuliah?->name} ({$section->display_code}) oleh {$kickerName}.{$reason}",
                    $eventAt,
                    'alert',
                    $joinUrl,
                    $actionLabel,
                    'sistem',
                    $section->id
                );
            }
        }

        // PRD-CLASS-LIFECYCLE-MANAGEMENT §7: Banding Disetujui / Ditolak
        $appeals = ClassEnrollmentAppeal::with(['classSection.mataKuliah'])
            ->where('mahasiswa_id', $user->id)
            ->whereIn('status', [ClassEnrollmentAppeal::STATUS_APPROVED, ClassEnrollmentAppeal::STATUS_REJECTED])
            ->get();

        foreach ($appeals as $appeal) {
            $section = $appeal->classSection;
            $mkName = $section?->mataKuliah?->name ?? 'Mata Kuliah';
            $code = $section?->display_code ?? '';
            $eventAt = $appeal->reviewed_at ?? $appeal->updated_at;

            if ($appeal->status === ClassEnrollmentAppeal::STATUS_APPROVED) {
                $notifications[] = $this->notification(
                    "appeal_approved_{$appeal->id}",
                    "Verifikasi Disetujui: {$code}",
                    "Permohonan verifikasi peserta Anda untuk kelas {$mkName} ({$code}) telah disetujui oleh Admin Prodi.",
                    $eventAt,
                    'check',
                    $section ? route('mahasiswa.course.show', $section->id, false) : route('mahasiswa.course.index', [], false),
                    'Buka Kelas',
                    'sistem',
                    $section?->id ?? 0
                );
            } else {
                $notes = $appeal->admin_notes ? " Catatan: {$appeal->admin_notes}." : '';
                $notifications[] = $this->notification(
                    "appeal_rejected_{$appeal->id}",
                    "Verifikasi Ditolak: {$code}",
                    "Permohonan verifikasi peserta Anda untuk kelas {$mkName} ({$code}) ditolak.{$notes}",
                    $eventAt,
                    'alert',
                    route('mahasiswa.course.index', [], false),
                    'Daftar Kelas',
                    'sistem',
                    $section?->id ?? 0
                );
            }
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

        // PRD-CLASS-LIFECYCLE-MANAGEMENT §7: Mahasiswa Re-join pasca Kick 1
        if ($sectionIds->isNotEmpty()) {
            $rejoinedRows = DB::table('class_section_student')
                ->whereIn('class_section_id', $sectionIds)
                ->where('status', 'enrolled')
                ->where('kick_count', '>', 0)
                ->whereNotNull('kicked_at')
                ->get();

            if ($rejoinedRows->isNotEmpty()) {
                $students = User::whereIn('id', $rejoinedRows->pluck('mahasiswa_id')->unique())->get()->keyBy('id');
                foreach ($rejoinedRows as $row) {
                    $student = $students->get($row->mahasiswa_id);
                    $section = $sections->firstWhere('id', $row->class_section_id);
                    if (! $student || ! $section) {
                        continue;
                    }

                    $eventAt = $row->updated_at ?? $row->kicked_at;
                    $studentNumber = $student->number ? " ({$student->number})" : '';
                    $notifications[] = $this->notification(
                        "rejoin_{$section->id}_{$student->id}_{$row->kick_count}",
                        "Peserta Masuk Kembali: {$section->display_code}",
                        "Mahasiswa {$student->name}{$studentNumber} yang sebelumnya Anda keluarkan telah bergabung kembali ke kelas {$section->display_code}.",
                        $eventAt,
                        'alert',
                        route('dosen.course.show', $section->id, false),
                        'Lihat Kelas',
                        'sistem',
                        $section->id
                    );
                }
            }
        }

        return $notifications;
    }

    private function adminProdiNotifications(User $user): array
    {
        $prodiId = (int) ($user->managing_prodi_id ?: $user->prodi_id);

        $pendingAppeals = ClassEnrollmentAppeal::query()
            ->where('status', ClassEnrollmentAppeal::STATUS_PENDING)
            ->with(['mahasiswa', 'classSection.mataKuliah'])
            ->when(! $user->hasRole(Role::ADMIN) && $prodiId > 0, function ($query) use ($prodiId) {
                $query->whereHas('classSection.mataKuliah', fn ($mk) => $mk->where('prodi_id', $prodiId));
            })
            ->latest('id')
            ->get();

        $notifications = [];
        foreach ($pendingAppeals as $appeal) {
            $student = $appeal->mahasiswa;
            $section = $appeal->classSection;
            $code = $section?->display_code ?? '-';
            $studentName = $student?->name ?? 'Mahasiswa';
            $studentNumber = $student?->number ? " ({$student->number})" : '';

            $notifications[] = $this->notification(
                "appeal_pending_{$appeal->id}",
                "Permohonan Verifikasi Peserta: {$code}",
                "Mahasiswa {$studentName}{$studentNumber} mengajukan verifikasi untuk kelas {$code}.",
                $appeal->created_at,
                'alert',
                route('admin-prodi.akademik.verifikasi-peserta', [], false),
                'Tinjau Permohonan',
                'sistem',
                $section?->id ?? 0
            );
        }

        return $notifications;
    }

    public function discussionStatsForSection(User $user, int $sectionId): array
    {
        if (! Schema::hasTable('rooms') || ! Schema::hasTable('messages')) {
            return [
                'unread_count' => 0,
                'mention_count' => 0,
                'latest_message' => null,
                'is_read' => true,
            ];
        }

        $room = Room::where('class_section_id', $sectionId)
            ->orWhere('course_id', $sectionId)
            ->first();

        if (! $room) {
            return [
                'unread_count' => 0,
                'mention_count' => 0,
                'latest_message' => null,
                'is_read' => true,
            ];
        }

        $latestMessage = Message::query()
            ->where('room_id', $room->id)
            ->where('user_id', '!=', $user->id)
            ->with(['user', 'mentions'])
            ->latest('created_at')
            ->first();

        if (! $latestMessage) {
            return [
                'unread_count' => 0,
                'mention_count' => 0,
                'latest_message' => null,
                'is_read' => true,
            ];
        }

        // Determine cutoff timestamp from user notification state
        $cutoff = null;
        if (Schema::hasTable('user_notification_states')) {
            $states = UserNotificationState::query()
                ->where('user_id', $user->id)
                ->where(function ($q) use ($sectionId) {
                    $q->where('notification_key', "discuss_section_{$sectionId}")
                      ->orWhere('notification_key', 'like', "discuss_{$sectionId}_message_%");
                })
                ->get();

            foreach ($states as $st) {
                if ($st->deleted_at && ($cutoff === null || $st->deleted_at->gt($cutoff))) {
                    $cutoff = $st->deleted_at;
                }
                if ($st->read_at && ($cutoff === null || $st->read_at->gt($cutoff))) {
                    $cutoff = $st->read_at;
                }
            }
        }

        $unreadQuery = Message::query()
            ->where('room_id', $room->id)
            ->where('user_id', '!=', $user->id);

        if ($cutoff) {
            $unreadQuery->where('created_at', '>', $cutoff);
        }

        $unreadMessages = $unreadQuery->with(['mentions'])->get();
        $unreadCount = $unreadMessages->count();

        if ($unreadCount === 0) {
            return [
                'unread_count' => 0,
                'mention_count' => 0,
                'latest_message' => $latestMessage,
                'is_read' => true,
            ];
        }

        // Calculate mention count among unread messages
        $mentionCount = 0;
        $userNameLower = mb_strtolower(trim($user->name));
        $nameParts = preg_split('/\s+/', $userNameLower);
        $firstName = $nameParts[0] ?? '';

        foreach ($unreadMessages as $msg) {
            $isMentioned = false;

            // 1. Check MessageMention relation
            if ($msg->mentions && $msg->mentions->contains('mentioned_user_id', $user->id)) {
                $isMentioned = true;
            } elseif (Schema::hasTable('chat_notifications') && ChatNotification::where('user_id', $user->id)->where('message_id', $msg->id)->where('type', 'mention')->exists()) {
                $isMentioned = true;
            } else {
                // 2. Text fallback: check content for @Name or @NIM
                $contentLower = mb_strtolower($msg->content);
                if (str_contains($contentLower, '@'.$userNameLower)) {
                    $isMentioned = true;
                } elseif ($user->number && str_contains($contentLower, '@'.mb_strtolower($user->number))) {
                    $isMentioned = true;
                } elseif ($firstName !== '' && mb_strlen($firstName) >= 3) {
                    if (preg_match('/@'.preg_quote($firstName, '/').'(\b|$)/i', $contentLower)) {
                        $isMentioned = true;
                    }
                }
            }

            if ($isMentioned) {
                $mentionCount++;
            }
        }

        return [
            'unread_count' => $unreadCount,
            'mention_count' => $mentionCount,
            'latest_message' => $latestMessage,
            'is_read' => false,
        ];
    }

    private function appendDiscussionNotification(array &$notifications, User $user, ClassSection $section): void
    {
        $stats = $this->discussionStatsForSection($user, $section->id);
        $message = $stats['latest_message'];

        if (! $message) {
            return;
        }

        $eventAt = $message->created_at;
        $author = $message->user?->name ?? 'Pengguna';
        $body = $message->content;
        $key = "discuss_{$section->id}_message_{$message->id}";

        $route = $user->hasRole(Role::DOSEN) ? 'dosen.course.show' : 'mahasiswa.course.show';

        $unreadCount = $stats['unread_count'];
        $mentionCount = $stats['mention_count'];

        $title = "Diskusi Baru: {$section->display_code} - {$section->mataKuliah->name}";

        $notif = $this->notification(
            $key,
            $title,
            $author.': "'.Str::limit($body, 100).'"',
            $eventAt,
            'chat',
            route($route, $section->id, false).'#diskusi-kelas',
            'Buka Forum Diskusi',
            'diskusi',
            $section->id
        );

        $notif['unread_count'] = $unreadCount;
        $notif['mention_count'] = $mentionCount;
        $notif['is_read'] = $stats['is_read'];

        $notifications[] = $notif;
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

    private function filterByPreferences(array $notifications, User $user, ?string $workspaceRole): array
    {
        $prefs = $user->notification_preferences;
        if (empty($prefs) || ! is_array($prefs)) {
            return $notifications;
        }

        if ($workspaceRole === Role::DOSEN) {
            return array_values(array_filter($notifications, function (array $n) use ($prefs) {
                $category = $n['category'] ?? '';
                $id = $n['id'] ?? '';

                if (str_starts_with($id, 'submission_') && isset($prefs['notif_submission']) && ! $prefs['notif_submission']) {
                    return false;
                }
                if ($category === 'diskusi' && isset($prefs['notif_forum']) && ! $prefs['notif_forum']) {
                    return false;
                }
                if (str_starts_with($id, 'rejoin_') && isset($prefs['notif_rekap']) && ! $prefs['notif_rekap']) {
                    return false;
                }

                return true;
            }));
        }

        return array_values(array_filter($notifications, function (array $n) use ($prefs) {
            $category = $n['category'] ?? '';
            $id = $n['id'] ?? '';

            if (($category === 'nilai' || str_starts_with($id, 'grade_')) && isset($prefs['grade']) && ! $prefs['grade']) {
                return false;
            }
            if (str_starts_with($id, 'announcement_') && isset($prefs['announcement']) && ! $prefs['announcement']) {
                return false;
            }
            if ($category === 'diskusi' && isset($prefs['forum']) && ! $prefs['forum']) {
                return false;
            }
            if (str_starts_with($id, 'pending_') && isset($prefs['deadline']) && ! $prefs['deadline']) {
                return false;
            }

            return true;
        }));
    }
}
