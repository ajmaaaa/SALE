<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Ai\GeminiTutor;
use App\Support\LearningPreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AiTutorController extends Controller
{
    public function login(Request $request)
    {
        $request->validate(['assignment' => 'nullable|integer|min:1']);
        $assignmentId = $request->integer('assignment', 1);
        $assignment = $this->codingContent($assignmentId);
        $destination = route('mahasiswa.assignment.code', $assignmentId);
        $credentials = $request->validate(['email' => 'required|email|max:254', 'password' => 'required|string|max:1024']);
        $credentials['email'] = strtolower(trim($credentials['email']));
        $key = 'ai:login:'.hash('sha256', strtolower($credentials['email']));
        abort_if(RateLimiter::tooManyAttempts($key, 5), 429, 'Terlalu banyak percobaan masuk. Tunggu satu menit.');
        RateLimiter::hit($key, 60);

        // Auto-provision demo account for quick UI testing / demonstration
        if (Schema::hasTable('users') && $credentials['email'] === 'demo.ai@sale.test' && $credentials['password'] === 'password123456') {
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

        if (Auth::user() && $assignmentId === 1 && Schema::hasTable('ai_access')) {
            if (Schema::hasTable('ai_tasks')) {
                DB::table('ai_tasks')->insertOrIgnore([
                    'id' => 1,
                    'title' => 'Praktikum Binary Tree',
                    'body' => 'Lengkapi metode insert() pada Binary Search Tree. Jelaskan penanganan cabang kiri, kanan, dan nilai duplikat.',
                    'enabled' => true,
                ]);
            }
            DB::table('ai_access')->insertOrIgnore([
                'user_id' => Auth::id(),
                'task_id' => 1,
            ]);
        }

        return redirect($destination);
    }

    public function logout(Request $request)
    {
        $request->validate(['assignment' => 'nullable|integer|min:1']);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('mahasiswa.assignment.code', $request->integer('assignment', 1));
    }

    private function task(Request $request, int $assignment): object
    {
        abort_unless($request->user(), 401, 'Masuk dengan akun AI terlebih dahulu.');

        $content = $this->codingContent($assignment);
        if ($content && Schema::hasTable('ai_tasks')) {
            DB::table('ai_tasks')->updateOrInsert(
                ['id' => $assignment],
                ['title' => $content['title'], 'body' => $content['body'], 'enabled' => true]
            );
        }

        abort_unless(DB::table('ai_access')->where('user_id', $request->user()->id)->where('task_id', $assignment)->exists(), 403, 'Akun ini belum mendapat akses AI untuk tugas ini.');
        $task = DB::table('ai_tasks')->where('id', $assignment)->where('enabled', true)->first();
        abort_unless($task, 403, 'AI untuk tugas ini tidak aktif.');

        return $task;
    }

    private function codingContent(int $assignment): ?array
    {
        $item = LearningPreview::items()[$assignment] ?? null;
        if (! $item) {
            return null;
        }

        $type = $item['type'] ?? null;
        $eligible = $type === 'coding'
            || ($type === 'materi' && ($item['material_mode'] ?? null) === 'coding');

        return $eligible ? $item : null;
    }

    public function status(Request $request, int $assignment)
    {
        $task = $this->task($request, $assignment);
        $turns = DB::table('ai_turns')->where('user_id', $request->user()->id)->where('task_id', $task->id)->orderBy('id')->get(['question', 'answer', 'status']);
        $used = (int) DB::table('ai_usage')->where('scope', 'user:'.$request->user()->id)->where('day', now('UTC')->toDateString())->value('tokens');

        $isLiveAi = (bool) config('ai.enabled') && filled(config('ai.key'));
        $isDemo = ! $isLiveAi && app()->environment('local');

        return response()->json([
            'enabled' => $isLiveAi || $isDemo,
            'remaining_tokens' => max(0, config('ai.daily_tokens', 100000) - $used),
            'remaining_turns' => max(0, config('ai.task_turns', 12) - $turns->count()),
            'reset_at' => now('UTC')->addDay()->startOfDay()->toIso8601String(),
            'history' => $turns,
            'demo_mode' => $isDemo,
        ]);
    }

    public function send(Request $request, int $assignment, GeminiTutor $tutor)
    {
        $task = $this->task($request, $assignment);

        $isLiveAi = config('ai.enabled') && filled(config('ai.key'));
        $isDemo = ! $isLiveAi && app()->environment('local');

        abort_unless($isLiveAi || $isDemo, 503, 'AI belum diaktifkan oleh pengelola.');
        $input = $request->validate(['question' => 'required|string|max:2000', 'code' => 'nullable|string|max:4000']);
        $userId = $request->user()->id;
        $lock = Cache::lock('ai:user:'.$userId, 180);
        abort_unless($lock->get(), 429, 'Tunggu permintaan sebelumnya selesai.');
        try {
            $rate = 'ai:send:'.$userId;
            abort_if(RateLimiter::tooManyAttempts($rate, 3), 429, 'Maksimal tiga pertanyaan per menit.');
            RateLimiter::hit($rate, 60);
            $history = DB::table('ai_turns')->where('user_id', $userId)->where('task_id', $task->id)->orderBy('id')->get(['question', 'code', 'answer', 'status'])->map(fn ($row) => (array) $row)->all();
            abort_if(count($history) >= config('ai.task_turns'), 429, 'Batas bantuan untuk tugas ini sudah tercapai. Lanjutkan percobaanmu atau diskusikan dengan dosen.');
            $id = DB::table('ai_turns')->insertGetId(['user_id' => $userId, 'task_id' => $task->id, 'question' => $input['question'], 'code' => $input['code'] ?? '']);
            try {
                if ($isLiveAi) {
                    $answer = $tutor->answer($userId, $task, $input['question'], $input['code'] ?? '', $history);
                } else {
                    $answer = $this->demoAnswer($input['question'], $input['code'] ?? '');
                }
                DB::table('ai_turns')->where('id', $id)->update(['answer' => $answer, 'status' => $answer === GeminiTutor::REFUSAL ? 'refused' : 'answered']);
            } catch (\Throwable $exception) {
                DB::table('ai_turns')->where('id', $id)->update(['status' => 'failed']);
                if ($exception instanceof HttpExceptionInterface) {
                    throw $exception;
                }
                // Record only the exception type, never prompts, credentials, or raw provider data.
                \Illuminate\Support\Facades\Log::error('AI tutor request failed', ['exception' => get_class($exception)]);
                abort(503, 'Layanan AI mengalami gangguan. Permintaan tidak diulang otomatis.');
            }

            return response()->json(['answer' => $answer]);
        } finally {
            $lock->release();
        }
    }

    private function demoAnswer(string $question, string $code): string
    {
        $q = mb_strtolower($question);

        if (str_contains($q, 'kerjakan') || str_contains($q, 'jawaban lengkap') || str_contains($q, 'buatkan kode penuh') || str_contains($q, 'dari awal sampai selesai')) {
            return GeminiTutor::REFUSAL;
        }

        if (str_contains($q, 'insert') || str_contains($q, 'tambah') || str_contains($q, 'masuk')) {
            return "Untuk menyisipkan simpul baru pada Binary Search Tree (BST), ingat kaidah dasarnya:\n\n"
                . "1. **Kondisi Dasar (Akar Kosong)**: Jika `self.root is None`, simpul baru langsung menjadi `self.root`.\n"
                . "2. **Penelusuran**: Bandingkan nilai baru (`val`) dengan nilai simpul saat ini (`current.val`):\n"
                . "   - Jika `val < current.val`, arahkan langkah ke cabang kiri (`current.left`).\n"
                . "   - Jika `val > current.val`, arahkan langkah ke cabang kanan (`current.right`).\n"
                . "   - Jika nilai sama (duplikat), tentukan penanganannya (biasanya diabaikan atau ditaruh di cabang kanan).\n"
                . "3. **Penempatan**: Lanjutkan penelusuran sampai menemukan posisi kosong (`None`), lalu tautkan `Node(val)` baru pada cabang tersebut.";
        }

        if (str_contains($q, 'rekursi') || str_contains($q, 'recursion')) {
            return "Dalam BST, rekursi bekerja dengan memecah pohon menjadi subtree yang lebih kecil:\n\n"
                . "- **Base Case**: Ketika simpul saat ini bernilai `None`, berarti kita telah mencapai posisi di mana simpul baru harus dipasang.\n"
                . "- **Recursive Step**: Panggil kembali fungsi pembantu pada `node.left` atau `node.right` sesuai perbandingan nilai.\n\n"
                . "Coba perhatikan: apakah fungsi pembantu Anda mengembalikan simpul yang diperbarui agar pointer induknya dapat terhubung?";
        }

        if (str_contains($q, 'traversal') || str_contains($q, 'inorder') || str_contains($q, 'preorder') || str_contains($q, 'postorder')) {
            return "Urutan penelusuran pohon (Tree Traversal):\n\n"
                . "- **In-order**: Kiri → Akar → Kanan (Pada BST terurut, ini menghasilkan deret angka menaik/ascending).\n"
                . "- **Pre-order**: Akar → Kiri → Kanan (Bagus untuk mengkloning struktur pohon).\n"
                . "- **Post-order**: Kiri → Kanan → Akar (Bagus untuk operasi penghapusan simpul dari daun ke akar).";
        }

        if (str_contains($q, 'error') || str_contains($q, 'bug') || str_contains($q, 'none') || str_contains($q, 'attributeerror')) {
            return "Periksa pesan galat Anda:\n\n"
                . "Biasanya `AttributeError: 'NoneType' object has no attribute 'val'` terjadi ketika mencoba mengakses atribut pada simpul yang kosong (`None`).\n\n"
                . "Pastikan Anda selalu memeriksa `if node is None:` sebelum mengakses atribut `node.val`, `node.left`, atau `node.right`.";
        }

        return "Untuk modul **Praktikum Binary Tree**, fokuskan pada bagaimana setiap simpul terhubung melalui pointer `left` dan `right`.\n\n"
            . "Jika Anda ingin menguji metode `insert()`, coba visualisasikan pohon dengan angka-angka sederhana seperti `[8, 3, 10, 1, 6]`. Simpul mana yang menjadi anak kiri dan kanan dari akar `8`?";
    }
}
