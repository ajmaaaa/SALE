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
        // 1. Main Laporan page
        $response = $this->get('/admin/laporan');
        $response->assertOk();
        $response->assertSee(route('admin.laporan.fakultas'));
        $response->assertSee(route('admin.laporan.prodi'));

        // Semester row removed from table
        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/<tbody>.*?Semester.*?<\/tbody>/s', $content);

        // 2. Dedicated Fakultas page (/admin/laporan/fakultas)
        $fakultasResp = $this->get('/admin/laporan/fakultas');
        $fakultasResp->assertOk();
        $fakultasResp->assertSee('Nama Fakultas');
        $fakultasResp->assertSee('Nama Prodi');
        $fakultasResp->assertSee('Jumlah Prodi');
        $fakultasResp->assertSee('Dekan');
        $fakultasResp->assertSee('Wakil');
        $fakultasResp->assertSee('Jumlah Mahasiswa');
        $fakultasResp->assertSee('id="prodi-dropdown-menu"', false);
        $fakultasResp->assertSee('S1 Teknik Informatika');
        $fakultasResp->assertSee('S1 Sistem Informasi');

        // 3. Dedicated Prodi page (/admin/laporan/prodi)
        $prodiResp = $this->get('/admin/laporan/prodi');
        $prodiResp->assertOk();
        $prodiResp->assertSee('Nama Prodi');
        $prodiResp->assertSee('Kaprodi');
        $prodiResp->assertSee('Wakil');
        $prodiResp->assertSee('Semester');
        $prodiResp->assertSee('Mata Kuliah');
        $prodiResp->assertSee('Jumlah Mahasiswa');
        $prodiResp->assertSee('IPK Rata-Rata');
        $prodiResp->assertSee('3,58');
    }
}
