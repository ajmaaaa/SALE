<?php

namespace App\Http\Controllers;

use App\Services\Ai\GeminiTutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AiTutorController extends Controller
{
    public function login(Request $request)
    {
        $request->validate(['assignment' => 'nullable|integer|min:1']);
        $destination = route('mahasiswa.assignment.code', $request->integer('assignment', 1));
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

        return redirect()->route('mahasiswa.assignment.code', $request->integer('assignment', 1));
    }

    private function task(Request $request, int $assignment): object
    {
        abort_unless($request->user(), 401, 'Masuk dengan akun AI terlebih dahulu.');
        abort_unless(DB::table('ai_access')->where('user_id', $request->user()->id)->where('task_id', $assignment)->exists(), 403, 'Akun ini belum mendapat akses AI untuk tugas ini.');
        $task = DB::table('ai_tasks')->where('id', $assignment)->where('enabled', true)->first();
        abort_unless($task, 403, 'AI untuk tugas ini tidak aktif.');

        return $task;
    }

    public function status(Request $request, int $assignment)
    {
        $task = $this->task($request, $assignment);
        $turns = DB::table('ai_turns')->where('user_id', $request->user()->id)->where('task_id', $task->id)->orderBy('id')->get(['question', 'answer', 'status']);
        $used = (int) DB::table('ai_usage')->where('scope', 'user:'.$request->user()->id)->where('day', now('UTC')->toDateString())->value('tokens');

        return response()->json([
            'enabled' => (bool) config('ai.enabled') && filled(config('ai.key')),
            'remaining_tokens' => max(0, config('ai.daily_tokens') - $used),
            'remaining_turns' => max(0, config('ai.task_turns') - $turns->count()),
            'reset_at' => now('UTC')->addDay()->startOfDay()->toIso8601String(),
            'history' => $turns,
        ]);
    }

    public function send(Request $request, int $assignment, GeminiTutor $tutor)
    {
        $task = $this->task($request, $assignment);
        abort_unless(config('ai.enabled') && filled(config('ai.key')), 503, 'AI belum diaktifkan oleh pengelola.');
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
                $answer = $tutor->answer($userId, $task, $input['question'], $input['code'] ?? '', $history);
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
}
