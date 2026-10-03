<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Ai\AiErrorCode;
use App\Services\Ai\AiTutor;
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

        $isDemoMode = (bool) (config('app.demo_mode') || app()->environment(['local', 'testing']));
        if ($isDemoMode && Schema::hasTable('users') && $credentials['email'] === 'demo.ai@sale.test' && $credentials['password'] === 'password123456') {
            $user = User::firstOrCreate(
                ['email' => 'demo.ai@sale.test'],
                ['name' => 'Mahasiswa Demo AI', 'password' => 'password123456']
            );
            if ($assignment && Schema::hasTable('ai_tasks')) {
                DB::table('ai_tasks')->updateOrInsert(
                    ['id' => $assignmentId],
                    ['title' => $assignment['title'], 'body' => $assignment['body'], 'enabled' => true]
                );
            }
            if ($assignment && Schema::hasTable('ai_access')) {
                DB::table('ai_access')->insertOrIgnore([
                    'user_id' => $user->id,
                    'task_id' => $assignmentId,
                ]);
            }
        }

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

    private function task(Request $request, int $assignment): object
    {
        abort_unless($request->user(), 401, 'Masuk dengan akun AI terlebih dahulu.');

        $content = $this->codingContent($assignment);
        $task = DB::table('ai_tasks')->where('id', $assignment)->first();
        abort_unless($content || $task, 404, 'Konten coding tidak ditemukan pada database.');
        if ($content && ! empty($content['assessment'])) {
            $this->authorizeAssessment($content['assessment'], $request->user());
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

        abort_unless(
            ! Schema::hasTable('ai_access') || DB::table('ai_access')->where('user_id', $request->user()->id)->where('task_id', $assignment)->exists(),
            403,
            'Akun ini belum mendapat akses AI untuk tugas ini.'
        );

        abort_unless($task && $task->enabled, 403, 'AI Asisten dinonaktifkan oleh dosen pengampu.');

        return $task;
    }

    private function codingContent(int $assignment): ?array
    {
        $assessment = Assessment::with('classSection')->find($assignment);
        if ($assessment) {
            $item = $assessment->learning_payload ?? [];
            $type = $item['type'] ?? $assessment->type;
            $eligible = $type === 'coding'
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

    private function authorizeAssessment(Assessment $assessment, $user): void
    {
        $section = $assessment->classSection;
        abort_unless($section && $user, 403);

        $allowed = $user->hasRole(Role::DOSEN)
            ? $user->can('manage', $section)
            : ($user->hasRole(Role::MAHASISWA)
                && $section->students()->where('users.id', $user->id)->exists()
                && $assessment->status === Assessment::STATUS_PUBLISHED);

        abort_unless($allowed, 403, 'Anda tidak memiliki akses ke konten ini.');
    }

    public function status(Request $request, int $assignment)
    {
        $task = $this->task($request, $assignment);
        $turns = DB::table('ai_turns')->where('user_id', $request->user()->id)->where('task_id', $task->id)->orderBy('id')->get(['question', 'answer', 'status']);
        $used = (int) DB::table('ai_usage')->where('scope', 'user:'.$request->user()->id)->where('day', now('UTC')->toDateString())->value('tokens');

        $dailyTokens = (int) (config('ai.daily_tokens') ?: (SystemSetting::valueFor('ai_token_quota') ?? 500000));
        $remainingTokens = max(0, $dailyTokens - $used);

        $apiKey = (string) (config('ai.key') ?: (SystemSetting::valueFor('ai_api_key') ?? ''));
        $isLiveAi = filled($apiKey);
        $isDemo = ! $isLiveAi && app()->environment('local');

        $taskTurns = config('ai.task_turns');

        return response()->json([
            'enabled' => $isLiveAi || $isDemo,
            'remaining_tokens' => $remainingTokens,
            'remaining_turns' => is_numeric($taskTurns) ? max(0, (int) $taskTurns - $turns->count()) : null,
            'total_tokens' => $dailyTokens,
            'used_tokens' => $used,
            'reset_at' => now('UTC')->addDay()->startOfDay()->toIso8601String(),
            'reset_time_wib' => '07.00 WIB',
            'history' => $turns,
            'demo_mode' => $isDemo,
        ]);
    }

    public function send(Request $request, int $assignment, AiTutor $tutor)
    {
        $task = $this->task($request, $assignment);

        $apiKey = (string) (config('ai.key') ?: (SystemSetting::valueFor('ai_api_key') ?? ''));
        $isLiveAi = filled($apiKey);
        $isDemo = ! $isLiveAi && app()->environment('local');

        abort_unless($isLiveAi || $isDemo, 503, 'AI belum diaktifkan oleh pengelola.');
        $input = $request->validate(['question' => 'required|string|max:2000', 'code' => 'nullable|string|max:4000']);
        $userId = $request->user()->id;
        $isV2 = (bool) config('ai.v2', false);

        $lock = null;
        $lockAcquired = false;
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

            $history = DB::table('ai_turns')->where('user_id', $userId)->where('task_id', $task->id)->orderBy('id')->get(['question', 'code', 'answer', 'status'])->map(fn ($row) => (array) $row)->all();
            $taskTurns = config('ai.task_turns');
            if (is_numeric($taskTurns)) {
                abort_if(count($history) >= (int) $taskTurns, 429, $isV2 ? AiErrorCode::TurnLimit->message() : 'Batas bantuan untuk tugas ini sudah tercapai. Lanjutkan percobaanmu atau diskusikan dengan dosen.', ['X-AI-Error' => AiErrorCode::TurnLimit->value]);
            }

            $id = DB::table('ai_turns')->insertGetId(['user_id' => $userId, 'task_id' => $task->id, 'question' => $input['question'], 'code' => $input['code'] ?? '']);
            try {
                if ($isLiveAi) {
                    $answer = $tutor->answer($userId, $task, $input['question'], $input['code'] ?? '', $history, $id);
                } else {
                    $answer = $this->demoAnswer($input['question'], $input['code'] ?? '');
                    $demoTokens = min(350, max(60, (int) round((strlen($input['question']) + strlen($answer)) / 2)));
                    $day = now('UTC')->toDateString();
                    DB::table('ai_usage')->insertOrIgnore(['scope' => 'user:'.$userId, 'day' => $day, 'tokens' => 0]);
                    DB::table('ai_usage')->where('scope', 'user:'.$userId)->where('day', $day)->increment('tokens', $demoTokens);
                }
                $isRefusal = $answer === GeminiTutor::REFUSAL || ($isV2 && $answer === AiErrorCode::BlockedAsksSolution->message());
                DB::table('ai_turns')->where('id', $id)->update(['answer' => $answer, 'status' => $isRefusal ? 'refused' : 'answered']);
            } catch (\Throwable $exception) {
                $status = ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 499) ? 'cancelled' : 'failed';
                DB::table('ai_turns')->where('id', $id)->update(['status' => $status]);
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
                    ], $statusCode);
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
