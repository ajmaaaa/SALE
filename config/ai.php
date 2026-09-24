<?php

return [
    'enabled' => env('AI_ENABLED', false),
    'key' => env('GEMINI_API_KEY', ''),
    'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
    'daily_tokens' => (int) env('AI_DAILY_TOKENS', 100000),
    'global_daily_tokens' => (int) env('AI_GLOBAL_DAILY_TOKENS', 1000000),
    'task_turns' => (int) env('AI_TASK_TURNS', 12),
    'benchmark_retries' => (int) env('AI_BENCHMARK_RETRIES', 2),
    // Gemini 3.6 Flash Standard paid-tier rates through 2026-12-31.
    // Keep these environment-configurable because provider prices can change.
    'pricing' => [
        'input_per_million_usd' => (float) env('AI_INPUT_PRICE_PER_MILLION_USD', 0.75),
        'cached_input_per_million_usd' => (float) env('AI_CACHED_INPUT_PRICE_PER_MILLION_USD', 0.075),
        'output_per_million_usd' => (float) env('AI_OUTPUT_PRICE_PER_MILLION_USD', 3.75),
    ],
    'piston_version' => env('PISTON_PYTHON_VERSION', '*'),
    'piston_url' => env('PISTON_URL', ''),
];
