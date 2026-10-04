<?php

return [
    'root' => env('SALE_BACKUP_ROOT', storage_path('app/private/backups')),
    'max_restore_kb' => (int) env('SALE_BACKUP_MAX_RESTORE_KB', 51200),
    'dump_binary' => env('SALE_DB_DUMP_BINARY'),
    'restore_binary' => env('SALE_DB_RESTORE_BINARY'),
    'dump_timeout' => (int) env('SALE_BACKUP_TIMEOUT', 300),
    'restore_timeout' => (int) env('SALE_RESTORE_TIMEOUT', 600),
];
