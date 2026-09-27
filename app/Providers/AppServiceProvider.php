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
