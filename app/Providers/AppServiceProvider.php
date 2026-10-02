<?php

namespace App\Providers;

use App\Models\ClassSection;
use App\Models\Message;
use App\Policies\ClassSectionPolicy;
use App\Policies\MessagePolicy;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(ClassSection::class, ClassSectionPolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);

        try {
            if (class_exists(\App\Models\SystemSetting::class) && \Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
                $sessionLifetime = \App\Models\SystemSetting::valueFor('session_lifetime');
                if ($sessionLifetime && is_numeric($sessionLifetime) && (int) $sessionLifetime > 0) {
                    config(['session.lifetime' => (int) $sessionLifetime]);
                }

                $apiKey = trim((string) \App\Models\SystemSetting::valueFor('ai_api_key', ''));
                if (filled($apiKey)) {
                    config(['ai.key' => $apiKey, 'ai.enabled' => true]);
                }
                $model = trim((string) \App\Models\SystemSetting::valueFor('ai_model', ''));
                if (filled($model)) {
                    if (in_array($model, ['gemini-2.5-flash', 'gemini-2.5-pro', 'gemini-2.5-flash-lite'], true)) {
                        $model = 'gemini-3.6-flash';
                    }
                    config(['ai.model' => $model]);
                }
                $quota = \App\Models\SystemSetting::valueFor('ai_token_quota');
                if ($quota && is_numeric($quota) && (int) $quota > 0) {
                    config(['ai.daily_tokens' => (int) $quota]);
                }
            }
        } catch (\Throwable) {
            // Abaikan jika database belum terhubung atau saat proses migrasi awal
        }

        $phpConfigDir = config('app.php_config_dir');
        if (! is_string($phpConfigDir) || $phpConfigDir === '' || ! is_dir($phpConfigDir)) {
            return;
        }

        if (class_exists(ServeCommand::class)) {
            ServeCommand::$passthroughVariables[] = 'PHPRC';
        }

        putenv('PHPRC='.$phpConfigDir);
        $_ENV['PHPRC'] = $phpConfigDir;
        $_SERVER['PHPRC'] = $phpConfigDir;
    }
}
