<?php

namespace App\Http\Controllers;

use App\Models\AiMessage;
use App\Models\AiThread;
use App\Models\Assessment;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Ai\AiErrorCode;
use App\Services\Ai\AiTutor;
use App\Services\Ai\ContextBuilder;
use App\Services\Ai\GeminiTutor;
use App\Support\LearningPreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AiTutorController extends Controller
{
    public function login(Request $request)
    {
        $request->validate(['assignment' => 'nullable|integer|min:1']);
        $assignmentId = $request->integer('assignment', 1);
        $assignment = $this->codingContent($assignmentId);
        abort_unless($assignment, 404, 'Konten coding tidak ditemukan pada database.');
        $destination = $this->destination($assignmentId, $assignment);
        $credentials = $request->validate(['email' => 'required|email|max:254', 'password' => 'required|string|max:1024']);
        $credentials['email'] = strtolower(trim($credentials['email']));
        $key = 'ai:login:'.hash('sha256', strtolower($credentials['email']));
        abort_if(RateLimiter::tooManyAttempts($key, 5), 429, 'Terlalu banyak percobaan masuk. Tunggu satu menit.');
        RateLimiter::hit($key, 60);

        if (! Auth::attempt($credentials)) {
            return redirect($destination)->withErrors(['ai' => 'Email atau password akun AI tidak sesuai.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect($destination);
    }

    public function logout(Request $request)
    {
        $request->validate(['assignment' => 'nullable|integer|min:1']);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $assignmentId = $request->integer('assignment', 1);
        $assignment = $this->codingContent($assignmentId);
        abort_unless($assignment, 404);

        return redirect($this->destination($assignmentId, $assignment));
    }

    private function destination(int $assignmentId, ?array $assignment): string
    {
        if (app()->environment('testing')) {
            return url("/mahasiswa/assignment/{$assignmentId}/code");
        }
        if (! empty($assignment['course'])) {
            return route('course.assignment.code', [$assignment['course'], $assignmentId]);
        }

        return url("/mahasiswa/assignment/{$assignmentId}/code");
    }

    private function task(Request $request, int $assignment, bool $isWrite = false): object
    {
        abort_unless($request->user(), 401, 'Masuk dengan akun AI terlebih dahulu.');

        $content = $this->codingContent($assignment);
        $task = DB::table('ai_tasks')->where('id', $assignment)->first();
        abort_unless($content || $task, 404, 'Konten coding tidak ditemukan pada database.');
        if ($content && ! empty($content['assessment'])) {
            $this->authorizeAssessment($content['assessment'], $request->user(), $isWrite);
        }

        // Pastikan status ai_tasks sinkron dengan pengaturan ai_enabled pada konten
        if ($content) {
            $isAiAllowed = (bool) ($content['ai_enabled'] ?? true);
            $taskTitle = $content['title'] ?? 'Praktikum Coding';
            $taskBody = $content['body'] ?? 'Selesaikan tugas coding.';
            if (! empty($content['coding_steps']) && is_array($content['coding_steps'])) {
                $stepsDesc = [];
                foreach ($content['coding_steps'] as $sIdx => $st) {
                    $stTitle = $st['title'] ?? ('Tahap '.($sIdx + 1));
                    $stBody = $st['body'] ?? '';
                    $stCpmk = ! empty($st['cpmk']) ? " [Target CPMK: {$st['cpmk']}]" : '';
                    $stepsDesc[] = ($sIdx + 1).". {$stTitle}{$stCpmk}: {$stBody}";
                }
                $taskBody .= "\n\nDetail Tahap/Instruksi:\n".implode("\n", $stepsDesc);
            }

            // RAG: Ambil materi pembelajaran dari course/kelas terkait sebagai konteks pengetahuan
            if (! empty($content['course']) && ! app()->environment('testing')) {
                $relatedMaterials = Assessment::where('class_section_id', $content['course'])
                    ->where('type', 'materi')
                    ->where('status', Assessment::STATUS_PUBLISHED)
                    ->orderBy('id')
                    ->limit(5)
                    ->get(['name', 'description', 'learning_payload']);
                if ($relatedMaterials->isNotEmpty()) {
                    $matSummaries = [];
                    foreach ($relatedMaterials as $cMat) {
                        $mBody = $cMat->learning_payload['body'] ?? $cMat->description ?? '';
                        $clean = trim(strip_tags((string) $mBody));
                        if ($clean !== '') {
                            $matSummaries[] = "- {$cMat->name}: ".Str::limit($clean, 250);
                        }
                    }
                    if (! empty($matSummaries)) {
                        $taskBody .= "\n\nKonteks Materi Kuliah Terkait (RAG):\n".implode("\n", $matSummaries);
                    }
                }
            }

            if (! $task) {
                DB::table('ai_tasks')->insert([
                    'id' => $assignment,
                    'title' => $taskTitle,
                    'body' => $taskBody,
                    'enabled' => $isAiAllowed,
                ]);
                $task = DB::table('ai_tasks')->where('id', $assignment)->first();
            } elseif (! empty($content['assessment'])) {
                DB::table('ai_tasks')->where('id', $assignment)->update([
                    'title' => $taskTitle,
                    'body' => $taskBody,
                    'enabled' => $isAiAllowed,
                ]);
                $task->title = $taskTitle;
                $task->body = $taskBody;
                $task->enabled = $isAiAllowed;
            }
        }

        // Integrasikan akses akun AI: seluruh akun mahasiswa (dan dosen) otomatis diberikan izin akses AI asisten belajar (kecuali saat testing)
        if (! app()->environment('testing') && Schema::hasTable('ai_access') && ! DB::table('ai_access')->where('user_id', $request->user()->id)->where('task_id', $assignment)->exists()) {
            DB::table('ai_access')->insertOrIgnore([
                'user_id' => $request->user()->id,
                'task_id' => $assignment,
            ]);
        }

        $isV2 = (bool) config('ai.v2', false);
        if (! $isV2) {
            abort_unless(
                ! Schema::hasTable('ai_access') || DB::table('ai_access')->where('user_id', $request->user()->id)->where('task_id', $assignment)->exists(),
                403,
                'Akun ini belum mendapat akses AI untuk tugas ini.'
            );
        }

        abort_unless($task && $task->enabled, 403, 'AI Asisten dinonaktifkan oleh dosen pengampu.');

        return $task;
    }

    private function codingContent(int $assignment): ?array
    {
        $assessment = Assessment::with('classSection')->find($assignment);
        if ($assessment) {
            $item = $assessment->learning_payload ?? [];
            $type = $item['type'] ?? $assessment->type;
            $isV2 = (bool) config('ai.v2', false);
            $eligible = ($isV2 && in_array($assessment->type, ['tugas', 'materi'], true))
                || $type === 'coding'
                || ($type === 'materi' && ($item['material_mode'] ?? null) === 'coding')
                || (($item['task_mode'] ?? null) === 'coding')
                || (($item['question_type'] ?? null) === 'coding')
                || ! empty($item['coding_steps']);

            if ($eligible) {
                return array_merge($item, [
                    'id' => $assessment->id,
                    'course' => $assessment->class_section_id,
                    'title' => $assessment->name,
                    'body' => $assessment->description ?? ($item['body'] ?? $assessment->name),
                    'assessment' => $assessment,
                ]);
            }
        }

        $previewItem = LearningPreview::items()[$assignment] ?? null;
        if ($previewItem) {
            $type = $previewItem['type'] ?? null;
            $eligible = $type === 'coding'
                || ($type === 'materi' && ($previewItem['material_mode'] ?? null) === 'coding')
                || (($previewItem['task_mode'] ?? null) === 'coding');

            return $eligible ? $previewItem : null;
        }

        return null;
    }

    private function authorizeAssessment(Assessment $assessment, $user, bool $isWrite = false): void
    {
        $section = $assessment->classSection;
        abort_unless($section && $user, 403, 'Akses ditolak.');

        $isV2 = (bool) config('ai.v2', false);

        // In v2, archived classes are read-only: students and lecturers cannot send new messages
        if ($isV2 && $isWrite && $section->isArchived()) {
            abort(403, 'Kelas ini telah diarsipkan. Anda hanya dapat melihat riwayat percakapan.');
        }

        if ($user->hasRole(Role::DOSEN)) {
            abort_unless($user->can('manage', $section), 403, 'Anda tidak memiliki akses ke kelas ini.');

            return;
        }

        // Student active enrollment check (status = enrolled, excludes kicked and dropped)
        $isEnrolled = $section->students()->where('users.id', $user->id)->exists();

        // In v2, if class is archived and reading: allowed for students who were enrolled in this class
        if ($isV2 && ! $isWrite && $section->isArchived()) {
            $wasEnrolled = $section->enrollmentRecords()
                ->where('users.id', $user->id)
                ->wherePivot('status', 'enrolled')
                ->exists();
            abort_unless($wasEnrolled, 403, 'Anda tidak memiliki akses ke konten ini.');

            return;
        }

        // Standard student access: must be actively enrolled (not kicked/dropped) and assessment published
        abort_unless(
            $isEnrolled && $assessment->status === Assessment::STATUS_PUBLISHED,
            403,
            'Anda tidak terdaftar aktif di kelas ini atau konten belum dipublikasikan.'
        );
    }

    public function status(Request $request, int $assignment)
    {
        $task = $this->task($request, $assignment, false);
        $user = $request->user();
        $isV2 = (bool) config('ai.v2', false);
        $useThreads = $isV2 && (bool) config('ai.threads', false);

        $assessment = Assessment::with('classSection')->find($assignment);
        $thread = ($useThreads && $assessment)
            ? AiThread::where('user_id', $user->id)->where('assessment_id', $assessment->id)->first()
            : null;

        if ($useThreads && $thread) {
            $msgQuery = AiMessage::where('thread_id', $thread->id)->orderBy('id');
            if ($thread->cleared_at) {
                $msgQuery->where('created_at', '>', $thread->cleared_at);
            }
            $messages = $msgQuery->get();
            $turns = [];
            $currentQ = null;
            foreach ($messages as $msg) {
                if ($msg->role === AiMessage::ROLE_USER) {
                    $currentQ = $msg->content;
                } elseif ($msg->role === AiMessage::ROLE_ASSISTANT) {
                    $turns[] = (object) [
                        'question' => $currentQ ?? '',
                        'answer' => $msg->content,
                        'status' => $msg->verdict === AiMessage::VERDICT_OK ? 'answered' : 'refused',
                    ];
                    $currentQ = null;
                }
            }
            $turnsCount = $thread->turns;
        } else {
            $turns = DB::table('ai_turns')->where('user_id', $user->id)->where('task_id', $task->id)->orderBy('id')->get(['question', 'answer', 'status']);
            $turnsCount = $turns->count();
        }

        $used = (int) DB::table('ai_usage')->where('scope', 'user:'.$user->id)->where('day', now('UTC')->toDateString())->value('tokens');

        $dailyTokens = (int) (config('ai.daily_tokens') ?: (SystemSetting::valueFor('ai_token_quota') ?? 500000));
        $remainingTokens = max(0, $dailyTokens - $used);

        $apiKey = (string) (config('ai.key') ?: (SystemSetting::valueFor('ai_api_key') ?? ''));
        $isLiveAi = filled($apiKey);
        $isDemo = ! $isLiveAi && app()->environment('local');

        $taskTurns = config('ai.task_turns');

        return response()->json([
            'enabled' => $isLiveAi || $isDemo,
            'remaining_tokens' => $remainingTokens,
            'remaining_turns' => is_numeric($taskTurns) ? max(0, (int) $taskTurns - $turnsCount) : null,
            'total_tokens' => $dailyTokens,
            'used_tokens' => $used,
            'reset_at' => now('UTC')->addDay()->startOfDay()->toIso8601String(),
            'reset_time_wib' => '07.00 WIB',
            'history' => $turns,
            'demo_mode' => $isDemo,
            'thread' => $thread,
        ]);
    }

    public function thread(Request $request, int $assignment)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Masuk dengan akun AI terlebih dahulu.');

        $assessment = Assessment::with('classSection')->find($assignment);
        abort_unless($assessment, 404, 'Asesmen tidak ditemukan.');

        $this->authorizeAssessment($assessment, $user, false);

        $useThreads = (bool) (config('ai.v2') && config('ai.threads'));
        if (! $useThreads) {
            return response()->json([
                'thread' => null,
                'messages' => [],
                'history' => [],
            ]);
        }

        $thread = AiThread::where('user_id', $user->id)
            ->where('assessment_id', $assessment->id)
            ->first();

        if (! $thread) {
            return response()->json([
                'thread' => null,
                'messages' => [],
                'history' => [],
            ]);
        }

        $msgQuery = AiMessage::where('thread_id', $thread->id)->orderBy('id');
        if ($thread->cleared_at) {
            $msgQuery->where('created_at', '>', $thread->cleared_at);
        }
        $messages = $msgQuery->get();

        $history = [];
        $currentUserMsg = null;
        foreach ($messages as $msg) {
            if ($msg->role === AiMessage::ROLE_USER) {
                $currentUserMsg = $msg;
            } elseif ($msg->role === AiMessage::ROLE_ASSISTANT) {
                $history[] = [
                    'question' => $currentUserMsg?->content ?? '',
                    'answer' => $msg->content,
                    'verdict' => $msg->verdict,
                    'status' => $msg->verdict === AiMessage::VERDICT_OK ? 'answered' : 'refused',
                ];
                $currentUserMsg = null;
            }
        }

        return response()->json([
            'thread' => $thread,
            'messages' => $messages,
            'history' => $history,
        ]);
    }

    public function destroyThread(Request $request, int $assignment)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Masuk dengan akun AI terlebih dahulu.');

        $assessment = Assessment::with('classSection')->find($assignment);
        abort_unless($assessment, 404, 'Asesmen tidak ditemukan.');

        $thread = AiThread::where('user_id', $user->id)
            ->where('assessment_id', $assessment->id)
            ->first();

        abort_unless($thread, 404, 'Thread percakapan AI tidak ditemukan.');
        abort_unless((int) $thread->user_id === (int) $user->id, 403, 'Anda hanya dapat menghapus thread milik Anda sendiri.');

        // Soft clear: sembunyikan percakapan dari tampilan mahasiswa, tetapi turns, blocked_count, tokens_used, dan prompt history tetap dipertahankan
        $thread->update(['cleared_at' => now()]);

        return response()->json([
            'cleared' => true,
            'deleted' => true,
            'message' => 'Tampilan percakapan AI berhasil dibersihkan.',
        ]);
    }

    public function send(Request $request, int $assignment, AiTutor $tutor)
    {
        $task = $this->task($request, $assignment, true);

        $apiKey = (string) (config('ai.key') ?: (SystemSetting::valueFor('ai_api_key') ?? ''));
        $isLiveAi = filled($apiKey);
        $isDemo = ! $isLiveAi && app()->environment('local');

        abort_unless($isLiveAi || $isDemo, 503, 'AI belum diaktifkan oleh pengelola.');

        // Server is single source of truth: strictly accept only allowed fields; never trust history or class_section_id from client
        $input = $request->validate([
            'question' => 'required|string|max:2000',
            'code' => 'nullable|string|max:4000',
            'console_output' => 'nullable|string|max:2000',
            'selected_line' => 'nullable|integer',
        ]);
        $userId = $request->user()->id;
        $isV2 = (bool) config('ai.v2', false);

        $lock = null;
        $lockAcquired = false;
        $thread = null;
        $userMessage = null;

        try {
            // Validasi kuota token harian: batasan HANYA berdasarkan sisa token, bukan jumlah turn/permintaan
            $used = (int) DB::table('ai_usage')->where('scope', 'user:'.$userId)->where('day', now('UTC')->toDateString())->value('tokens');
            $dailyTokens = (int) (config('ai.daily_tokens') ?: (SystemSetting::valueFor('ai_token_quota') ?? 500000));
            abort_if($used >= $dailyTokens, 429, $isV2 ? AiErrorCode::QuotaDaily->message() : 'Sisa kuota token Anda telah habis. Kuota akan di-reset pada pukul 07.00 WIB.', ['X-AI-Error' => AiErrorCode::QuotaDaily->value]);

            $lockTtl = $isV2 ? 90 : 180;
            $lock = Cache::lock('ai:user:'.$userId, $lockTtl);
            abort_unless($lock->get(), 429, $isV2 ? AiErrorCode::Locked->message() : 'Tunggu permintaan sebelumnya selesai.', ['X-AI-Error' => AiErrorCode::Locked->value]);
            $lockAcquired = true;
            Cache::forget('ai:cancelled:'.$userId);

            $rate = 'ai:send:'.$userId;
            abort_if(RateLimiter::tooManyAttempts($rate, 10), 429, 'Pertanyaan terlalu cepat. Tunggu beberapa detik sebelum mengirim lagi.');
            RateLimiter::hit($rate, 60);

            $useThreads = $isV2 && (bool) config('ai.threads', false);
            $useContext = $isV2 && (bool) config('ai.context', false);
            $assessment = ($useThreads || $useContext) ? Assessment::with('classSection')->find($assignment) : null;
            if ($useThreads && $assessment) {
                $thread = AiThread::firstOrCreate(
                    [
                        'user_id' => $userId,
                        'assessment_id' => $assessment->id,
                    ],
                    [
                        'class_section_id' => $assessment->class_section_id,
                        'turns' => 0,
                        'blocked_count' => 0,
                        'tokens_used' => 0,
                    ]
                );

                // Scope guard cooldown: jika blocked_count >= AI_BLOCK_THRESHOLD (default 5), tolak selama AI_BLOCK_COOLDOWN_SECONDS (default 120s)
                if ($useContext) {
                    $blockThreshold = (int) config('ai.block_threshold', 5);
                    $cooldownSeconds = (int) config('ai.block_cooldown_seconds', 120);
                    if ($thread->blocked_count >= $blockThreshold && $thread->last_blocked_at !== null) {
                        $cooldownUntil = $thread->last_blocked_at->copy()->addSeconds($cooldownSeconds);
                        if (now()->lessThan($cooldownUntil)) {
                            $retryAfter = max(1, (int) ceil(now()->diffInSeconds($cooldownUntil, true)));
                            abort(429, 'Terlalu banyak permintaan yang ditolak. Anda perlu jeda '.$retryAfter.' detik sebelum mencoba lagi.', [
                                'X-AI-Error' => AiErrorCode::BlockedOffTopic->value,
                                'Retry-After' => (string) $retryAfter,
                            ]);
                        }
                    }
                }

                $taskTurns = config('ai.task_turns');
                if (is_numeric($taskTurns)) {
                    abort_if($thread->turns >= (int) $taskTurns, 429, AiErrorCode::TurnLimit->message(), ['X-AI-Error' => AiErrorCode::TurnLimit->value]);
                }

                // History HANYA dari DB: muat AI_HISTORY_MESSAGES (default 8) pesan terakhir
                // Prompt tetap menggunakan seluruh riwayat pesan (tidak dipotong oleh cleared_at)
                $historyLimit = (int) config('ai.history_messages', 8);
                $dbMessages = AiMessage::where('thread_id', $thread->id)
                    ->orderBy('id', 'desc')
                    ->limit($historyLimit)
                    ->get()
                    ->reverse()
                    ->values();

                $history = $dbMessages->map(function (AiMessage $msg) {
                    $isBlockedAssistant = $msg->role === AiMessage::ROLE_ASSISTANT && $msg->verdict !== AiMessage::VERDICT_OK;
                    $content = $isBlockedAssistant
                        ? '[permintaan ditolak]'
                        : Str::limit((string) $msg->content, 1500, '');

                    return [
                        'role' => $msg->role,
                        'content' => $content,
                    ];
                })->all();

                // Simpan pesan user ke database
                $userMessage = AiMessage::create([
                    'thread_id' => $thread->id,
                    'role' => AiMessage::ROLE_USER,
                    'content' => $input['question'],
                    'verdict' => AiMessage::VERDICT_OK,
                    'tokens_in' => 0,
                    'tokens_out' => 0,
                ]);
            } else {
                $history = DB::table('ai_turns')->where('user_id', $userId)->where('task_id', $task->id)->orderBy('id')->get(['question', 'code', 'answer', 'status'])->map(fn ($row) => (array) $row)->all();
                $taskTurns = config('ai.task_turns');
                if (is_numeric($taskTurns)) {
                    abort_if(count($history) >= (int) $taskTurns, 429, $isV2 ? AiErrorCode::TurnLimit->message() : 'Batas bantuan untuk tugas ini sudah tercapai. Lanjutkan percobaanmu atau diskusikan dengan dosen.', ['X-AI-Error' => AiErrorCode::TurnLimit->value]);
                }
            }

            // ContextBuilder & Input Sanitization
            $sendQuestion = $input['question'];
            $sendCode = $input['code'] ?? '';
            $sendTask = $task;

            if ($useContext) {
                $contextBuilder = app(ContextBuilder::class);
                $built = $contextBuilder->build($assessment ?: $task, $input);

                $sendCode = $built['code'];
                $sendQuestion = $built['question'];
                if ($built['console_output'] !== '') {
                    $sendQuestion .= "\n\n[Output Konsol/Traceback]:\n".$built['console_output'];
                }
                if ($built['selected_line'] !== '-') {
                    $sendQuestion .= "\n[Baris Dipilih]: ".$built['selected_line'];
                }

                $sendTask = (object) [
                    'id' => $task->id ?? $assignment,
                    'title' => $built['title'],
                    'body' => $built['description']
                        ."\n\nTahapan/instruksi:\n".$built['coding_steps']
                        ."\n\nMateri kuliah terkait:\n".$built['linked_materials'],
                ];
            }

            $turnId = null;
            if (! $useThreads) {
                $turnId = DB::table('ai_turns')->insertGetId([
                    'user_id' => $userId,
                    'task_id' => $task->id,
                    'question' => $input['question'],
                    'code' => $input['code'] ?? '',
                ]);
            } else {
                $turnId = null;
            }

            try {
                $finalVerdict = AiMessage::VERDICT_OK;
                if ($isLiveAi) {
                    $useSingleCall = $isV2 && (bool) config('ai.single_call', false);
                    if ($useSingleCall) {
                        $contextBuilder = app(ContextBuilder::class);
                        $builtContext = $useContext ? ($built ?? $contextBuilder->build($assessment ?: $task, $input)) : $contextBuilder->build($assessment ?: $task, $input);
                        $turnsCount = $thread ? $thread->turns : count($history);
                        $singleResult = $tutor->answerSingleCall($userId, $sendTask, $builtContext, $history, $turnsCount, $turnId, $thread?->id);
                        $answer = $singleResult['reply'];
                        $finalVerdict = $singleResult['verdict'];
                    } else {
                        $answer = $tutor->answer($userId, $sendTask, $sendQuestion, $sendCode, $history, $turnId);
                        if ($answer === GeminiTutor::REFUSAL || $answer === AiErrorCode::BlockedAsksSolution->message()) {
                            $finalVerdict = AiMessage::VERDICT_ASKS_SOLUTION;
                        } elseif ($answer === AiErrorCode::BlockedOffTopic->message()) {
                            $finalVerdict = AiMessage::VERDICT_OFF_TOPIC;
                        }
                    }
                } else {
                    $answer = $this->demoAnswer($sendQuestion, $sendCode);
                    if ($answer === GeminiTutor::REFUSAL) {
                        $finalVerdict = AiMessage::VERDICT_ASKS_SOLUTION;
                    }
                    $demoTokens = min(350, max(60, (int) round((strlen($sendQuestion) + strlen($answer)) / 2)));
                    $day = now('UTC')->toDateString();
                    DB::table('ai_usage')->insertOrIgnore(['scope' => 'user:'.$userId, 'day' => $day, 'tokens' => 0]);
                    DB::table('ai_usage')->where('scope', 'user:'.$userId)->where('day', $day)->increment('tokens', $demoTokens);
                }

                if (! $useThreads) {
                    $isRefusal = $finalVerdict !== AiMessage::VERDICT_OK;
                    DB::table('ai_turns')->where('id', $turnId)->update(['answer' => $answer, 'status' => $isRefusal ? 'refused' : 'answered']);
                } else {
                    $lastUsage = $tutor->getLastUsage() ?? [];
                    $tokensIn = (int) ($lastUsage['input_tokens'] ?? 0);
                    $tokensOut = (int) ($lastUsage['output_tokens'] ?? 0);
                    $totalTokens = (int) ($lastUsage['total_tokens'] ?? ($tokensIn + $tokensOut));

                    AiMessage::create([
                        'thread_id' => $thread->id,
                        'role' => AiMessage::ROLE_ASSISTANT,
                        'content' => $answer,
                        'verdict' => $finalVerdict,
                        'tokens_in' => $tokensIn,
                        'tokens_out' => $tokensOut,
                    ]);

                    $thread->increment('turns');
                    if ($finalVerdict !== AiMessage::VERDICT_OK) {
                        $thread->increment('blocked_count');
                        $thread->update(['last_blocked_at' => now()]);
                        if ($thread->blocked_count >= (int) config('ai.block_threshold', 5) && ! $thread->flagged_for_review) {
                            $thread->update(['flagged_for_review' => true]);
                        }
                    }
                    if ($totalTokens > 0) {
                        $thread->increment('tokens_used', $totalTokens);
                    }
                    [$provider] = $tutor->resolveProviderAndModel();
                    $thread->update(['last_provider' => $provider]);
                }
            } catch (\Throwable $exception) {
                if (! $useThreads) {
                    $status = ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 499) ? 'cancelled' : 'failed';
                    if ($turnId) {
                        DB::table('ai_turns')->where('id', $turnId)->update(['status' => $status]);
                    }
                } else {
                    if ($userMessage) {
                        $userMessage->update(['verdict' => AiMessage::VERDICT_ERROR]);
                    }
                }
                throw $exception;
            }

            $currentUsed = (int) DB::table('ai_usage')->where('scope', 'user:'.$userId)->where('day', now('UTC')->toDateString())->value('tokens');

            return response()->json([
                'answer' => $answer,
                'remaining_tokens' => max(0, $dailyTokens - $currentUsed),
                'total_tokens' => $dailyTokens,
                'used_tokens' => $currentUsed,
            ]);
        } catch (\Throwable $exception) {
            if ($exception instanceof HttpExceptionInterface) {
                if ($isV2) {
                    $headers = $exception->getHeaders();
                    $statusCode = $exception->getStatusCode();
                    $errorCode = $headers['X-AI-Error'] ?? match ($statusCode) {
                        429 => AiErrorCode::QuotaDaily->value,
                        499 => 'cancelled',
                        503 => AiErrorCode::ProviderBusy->value,
                        default => 'error',
                    };

                    return response()->json([
                        'error' => $errorCode,
                        'message' => $exception->getMessage(),
                    ], $statusCode, $headers);
                }
                throw $exception;
            }
            // Record only the exception type, never prompts, credentials, or raw provider data.
            Log::error('AI tutor request failed', ['exception' => get_class($exception), 'message' => $exception->getMessage()]);
            if ($isV2) {
                return response()->json([
                    'error' => AiErrorCode::InternalError->value,
                    'message' => AiErrorCode::InternalError->message(),
                ], 503);
            }
            abort(503, 'Layanan AI mengalami gangguan: '.($exception->getMessage() ?: 'Silakan coba lagi sebentar lagi.'));
        } finally {
            if ($lockAcquired && $lock) {
                $lock->release();
            }
        }
    }

    public function cancel(Request $request, int $assignment)
    {
        $userId = $request->user()->id;
        Cache::put('ai:cancelled:'.$userId, true, 60);
        Cache::lock('ai:user:'.$userId)->forceRelease();

        return response()->json(['cancelled' => true]);
    }

    private function demoAnswer(string $question, string $code): string
    {
        $q = mb_strtolower($question);

        if (str_contains($q, 'kerjakan') || str_contains($q, 'jawaban lengkap') || str_contains($q, 'buatkan kode penuh') || str_contains($q, 'dari awal sampai selesai')) {
            return GeminiTutor::REFUSAL;
        }

        if (str_contains($q, 'insert') || str_contains($q, 'tambah') || str_contains($q, 'masuk')) {
            return "Untuk menyisipkan simpul baru pada Binary Search Tree (BST), ingat kaidah dasarnya:\n\n"
                ."1. **Kondisi Dasar (Akar Kosong)**: Jika `self.root is None`, simpul baru langsung menjadi `self.root`.\n"
                ."2. **Penelusuran**: Bandingkan nilai baru (`val`) dengan nilai simpul saat ini (`current.val`):\n"
                ."   - Jika `val < current.val`, arahkan langkah ke cabang kiri (`current.left`).\n"
                ."   - Jika `val > current.val`, arahkan langkah ke cabang kanan (`current.right`).\n"
                ."   - Jika nilai sama (duplikat), tentukan penanganannya (biasanya diabaikan atau ditaruh di cabang kanan).\n"
                .'3. **Penempatan**: Lanjutkan penelusuran sampai menemukan posisi kosong (`None`), lalu tautkan `Node(val)` baru pada cabang tersebut.';
        }

        if (str_contains($q, 'rekursi') || str_contains($q, 'recursion')) {
            return "Dalam BST, rekursi bekerja dengan memecah pohon menjadi subtree yang lebih kecil:\n\n"
                ."- **Base Case**: Ketika simpul saat ini bernilai `None`, berarti kita telah mencapai posisi di mana simpul baru harus dipasang.\n"
                ."- **Recursive Step**: Panggil kembali fungsi pembantu pada `node.left` atau `node.right` sesuai perbandingan nilai.\n\n"
                .'Coba perhatikan: apakah fungsi pembantu Anda mengembalikan simpul yang diperbarui agar pointer induknya dapat terhubung?';
        }

        if (str_contains($q, 'traversal') || str_contains($q, 'inorder') || str_contains($q, 'preorder') || str_contains($q, 'postorder')) {
            return "Urutan penelusuran pohon (Tree Traversal):\n\n"
                ."- **In-order**: Kiri → Akar → Kanan (Pada BST terurut, ini menghasilkan deret angka menaik/ascending).\n"
                ."- **Pre-order**: Akar → Kiri → Kanan (Bagus untuk mengkloning struktur pohon).\n"
                .'- **Post-order**: Kiri → Kanan → Akar (Bagus untuk operasi penghapusan simpul dari daun ke akar).';
        }

        if (str_contains($q, 'error') || str_contains($q, 'bug') || str_contains($q, 'none') || str_contains($q, 'attributeerror')) {
            return "Periksa pesan galat Anda:\n\n"
                ."Biasanya `AttributeError: 'NoneType' object has no attribute 'val'` terjadi ketika mencoba mengakses atribut pada simpul yang kosong (`None`).\n\n"
                .'Pastikan Anda selalu memeriksa `if node is None:` sebelum mengakses atribut `node.val`, `node.left`, atau `node.right`.';
        }

        return "Untuk modul **Praktikum Binary Tree**, fokuskan pada bagaimana setiap simpul terhubung melalui pointer `left` dan `right`.\n\n"
            .'Jika Anda ingin menguji metode `insert()`, coba visualisasikan pohon dengan angka-angka sederhana seperti `[8, 3, 10, 1, 6]`. Simpul mana yang menjadi anak kiri dan kanan dari akar `8`?';
    }
}
