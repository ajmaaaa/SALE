<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** Optional server runner. Never execute submitted code on the Laravel host. */
class CodeRunner
{
    public function run(int $taskId, string $studentCode): array
    {
        $base = ['stdout' => '', 'stderr' => '', 'output' => '', 'exit_code' => null,
            'language' => 'python', 'version' => '', 'error' => null];
        $url = config('ai.piston_url');
        if (! $url) {
            return array_replace($base, ['error' => 'Runner server belum dikonfigurasi. Gunakan Jalankan di editor untuk latihan Python.']);
        }
        if ($taskId !== 1) {
            return array_replace($base, ['error' => 'Pengujian server belum tersedia untuk tugas ini.']);
        }
        try {
            $response = Http::acceptJson()->connectTimeout(3)->timeout(15)->post(rtrim($url, '/').'/execute', [
                'language' => 'python', 'version' => config('ai.piston_version', '*'),
                'files' => [['name' => 'main.py', 'content' => $studentCode."\n\n".file_get_contents(__DIR__.'/TestSuites/task_1.py')]],
                'run_timeout' => 10000, 'run_cpu_time' => 10000, 'run_memory_limit' => 134217728,
            ]);
            if (! $response->successful()) {
                return array_replace($base, ['error' => 'Runner server tidak tersedia. Periksa konfigurasi endpoint Piston.']);
            }
            $run = $response->json('run');
            if (! is_array($run) || ! is_string($run['stdout'] ?? null) || ! is_string($run['stderr'] ?? null)) {
                return array_replace($base, ['error' => 'Respons runner server tidak valid.']);
            }
            return array_replace($base, ['stdout' => $run['stdout'], 'stderr' => $run['stderr'],
                'output' => $run['stdout'].$run['stderr'], 'exit_code' => $run['code'] ?? null,
                'version' => (string) $response->json('version', ''),
                'error' => ! empty($run['signal']) ? 'Eksekusi dihentikan oleh runner (batas waktu atau sumber daya).' : null]);
        } catch (ConnectionException) {
            return array_replace($base, ['error' => 'Tidak dapat terhubung ke runner server.']);
        }
    }
}
