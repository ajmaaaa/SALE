<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFrontendTest extends TestCase
{
    public function test_the_application_redirects_to_the_student_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('mahasiswa.dashboard'));
    }

    public function test_public_prototype_pages_are_available_with_security_headers(): void
    {
        $routes = [
            route('mahasiswa.dashboard'),
            route('mahasiswa.course.index'),
            route('mahasiswa.course.show', 1),
            route('mahasiswa.assignment.index'),
            route('mahasiswa.assignment.code', 1),
            route('mahasiswa.assignment.index', ['tab' => 'nilai']),
            route('mahasiswa.course.item', [1, 3]),
            route('dosen.dashboard'),
            route('dosen.course.index'),
            route('dosen.course.create'),
            route('dosen.course.show', 1),
            route('dosen.item.create', 1),
            route('dosen.grades'),
            route('mahasiswa.notifications'),
            route('mahasiswa.discussion.index'),
            route('mahasiswa.profile.index'),
        ];

        foreach ($routes as $route) {
            $this->get($route)
                ->assertOk()
                ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'")
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'DENY');
        }
    }

    public function test_unknown_prototype_resources_return_not_found(): void
    {
        $this->get(route('mahasiswa.course.show', 999))->assertNotFound();
        $this->get(route('mahasiswa.assignment.code', 999))->assertNotFound();
    }

    public function test_student_and_lecturer_dashboards_render_expected_sections(): void
    {
        $studentDash = $this->get(route('mahasiswa.dashboard'));
        $studentDash->assertOk()
            ->assertSee('Mata Kuliah Aktif')
            ->assertSee('Tugas Belum Dikerjakan')
            ->assertSee('Tenggat Terdekat')
            ->assertSee('Diskusi Terbaru');

        $dosenDash = $this->get(route('dosen.dashboard'));
        $dosenDash->assertOk()
            ->assertSee('Jumlah Course (Matkul)')
            ->assertSee(route('dosen.course.index'))
            ->assertSee('Struktur Data dan Algoritma');
    }

    public function test_course_detail_page_layout_has_sidebar_discussion_and_no_lihat_nilai_saya(): void
    {
        $response = $this->get(route('mahasiswa.course.show', 1));
        $response->assertOk()
            ->assertDontSee('Lihat Nilai Saya')
            ->assertSee('Forum Diskusi Kelas')
            ->assertSee('diskusi-kelas')
            ->assertSee('Dosen Pengampu')
            ->assertSee('Dr. Budi Santoso, M.Kom.');
    }
}
