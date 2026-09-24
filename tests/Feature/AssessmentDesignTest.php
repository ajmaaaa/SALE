<?php

namespace Tests\Feature;

use Tests\TestCase;

class AssessmentDesignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->disableRoleGateForPreviewBehavior();
    }

    public function test_assessment_sheets_render_with_grading_controls(): void
    {
        foreach (['uas', 'uts', 'quiz', 'tugas', 'project'] as $type) {
            $response = $this->get(route('dosen.grades', ['course' => 1, 'type' => $type]));

            $response->assertOk()
                ->assertSee('assessment-sheet')
                ->assertSee('assessment-table-panel')
                ->assertSee('assessment-savebar')
                ->assertSee('WAKTU PENGUMPULAN')
                ->assertSee('AKSI DOKUMEN')
                ->assertSee('Cek Tugas')
                ->assertSee('data-submission-open')
                ->assertSee('data-grade-import-open')
                ->assertSee('id="grade-import"', false)
                ->assertSee('accept=".csv,.xlsx"', false)
                ->assertSee('Unduh Template CSV')
                ->assertSee('id="submission-preview"', false)
                ->assertDontSee('STATUS KELULUSAN')
                ->assertSee('id="student-search"', false)
                ->assertSee('id="student-status-filter"', false)
                ->assertSee('id="score-2024081001"', false)
                ->assertSee('data-status="memenuhi"', false)
                ->assertSee('data-status="evaluasi"', false)
                ->assertSee('onchange="recalcScore(this)"', false);

            $this->assertSame(6, substr_count($response->getContent(), 'class="student-row"'));
            $this->assertSame(12, substr_count($response->getContent(), 'data-threshold="'));
            $this->assertSame(6, substr_count($response->getContent(), 'class="assessment-document '));

            if (in_array($type, ['quiz', 'uts', 'project'], true)) {
                $response->assertSee('Belum tersedia');
            } else {
                $response->assertSee($type === 'tugas' ? '05 Sep 2026, 10:30 WIB' : '10 Jan 2027, 13:45 WIB');
            }
        }
    }
}
