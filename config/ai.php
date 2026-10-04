<?php

return [
    'enabled' => env('AI_ENABLED', false),
    'v2' => env('AI_TUTOR_V2', false),
    'key' => env('GEMINI_API_KEY', ''),
    'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
    'daily_tokens' => (int) env('AI_DAILY_TOKENS', 100000),
    'global_daily_tokens' => (int) env('AI_GLOBAL_DAILY_TOKENS', 1000000),
    'task_turns' => (int) env('AI_TASK_TURNS', 12),
    'threads' => (bool) env('AI_THREADS', false),
    'context' => (bool) env('AI_CONTEXT', false),
    'single_call' => (bool) env('AI_SINGLE_CALL', false),
    'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 500),
    'code_ratio_threshold' => (float) env('AI_CODE_RATIO_THRESHOLD', 0.25),
    'ngram_threshold' => (float) env('AI_NGRAM_THRESHOLD', 0.4),
    'max_reply_words' => (int) env('AI_MAX_REPLY_WORDS', 250),
    'reviewer_model' => env('AI_REVIEWER_MODEL', null),
    'block_threshold' => (int) env('AI_BLOCK_THRESHOLD', 5),
    'block_cooldown_seconds' => (int) env('AI_BLOCK_COOLDOWN_SECONDS', 120),
    'history_messages' => (int) env('AI_HISTORY_MESSAGES', 8),
    'thread_retention_days' => (int) env('AI_THREAD_RETENTION_DAYS', 180),
    'total_timeout' => (int) env('AI_TOTAL_TIMEOUT', 60),
    'chars_per_token' => (int) env('AI_CHARS_PER_TOKEN', 4),
    'reserve_padding' => (int) env('AI_RESERVE_PADDING', 256),
    'rpm' => (int) env('AI_RPM', 0),
    'eval_token' => env('AI_EVAL_TOKEN', ''),
    'eval_allowed_ips' => array_filter(array_map('trim', explode(',', (string) env('AI_EVAL_ALLOWED_IPS', '127.0.0.1,::1')))),
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
