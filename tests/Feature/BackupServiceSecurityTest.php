<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BackupServiceSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => Role::ADMIN, 'label' => 'Admin']);
        $this->admin = User::factory()->create(['role_id' => $role->id]);
    }

    public function test_backup_root_is_strictly_outside_public_directories(): void
    {
        $root = BackupService::getBackupRoot();
        $this->assertDirectoryExists($root);

        $publicPath = rtrim(str_replace('\\', '/', public_path()), '/');
        $publicStorage = rtrim(str_replace('\\', '/', storage_path('app/public')), '/');

        $this->assertFalse(str_starts_with($root, $publicPath));
        $this->assertFalse(str_starts_with($root, $publicStorage));
    }

    public function test_public_prefix_collision_is_not_mistaken_for_public_directory(): void
    {
        $safeSibling = public_path().'ity-backups';
        config(['backup.root' => $safeSibling]);

        $this->assertSame(str_replace('\\', '/', $safeSibling), BackupService::getBackupRoot());

        @rmdir($safeSibling);
    }

    public function test_filename_validator_rejects_path_traversal_null_bytes_and_invalid_extensions(): void
    {
        $this->assertFalse(BackupService::isValidFilename('../../etc/passwd'));
        $this->assertFalse(BackupService::isValidFilename('backup.sql'."\0".'.php'));
        $this->assertFalse(BackupService::isValidFilename('sub/backup.sql'));
        $this->assertFalse(BackupService::isValidFilename('..\..\backup.sql'));
        $this->assertFalse(BackupService::isValidFilename('backup.exe'));
        $this->assertFalse(BackupService::isValidFilename(''));

        $this->assertTrue(BackupService::isValidFilename('sale-backup-2026-10-04.sql'));
        $this->assertTrue(BackupService::isValidFilename('backup_test-01.sql'));
    }

    public function test_backup_settings_rejects_absolute_path_and_public_path(): void
    {
        $resAbsolute = $this->actingAs($this->admin)->post('/admin/monitoring/backup/settings', [
            'backup_path' => '/var/log/sale-backups',
            'backup_schedule' => 'daily',
            'backup_time' => '02:00',
        ]);
        $resAbsolute->assertSessionHasErrors('backup_path');

        $resPublic = $this->actingAs($this->admin)->post('/admin/monitoring/backup/settings', [
            'backup_path' => 'public/secret_backups',
            'backup_schedule' => 'daily',
            'backup_time' => '02:00',
        ]);
        $resPublic->assertSessionHasErrors('backup_path');
    }

    public function test_backup_directory_rejects_symlink_escape(): void
    {
        $suffix = bin2hex(random_bytes(5));
        $root = storage_path("framework/testing/backup-root-{$suffix}");
        $outside = storage_path("framework/testing/backup-outside-{$suffix}");
        mkdir($root, 0700, true);
        mkdir($outside, 0700, true);
        symlink($outside, $root.'/escape');

        config(['backup.root' => $root]);
        SystemSetting::updateOrCreate(['key' => 'backup_path'], ['value' => 'escape']);

        $this->assertSame(realpath($root), BackupService::getBackupDirectory());

        unlink($root.'/escape');
        rmdir($outside);
        rmdir($root);
    }

    public function test_restore_rejects_empty_file_and_non_sql_file(): void
    {
        // 1. Empty file
        $emptyFile = UploadedFile::fake()->createWithContent('empty.sql', '');
        $resEmpty = $this->actingAs($this->admin)->post(route('admin.backup.restore'), [
            'sql_file' => $emptyFile,
        ]);
        $resEmpty->assertSessionHasErrors('sql_file');

        // 2. Non-SQL file (e.g. binary/executable or garbage)
        $garbageFile = UploadedFile::fake()->createWithContent('test.sql', 'THIS_IS_NOT_SQL_CONTENT_JUST_RANDOM_TEXT');
        $resGarbage = $this->actingAs($this->admin)->post(route('admin.backup.restore'), [
            'sql_file' => $garbageFile,
        ]);
        $resGarbage->assertSessionHasErrors('sql_file');
    }

    public function test_delete_backup_sanitizes_filename_and_prevents_arbitrary_file_deletion(): void
    {
        $this->assertFalse(BackupService::deleteBackup('../../composer.json'));
        $this->assertFileExists(base_path('composer.json'));

        $this->assertFalse(BackupService::deleteBackup('not-found-file.sql'));
    }

    public function test_backup_creates_file_with_strict_permissions(): void
    {
        $res = BackupService::createBackup('test-security-backup.sql');
        $this->assertTrue($res['success']);
        $this->assertFileExists($res['path']);

        $perms = fileperms($res['path']) & 0777;
        $this->assertSame(0600, $perms);

        // Cleanup
        BackupService::deleteBackup('test-security-backup.sql');
    }

    public function test_backup_fails_closed_when_dump_binary_is_missing(): void
    {
        config(['backup.dump_binary' => '/definitely/missing/database-dump']);

        $result = BackupService::createBackup('must-not-exist.sql');

        $this->assertFalse($result['success']);
        $this->assertFileDoesNotExist(BackupService::getBackupDirectory().'/must-not-exist.sql');
    }
}
