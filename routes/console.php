<?php

use App\Models\User;
use App\Services\Ai\GeminiEvaluationBenchmark;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:grant {email} {assignment=1} {--task-file= : JSON file containing title and body for a new task}', function () {
    $email = strtolower(trim($this->argument('email')));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Email tidak valid.');

        return 1;
    }
    $id = (int) $this->argument('assignment');
    if ($id < 1) {
        $this->error('ID tugas harus positif.');

        return 1;
    }
    $task = DB::table('ai_tasks')->where('id', $id)->first();
    if (! $task) {
        $file = $this->option('task-file');
        $data = $file ? json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR) : ($id === 1 ? [
            'title' => 'Praktikum Binary Tree',
            'body' => 'Lengkapi metode insert() pada Binary Search Tree. Jelaskan penanganan cabang kiri, kanan, dan nilai duplikat.',
        ] : []);
        Validator::make($data, ['title' => 'required|string|max:255', 'body' => 'required|string|max:8000'])->validate();
        DB::table('ai_tasks')->insert(['id' => $id, 'title' => $data['title'], 'body' => $data['body']]);
    }
    $user = User::where('email', $email)->first();
    if (! $user) {
        $password = $this->secret('Password akun AI (minimal 12 karakter)');
        if (mb_strlen($password ?? '') < 12) {
            $this->error('Password minimal 12 karakter.');

            return 1;
        }
        $user = User::create(['name' => $email, 'email' => $email, 'password' => $password]);
    }
    DB::table('ai_access')->insertOrIgnore(['user_id' => $user->id, 'task_id' => $id]);
    $this->info('Akses AI diberikan. Masuk melalui panel Lumina AI di room tugas.');
})->purpose('Create a password-protected AI account and grant access to an authoritative task');

Artisan::command('ai:revoke {email} {assignment}', function () {
    $user = User::where('email', strtolower(trim($this->argument('email'))))->first();
    if ($user) {
        DB::table('ai_access')->where('user_id', $user->id)->where('task_id', (int) $this->argument('assignment'))->delete();
    }
    $this->info('Akses tugas dicabut. Riwayat dan kuota tetap disimpan.');
})->purpose('Revoke access without resetting assistance history or usage');

Artisan::command('ai:reset {email?} {--all : Reset seluruh kuota dan riwayat AI}', function () {
    if ($this->option('all')) {
        if (Schema::hasTable('ai_api_calls')) {
            DB::table('ai_api_calls')->truncate();
        }
        DB::table('ai_turns')->truncate();
        DB::table('ai_usage')->truncate();
        Cache::flush();
        $this->info('Seluruh riwayat chat dan kuota AI berhasil di-reset.');

        return 0;
    }

    $email = $this->argument('email') ? strtolower(trim($this->argument('email'))) : 'coba.ai.9c0f0c@sale.test';
    $user = User::where('email', $email)->first();
    if (! $user) {
        $this->error("User dengan email {$email} tidak ditemukan.");

        return 1;
    }

    if (Schema::hasTable('ai_api_calls')) {
        DB::table('ai_api_calls')->where('user_id', $user->id)->delete();
    }
    DB::table('ai_turns')->where('user_id', $user->id)->delete();
    DB::table('ai_usage')->where('scope', 'user:'.$user->id)->update(['tokens' => 0]);
    DB::table('ai_usage')->where('scope', 'global')->update(['tokens' => 0]);
    Cache::forget('ai:user:'.$user->id);
    Cache::forget('ai:budget');

    $this->info("Kuota token dan riwayat percakapan untuk {$email} berhasil di-reset.");

    return 0;
})->purpose('Reset AI token usage and conversation turns for testing');

Artisan::command('ai:usage-report {--days=30 : Number of calendar days to include}', function () {
    $days = max(1, min(366, (int) $this->option('days')));
    if (! Schema::hasTable('ai_api_calls')) {
        $this->error('Tabel ai_api_calls belum tersedia. Jalankan php artisan migrate.');

        return 1;
    }

    $query = DB::table('ai_api_calls')->where('created_at', '>=', now()->subDays($days - 1)->startOfDay());
    $rows = (clone $query)
        ->selectRaw('feature, stage, model, COUNT(*) as calls, SUM(input_tokens) as input_tokens, SUM(output_tokens) as output_tokens, SUM(thinking_tokens) as thinking_tokens, SUM(total_tokens) as total_tokens, SUM(estimated_cost_usd) as cost_usd, AVG(latency_ms) as avg_latency_ms')
        ->groupBy('feature', 'stage', 'model')
        ->orderBy('feature')->orderBy('stage')->get();

    if ($rows->isEmpty()) {
        $this->warn('Belum ada panggilan AI tercatat pada periode ini.');

        return 0;
    }

    $this->table(
        ['Fitur/tahap', 'Model', 'Call', 'Input', 'Output', 'Thinking', 'Total', 'Rerata ms', 'Est. USD'],
        $rows->map(fn ($row) => [
            $row->feature.'/'.$row->stage,
            $row->model,
            number_format($row->calls),
            number_format($row->input_tokens),
            number_format($row->output_tokens),
            number_format($row->thinking_tokens),
            number_format($row->total_tokens),
            number_format($row->avg_latency_ms),
            '$'.number_format((float) $row->cost_usd, 6),
        ])->all()
    );

    $turnQuery = (clone $query)->whereNotNull('turn_id');
    $turns = (clone $turnQuery)->distinct()->count('turn_id');
    $confirmed = (clone $query)->where('usage_source', 'confirmed');
    $cost = (float) $confirmed->sum('estimated_cost_usd');
    $this->info('Total estimasi biaya terkonfirmasi: $'.number_format($cost, 6));
    if ($turns > 0) {
        $turnCost = (float) (clone $turnQuery)->where('usage_source', 'confirmed')->sum('estimated_cost_usd');
        $this->line('Chat mahasiswa unik: '.number_format($turns).' | rerata biaya/chat: $'.number_format($turnCost / $turns, 6));
    }
    $this->line('Baris tanpa usage provider memakai cadangan kuota dan tidak diberi estimasi biaya.');

    return 0;
})->purpose('Show actual token usage, latency, and estimated AI cost');

Artisan::command('ai:benchmark {file=docs/samples/ai-evaluation-benchmark.json} {--strategy=all : compact, detailed, or all} {--repeat=1 : Runs per sample and strategy} {--sample= : Run only one sample ID} {--live : Actually call the paid provider}', function (GeminiEvaluationBenchmark $benchmark) {
    $path = base_path($this->argument('file'));
    if (! is_file($path) || filesize($path) > 1_000_000) {
        $this->error('Berkas benchmark tidak ditemukan atau lebih dari 1 MB.');

        return 1;
    }
    $samples = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    Validator::make(['samples' => $samples], [
        'samples' => 'required|array|min:1|max:100',
        'samples.*.id' => 'required|string|max:100|distinct',
        'samples.*.type' => 'required|string|in:essay,code',
        'samples.*.question' => 'required|string|max:8000',
        'samples.*.rubric' => 'required|string|max:8000',
        'samples.*.answer' => 'required|string|max:20000',
        'samples.*.max_score' => 'required|numeric|min:1|max:1000',
        'samples.*.expected_score' => 'nullable|numeric|min:0',
    ])->validate();
    if (filled($this->option('sample'))) {
        $samples = array_values(array_filter($samples, fn ($sample) => $sample['id'] === $this->option('sample')));
        if ($samples === []) {
            $this->error('Sample ID tidak ditemukan dalam dataset.');

            return 1;
        }
    }
    $strategy = $this->option('strategy');
    if (! in_array($strategy, ['compact', 'detailed', 'all'], true)) {
        $this->error('Strategy harus compact, detailed, atau all.');

        return 1;
    }
    $strategies = $strategy === 'all' ? ['compact', 'detailed'] : [$strategy];
    $repeat = max(1, min(10, (int) $this->option('repeat')));
    $calls = count($samples) * count($strategies) * $repeat;

    if (! $this->option('live')) {
        $this->warn("Dry run: {$calls} panggilan API akan dilakukan. Tambahkan --live untuk memakai provider dan kemungkinan menimbulkan biaya.");
        $this->line('Model: '.config('ai.model').' | sampel: '.count($samples).' | strategi: '.implode(', ', $strategies).' | pengulangan: '.$repeat);

        return 0;
    }
    if (! config('ai.enabled') || blank(config('ai.key'))) {
        $this->error('Aktifkan AI_ENABLED dan isi GEMINI_API_KEY sebelum benchmark live.');

        return 1;
    }
    if (! Schema::hasTable('ai_api_calls')) {
        $this->error('Jalankan php artisan migrate agar pemakaian benchmark tercatat.');

        return 1;
    }

    $results = [];
    foreach ($samples as $sample) {
        foreach ($strategies as $selected) {
            for ($run = 1; $run <= $repeat; $run++) {
                $this->line("Menilai {$sample['id']} / {$selected} / run {$run}...");
                try {
                    $result = $benchmark->evaluate($sample, $selected);
                    $expected = isset($sample['expected_score']) ? (float) $sample['expected_score'] : null;
                    $results[] = [
                        'sample' => $sample['id'], 'type' => $sample['type'], 'strategy' => $selected, 'run' => $run,
                        'score' => $result['score'], 'expected_score' => $expected,
                        'absolute_error' => $expected === null ? null : abs($result['score'] - $expected),
                        ...$result,
                    ];
                } catch (Throwable $exception) {
                    $results[] = ['sample' => $sample['id'], 'type' => $sample['type'], 'strategy' => $selected, 'run' => $run, 'error' => $exception->getMessage()];
                    $this->error($exception->getMessage());
                }
            }
        }
    }

    $successful = collect($results)->whereNull('error');
    $this->table(
        ['Sampel', 'Strategi', 'Skor', 'Target', '|Selisih|', 'Input', 'Output+think', 'ms', 'Est. USD'],
        $successful->map(fn ($row) => [
            $row['sample'], $row['strategy'], number_format($row['score'], 1),
            $row['expected_score'] === null ? '-' : number_format($row['expected_score'], 1),
            $row['absolute_error'] === null ? '-' : number_format($row['absolute_error'], 1),
            number_format($row['input_tokens']), number_format($row['output_tokens'] + $row['thinking_tokens']),
            number_format($row['latency_ms']), '$'.number_format($row['estimated_cost_usd'], 6),
        ])->all()
    );

    $report = [
        'generated_at' => now()->toIso8601String(),
        'model' => config('ai.model'),
        'pricing_usd_per_million' => config('ai.pricing'),
        'results' => $results,
    ];
    $reportPath = 'ai-benchmarks/'.now()->format('Ymd-His').'.json';
    Storage::disk('local')->put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    $this->info('Laporan: '.Storage::disk('local')->path($reportPath));
    $this->info('Total estimasi: $'.number_format((float) $successful->sum('estimated_cost_usd'), 6));
    if ($successful->whereNotNull('absolute_error')->isNotEmpty()) {
        $this->line('Mean absolute error skor: '.number_format((float) $successful->whereNotNull('absolute_error')->avg('absolute_error'), 2));
    }

    return $successful->count() === $calls ? 0 : 1;
})->purpose('Compare compact and detailed AI grading using labeled essay/code samples');
