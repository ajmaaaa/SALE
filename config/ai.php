<?php

return [
    'enabled' => env('AI_ENABLED', false),
    'key' => env('GEMINI_API_KEY', ''),
    'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
    'daily_tokens' => (int) env('AI_DAILY_TOKENS', 100000),
    'global_daily_tokens' => (int) env('AI_GLOBAL_DAILY_TOKENS', 1000000),
    'task_turns' => (int) env('AI_TASK_TURNS', 12),
    'piston_version' => env('PISTON_PYTHON_VERSION', '*'),
    'piston_url' => env('PISTON_URL', ''),
];
