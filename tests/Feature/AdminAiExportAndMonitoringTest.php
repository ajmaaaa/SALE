<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAiExportAndMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $adminRole = Role::where('name', Role::ADMIN)->firstOrFail();
        $this->admin = User::create([
            'name' => 'Admin Utama',
            'email' => 'admin-utama@test.local',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'nim_nidn' => 'ADM001',
            'is_active' => true,
        ]);
        $this->admin->roles()->sync([$adminRole->id]);
    }

    public function test_admin_can_access_dashboard_without_server_health(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertOk();
        $response->assertDontSee('Beban Server & Ketersediaan');
        $response->assertSee('Pemantauan Token');
    }

    public function test_admin_can_access_monitoring_without_server_load_and_without_simulation_label(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/monitoring');

        $response->assertOk();
        $response->assertDontSee('Tidak ada data simulasi');
        $response->assertDontSee('Beban sistem');
        $response->assertSee('Pemakaian AI');
        $response->assertSee('Backup & pemulihan');
    }

    public function test_admin_monitoring_ai_detail_has_export_button(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/monitoring?detail=ai');

        $response->assertOk();
        $response->assertSee('Unduh Rekap AI (Excel)');
        $response->assertSee(route('admin.export.ai'));
    }

    public function test_admin_laporan_does_not_have_export_ai_button(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/laporan');

        $response->assertOk();
        $response->assertDontSee('Unduh Rekap AI (Excel)');
        $response->assertDontSee(route('admin.export.ai'));
    }

    public function test_admin_can_download_ai_excel_report(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/laporan/export-ai');

        $response->assertOk();
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );
        $this->assertStringContainsString('sale-rekap-penggunaan-ai.xlsx', $response->headers->get('content-disposition'));

        // Verify spreadsheet readability
        $tempPath = tempnam(sys_get_temp_dir(), 'ai_export_test_') . '.xlsx';
        file_put_contents($tempPath, $response->streamedContent());

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($tempPath);

        $this->assertTrue($spreadsheet->sheetNameExists('Log Panggilan AI'));
        $this->assertTrue($spreadsheet->sheetNameExists('Ringkasan Per Modul'));

        $logSheet = $spreadsheet->getSheetByName('Log Panggilan AI');
        $this->assertEquals('REKAPITULASI PENGGUNAAN LAYANAN & TOKEN AI', $logSheet->getCell('A1')->getValue());
        $this->assertEquals('Waktu Panggilan', $logSheet->getCell('B5')->getValue());
        $this->assertEquals('Modul / Fitur', $logSheet->getCell('C5')->getValue());

        $summarySheet = $spreadsheet->getSheetByName('Ringkasan Per Modul');
        $this->assertEquals('RINGKASAN PEMAKAIAN AI BERDASARKAN MODUL', $summarySheet->getCell('A1')->getValue());
        $this->assertEquals('Modul / Fitur', $summarySheet->getCell('A3')->getValue());

        @unlink($tempPath);
    }

    public function test_admin_can_download_academic_excel_report(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/laporan/export');

        $response->assertOk();
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );
        $this->assertStringContainsString('sale-rekap-akademik.xlsx', $response->headers->get('content-disposition'));

        $tempPath = tempnam(sys_get_temp_dir(), 'academic_export_test_') . '.xlsx';
        file_put_contents($tempPath, $response->streamedContent());

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($tempPath);

        $this->assertTrue($spreadsheet->sheetNameExists('Rekap Akademik'));
        $sheet = $spreadsheet->getSheetByName('Rekap Akademik');
        $this->assertEquals('REKAPITULASI DATA STRUKTUR AKADEMIK', $sheet->getCell('A1')->getValue());
        $this->assertEquals('Jenis', $sheet->getCell('A5')->getValue());
        $this->assertEquals('Kode', $sheet->getCell('B5')->getValue());

        @unlink($tempPath);
    }

    public function test_admin_monitoring_backup_detail_shows_settings_and_restore_info(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/monitoring?detail=backup');

        $response->assertOk();
        $response->assertSee('Backup &amp; pemulihan', false);
        $response->assertSee('Buat backup server');
        $response->assertSee(route('admin.backup.create'));
        $response->assertSee(route('admin.backup.restore'));
        $response->assertSee('Pengaturan Jadwal &amp; Path Backup Otomatis', false);
        $response->assertSee(route('admin.backup.settings'));
        $response->assertSee(route('admin.backup.destroy'));
        $response->assertSee('Pulihkan dari Berkas Cadangan Eksternal');
    }

    public function test_admin_can_save_backup_settings(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/monitoring/backup/settings', [
            'backup_path' => 'storage/app/custom_backups',
            'backup_schedule' => 'weekly',
            'backup_time' => '03:30',
        ]);

        $response->assertRedirect('/admin/monitoring?detail=backup');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('system_settings', [
            'key' => 'backup_path',
            'value' => 'storage/app/custom_backups',
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'backup_schedule',
            'value' => 'weekly',
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'backup_time',
            'value' => '03:30',
        ]);
    }

    public function test_admin_can_delete_backup(): void
    {
        // Create dummy backup file
        $dir = \App\Http\Controllers\AdminPreviewController::getBackupDirectory();
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $testFile = 'sale-backup-test-dummy.sql';
        file_put_contents($dir . '/' . $testFile, '-- test dummy');

        $this->assertFileExists($dir . '/' . $testFile);

        $response = $this->actingAs($this->admin)->post('/admin/monitoring/backup/delete', [
            'filename' => $testFile,
        ]);

        $response->assertRedirect('/admin/monitoring?detail=backup');
        $response->assertSessionHas('status');
        $this->assertFileDoesNotExist($dir . '/' . $testFile);
    }

    public function test_admin_can_create_server_backup(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/monitoring/backup');

        $response->assertRedirect('/admin/monitoring?detail=backup');
        $response->assertSessionHas('status');
    }

    public function test_admin_can_download_database_sql_dump(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/monitoring/backup-sql');

        $response->assertOk();
        $this->assertEquals('application/sql', $response->headers->get('content-type'));
        $this->assertStringContainsString('.sql', $response->headers->get('content-disposition'));
        $this->assertNotEmpty($response->streamedContent());
    }

    public function test_sale_backup_artisan_command_generates_sql_dump(): void
    {
        $exitCode = \Illuminate\Support\Facades\Artisan::call('sale:backup');
        $this->assertEquals(0, $exitCode);
    }
}
