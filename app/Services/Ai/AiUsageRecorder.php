<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AiUsageRecorder
{
    public function record(array $context, array $usage): void
    {
        try {
            if (! Schema::hasTable('ai_api_calls')) {
                return;
            }

            $input = max(0, (int) ($usage['input_tokens'] ?? 0));
            $cached = min($input, max(0, (int) ($usage['cached_tokens'] ?? 0)));
            $output = max(0, (int) ($usage['output_tokens'] ?? 0));
            $thinking = max(0, (int) ($usage['thinking_tokens'] ?? 0));
            $confirmed = ($usage['usage_source'] ?? 'confirmed') === 'confirmed';

            DB::table('ai_api_calls')->insert([
                'turn_id' => $context['turn_id'] ?? null,
                'user_id' => $context['user_id'] ?? null,
                'task_id' => $context['task_id'] ?? null,
                'feature' => $context['feature'] ?? 'tutor',
                'stage' => $context['stage'],
                'provider' => $context['provider'] ?? 'gemini',
                'model' => $context['model'],
                'model_version' => $usage['model_version'] ?? null,
                'status' => $usage['status'],
                'usage_source' => $usage['usage_source'] ?? 'confirmed',
                'input_tokens' => $input,
                'cached_tokens' => $cached,
                'output_tokens' => $output,
                'thinking_tokens' => $thinking,
                'total_tokens' => max(0, (int) ($usage['total_tokens'] ?? ($input + $output + $thinking))),
                'estimated_cost_usd' => $confirmed ? $this->estimatedCost($input, $cached, $output, $thinking) : null,
                'latency_ms' => max(0, (int) ($usage['latency_ms'] ?? 0)),
                'finish_reason' => $usage['finish_reason'] ?? null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            // Observability must never make an otherwise valid tutoring request fail.
            Log::warning('AI usage recording failed', ['exception' => get_class($exception)]);
        }
    }

    public function estimatedCost(int $input, int $cached, int $output, int $thinking): float
    {
        $uncached = max(0, $input - $cached);

        return round((
            $uncached * (float) config('ai.pricing.input_per_million_usd', 0)
            + $cached * (float) config('ai.pricing.cached_input_per_million_usd', 0)
            + ($output + $thinking) * (float) config('ai.pricing.output_per_million_usd', 0)
        ) / 1_000_000, 10);
    }
}
