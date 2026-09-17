<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminPreviewTest extends TestCase
{
    public function test_admin_pages_render_and_unknown_sections_are_rejected(): void
    {
        foreach (['dashboard', 'akademik', 'pengguna', 'aktivitas', 'monitoring', 'laporan', 'pengaturan'] as $page) {
            $this->get('/admin/'.$page)->assertOk();
        }
        $this->get('/admin/unknown')->assertNotFound();
    }

    public function test_user_changes_are_logged_and_last_admin_is_preserved(): void
    {
        $user = ['name' => 'Siti', 'email' => 'siti@example.test', 'number' => 'M002', 'role' => 'mahasiswa', 'status' => 'aktif'];
        $this->post('/admin/pengguna', $user)->assertRedirect('/admin/pengguna');
        $newId = max(array_keys(session('admin.users')));
        $this->post('/admin/pengguna', $user + ['id' => (string) $newId])->assertSessionHasNoErrors();
        $this->get('/admin/aktivitas')->assertSee('Menyimpan pengguna Siti');
        $this->post('/admin/pengguna', $user)->assertSessionHasErrors('email');
        $this->post('/admin/pengguna', ['id' => 3, 'name' => 'Admin', 'email' => 'admin@example.test', 'number' => 'ADM001', 'role' => 'mahasiswa', 'status' => 'aktif'])->assertSessionHasErrors('role');
    }

    public function test_prodi_requires_correct_parent_fakultas(): void
    {
        $base = ['type' => 'prodi', 'name' => 'Sistem Informasi', 'code' => 'SI', 'status' => 'aktif'];
        $this->post('/admin/akademik', $base + ['parent' => 999])->assertSessionHasErrors('parent');
        $this->post('/admin/akademik', $base + ['parent' => 1])->assertRedirect('/admin/akademik');
        $this->get('/admin/akademik')->assertSee('Sistem Informasi');
        $this->get('/admin/laporan/export')->assertDownload('sale-rekap-akademik.csv');
    }

    public function test_academic_can_be_deleted(): void
    {
        // 1. Delete via DELETE route
        $this->delete('/admin/akademik/3')->assertRedirect('/admin/akademik');
        $this->get('/admin/akademik')->assertDontSee('2026-1');

        // 2. Delete via POST action=delete
        $this->post('/admin/akademik', ['action' => 'delete', 'id' => 2])->assertRedirect('/admin/akademik');
        $this->get('/admin/akademik')->assertSee('berhasil dihapus');
        $this->assertArrayNotHasKey(2, session('admin.academic'));
    }

    public function test_admin_laporan_displays_clickable_fakultas_prodi_details_and_removes_semester(): void
    {
        $response = $this->get('/admin/laporan');
        $response->assertOk();

        // 1. Fakultas clickable with detail fields
        $response->assertSee('toggleAcademicDetail(\'fakultas\')', false);
        $response->assertSee('id="detail-fakultas"', false);
        $response->assertSee('Nama Fakultas');
        $response->assertSee('Jumlah Prodi');
        $response->assertSee('Dekan');
        $response->assertSee('Wakil');
        $response->assertSee('Jumlah Mahasiswa');

        // 2. Prodi clickable with detail fields
        $response->assertSee('toggleAcademicDetail(\'prodi\')', false);
        $response->assertSee('id="detail-prodi"', false);
        $response->assertSee('Nama Prodi');
        $response->assertSee('Kaprodi');
        $response->assertSee('Wakil');
        $response->assertSee('IPK Rata-Rata');

        // 3. Semester row removed from table
        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/<tbody>.*?Semester.*?<\/tbody>/s', $content);
    }
}
