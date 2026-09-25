<?php

namespace App\Providers;

use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Message::class, \App\Policies\MessagePolicy::class);

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
