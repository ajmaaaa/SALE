<?php

namespace App\Providers;

use App\Models\ClassEnrollmentAppeal;
use App\Models\ClassSection;
use App\Models\Message;
use App\Models\Semester;
use App\Models\SystemSetting;
use App\Policies\ClassSectionPolicy;
use App\Policies\MessagePolicy;
use App\Services\DatabaseNotificationService;
use App\Support\DosenNavigation;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(ClassSection::class, ClassSectionPolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);

        View::composer('layouts.mahasiswa', function ($view) {
            static $activeSemesterName = null;
            if ($activeSemesterName === null && Schema::hasTable('semesters')) {
                $activeSemesterName = Semester::where('is_active', true)->value('name') ?? 'Belum ditetapkan';
            }
            $view->with('activeSemesterName', $activeSemesterName ?? 'Belum ditetapkan');

            $pendingAppealCount = 0;
            if (request()->is('admin-prodi*') && Schema::hasTable('class_enrollment_appeals')) {
                $sidebarProdi = auth()->user()?->managingProdi ?? auth()->user()?->prodi;
                $pendingAppealCount = ClassEnrollmentAppeal::where('status', ClassEnrollmentAppeal::STATUS_PENDING)
                    ->when($sidebarProdi, fn ($q) => $q->whereHas('classSection.mataKuliah', fn ($mk) => $mk->where('prodi_id', $sidebarProdi->id)))
                    ->count();
            }
            $view->with('pendingAppealCount', $pendingAppealCount);

            $sidebar = [
                'pendingGradingCount' => 0,
                'dosenForumUnread' => 0,
                'dosenForumMention' => 0,
                'dosenUnreadNotifCount' => 0,
                'forumUnreadCount' => 0,
                'forumMentionCount' => 0,
                'pendingTaskCount' => 0,
                'unreadNotifCount' => 0,
            ];
            $user = auth()->user();
            if ($user) {
                $isDosenWorkspace = request()->is('dosen*');
                $role = $isDosenWorkspace ? 'dosen' : 'mahasiswa';
                $notificationService = app(DatabaseNotificationService::class);
                $notifications = collect($notificationService->forUser($user, $role));
                $unread = $notifications->where('is_read', false);
                $discussionUnread = (int) $unread->where('category', 'diskusi')->sum('unread_count');
                if ($discussionUnread === 0) {
                    $discussionUnread = $unread->where('category', 'diskusi')->count();
                }
                $discussionMentions = (int) $unread->where('category', 'diskusi')->sum('mention_count');

                if ($isDosenWorkspace) {
                    $sidebar['pendingGradingCount'] = DosenNavigation::pendingGradingCount();
                    $sidebar['dosenForumUnread'] = $discussionUnread;
                    $sidebar['dosenForumMention'] = $discussionMentions;
                    $sidebar['dosenUnreadNotifCount'] = $unread->count();
                } else {
                    $sidebar['forumUnreadCount'] = $discussionUnread;
                    $sidebar['forumMentionCount'] = $discussionMentions;
                    $sidebar['pendingTaskCount'] = $notificationService->pendingTaskCount($user);
                    $sidebar['unreadNotifCount'] = $unread->count();
                }
            }
            $view->with($sidebar);
        });

        try {
            if (class_exists(SystemSetting::class) && Schema::hasTable('system_settings')) {
                $sessionLifetime = SystemSetting::valueFor('session_lifetime');
                if ($sessionLifetime && is_numeric($sessionLifetime) && (int) $sessionLifetime > 0) {
                    config(['session.lifetime' => (int) $sessionLifetime]);
                }

                $apiKey = trim((string) SystemSetting::valueFor('ai_api_key', ''));
                if (filled($apiKey)) {
                    config([
                        'ai.key' => $apiKey,
                        'ai.enabled' => true,
                    ]);
                }
                $model = trim((string) SystemSetting::valueFor('ai_model', ''));
                if (filled($model)) {
                    if (in_array($model, ['gemini-2.5-flash', 'gemini-2.5-pro', 'gemini-2.5-flash-lite'], true)) {
                        $model = 'gemini-3.6-flash';
                    }
                    config(['ai.model' => $model]);
                }
                $quota = SystemSetting::valueFor('ai_token_quota');
                if ($quota && is_numeric($quota) && (int) $quota > 0) {
                    config([
                        'ai.daily_tokens' => (int) $quota,
                        'ai.global_daily_tokens' => (int) $quota,
                    ]);
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
