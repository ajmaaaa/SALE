<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFrontendTest extends TestCase
{
    public function test_the_application_redirects_to_the_login_page(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
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
            ->assertSee('Course diikuti')
            ->assertSee('Tugas belum dikerjakan')
            ->assertSee('Pesan belum dibaca')
            ->assertDontSee('Tenggat terdekat')
            ->assertDontSee('Nilai tersedia');

        $dosenDash = $this->get(route('dosen.dashboard'));
        $dosenDash->assertOk()
            ->assertSee('Jumlah Course (Matkul)')
            ->assertSee('Pesan belum dibaca')
            ->assertSee('Pesan masuk dari forum kelas')
            ->assertSee(route('dosen.course.index'))
            ->assertSee('Struktur Data dan Algoritma')
            ->assertSee('Salin kode')
            ->assertSee('Salin link')
            ->assertSee('copyEnrollmentCode(this)', false)
            ->assertSee('copyEnrollmentUrl(this)', false)
            ->assertDontSee('Sesi aktif:')
            ->assertDontSee('>Tutup</button>', false);
    }

    public function test_course_detail_page_layout_has_sidebar_discussion_and_no_lihat_nilai_saya(): void
    {
        $response = $this->get(route('mahasiswa.course.show', 1));
        $response->assertOk()
            ->assertDontSee('Lihat Nilai Saya')
            ->assertSee('Forum Diskusi Kelas')
            ->assertSee('diskusi-kelas')
            ->assertSee('Dosen Pengampu')
            ->assertSee('video-heading', false);
    }

    public function test_recent_discussions_links_on_dashboard_resolve_without_404(): void
    {
        $response = $this->get(route('mahasiswa.dashboard'));
        $response->assertOk();

        $recentDiscussions = \App\Support\LearningPreview::recentDiscussions();
        $this->assertNotEmpty($recentDiscussions);

        foreach ($recentDiscussions as $discussion) {
            $response->assertSee(route('mahasiswa.course.show', $discussion['course']).'#diskusi-kelas', false);
            $this->get(route('mahasiswa.course.show', $discussion['course']))->assertOk();
        }
    }

    public function test_forum_and_quiz_badges_and_discussion_read_state(): void
    {
        $dashboard = $this->get(route('mahasiswa.dashboard'));
        $dashboard->assertOk()
            ->assertSee('5 pesan belum dibaca')
            ->assertSee('3 tugas dan kuis belum dikerjakan')
            ->assertDontSee('Pak, untuk praktikum Binary Tree apakah implementasi delete node juga akan diuji pada kuis akhir nanti?');

        $course = $this->get(route('mahasiswa.course.show', 1));
        $course->assertOk()
            ->assertSee('3 pesan belum dibaca')
            ->assertSessionHas('learning.discussion_reads.1', 3)
            ->assertSee('Budi Santoso', false)
            ->assertSee('Ahmad Maulana', false);
    }

    public function test_dashboard_shows_empty_states_when_there_are_no_unread_messages_or_pending_work(): void
    {
        $submissions = collect(\App\Support\LearningPreview::items())
            ->filter(fn ($item) => in_array($item['type'], ['tugas', 'coding', 'kuis', 'uts', 'uas'], true))
            ->mapWithKeys(fn ($item) => [$item['id'] => ['answer' => 'selesai']])
            ->all();

        $response = $this->withSession([
            'learning.discussion_reads' => [1 => 3, 2 => 2, 3 => 1, 4 => 0],
            'learning.submissions' => $submissions,
        ])->get(route('mahasiswa.dashboard'));

        $response->assertOk()
            ->assertSee('Belum ada pesan terbaru.')
            ->assertDontSee('Belum ada tenggat terdekat.')
            ->assertDontSee('Tenggat terdekat');
    }
}
