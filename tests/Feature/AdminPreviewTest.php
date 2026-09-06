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
        $this->post('/admin/pengguna', $user + ['id' => '4'])->assertSessionHasNoErrors();
        $this->get('/admin/aktivitas')->assertSee('Menyimpan pengguna Siti');
        $this->post('/admin/pengguna', $user)->assertSessionHasErrors('email');
        $this->post('/admin/pengguna', ['id' => 3, 'name' => 'Admin', 'email' => 'admin@example.test', 'number' => 'ADM001', 'role' => 'mahasiswa', 'status' => 'aktif'])->assertSessionHasErrors('role');
    }

    public function test_classes_require_correct_parent_and_student_roles(): void
    {
        $base = ['type' => 'kelas', 'name' => 'Kelas B', 'code' => 'IF204-B', 'status' => 'aktif', 'course' => 1];
        $this->post('/admin/akademik', $base + ['parent' => 1, 'students' => [1]])->assertSessionHasErrors('parent');
        $this->post('/admin/akademik', $base + ['parent' => 2, 'students' => [2]])->assertSessionHasErrors('students.0');
        $this->post('/admin/akademik', $base + ['parent' => 2, 'students' => [1]])->assertRedirect('/admin/akademik');
        $this->get('/admin/akademik')->assertSee('Kelas B');
        $this->get('/admin/laporan/export')->assertDownload('sale-rekap-akademik.csv');
    }
}
