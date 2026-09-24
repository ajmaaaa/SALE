<?php

namespace Tests\Feature;

use App\Support\AcademicPreview;
use Tests\TestCase;

class GradebookAttainmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->disableRoleGateForPreviewBehavior();
    }

    public function test_gradebook_filters_render_details_and_reject_cross_course_assessments(): void
    {
        $this->get('/dosen/gradebook?course=1')->assertOk()->assertSee('Telusuri penilaian')->assertSee('Ketercapaian CPMK');
        $this->get('/dosen/gradebook?course=1&component=tugas&assessment=1')->assertOk()->assertSee('Pemetaan soal')->assertSee('Lihat nilai')->assertDontSee('Simpan Semua Nilai');
        $this->get('/dosen/gradebook?course=1&component=uas')->assertOk()->assertSee('Belum ada penilaian pada komponen ini');
        $this->get('/dosen/gradebook?course=1&component=tugas&assessment=5')->assertStatus(422);
        $this->get('/dosen/gradebook?course=1&component=invalid')->assertStatus(422);
    }

    public function test_cpmk_thresholds_are_validated_and_saved(): void
    {
        $config = AcademicPreview::config(1);
        $config['cpmk'][0]['threshold'] = 101;
        $this->post('/dosen/course/1/akademik', $config)->assertSessionHasErrors('cpmk.0.threshold');
        $config['cpmk'][0]['threshold'] = 55;
        $this->post('/dosen/course/1/akademik', $config)->assertSessionHasNoErrors();
        $this->assertEquals(55, AcademicPreview::config(1)['cpmk'][0]['threshold']);
    }

    private function assessment(int $id, int $course, array $questions): array
    {
        return ['id' => $id, 'course' => $course, 'title' => "Assessment $id", 'type' => 'tugas', 'questions' => $questions];
    }

    public function test_criteria_are_point_weighted_and_outcomes_have_separate_thresholds(): void
    {
        session(['learning.items' => [
            10 => $this->assessment(10, 1, [
                ['prompt' => 'Small', 'cpmk' => 'CPMK-01', 'points' => 10],
                ['prompt' => 'Large', 'cpmk' => 'CPMK-01', 'points' => 30],
                ['prompt' => 'Application', 'cpmk' => 'CPMK-02', 'points' => 60],
            ]),
        ], 'academic.item_grades.10.1.points' => [10, 10, 42]]);

        $result = AcademicPreview::breakdown(1);
        $this->assertSame(['items', 'cpmk', 'complete', 'passed'], array_keys($result));
        $this->assertSame([10.0, 30.0, 60.0], array_column($result['items'][0]['questions'], 'weight'));
        $this->assertSame(62.0, $result['items'][0]['score']);
        $this->assertSame([50.0, 70.0], array_column($result['cpmk'], 'score'));
        $this->assertSame([50, 80], array_column($result['cpmk'], 'threshold'));
        $this->assertSame([true, false], array_column($result['cpmk'], 'passed'));
        $this->assertTrue($result['complete']);
        $this->assertFalse($result['passed']);

        session(['academic.item_grades.10.1.points' => [10, 10, 48]]);
        $this->assertTrue(AcademicPreview::breakdown(1)['passed']);
    }

    public function test_missing_grades_are_not_zero_and_a_complete_failure_can_fail_early(): void
    {
        session(['learning.items' => [10 => $this->assessment(10, 1, [
            ['prompt' => 'First', 'cpmk' => 'CPMK-01', 'points' => 40],
            ['prompt' => 'Second', 'cpmk' => 'CPMK-02', 'points' => 60],
        ])]]);
        $missing = AcademicPreview::breakdown(1);
        $this->assertSame([null, null], array_column($missing['items'][0]['questions'], 'earned'));
        $this->assertNull($missing['items'][0]['score']);
        $this->assertSame([null, null], array_column($missing['cpmk'], 'score'));
        $this->assertFalse($missing['complete']);
        $this->assertNull($missing['passed']);

        session(['academic.item_grades.10.1.points' => [0, '']]);
        $partial = AcademicPreview::breakdown(1);
        $this->assertSame([0.0, null], array_column($partial['items'][0]['questions'], 'earned'));
        $this->assertSame([0.0, null], array_column($partial['cpmk'], 'score'));
        $this->assertFalse($partial['complete']);
        $this->assertFalse($partial['passed']);

        session(['academic.item_grades.10.1.points' => [0, 0]]);
        $zero = AcademicPreview::breakdown(1);
        $this->assertSame(0.0, $zero['items'][0]['score']);
        $this->assertTrue($zero['complete']);
        $this->assertFalse($zero['passed']);
    }

    public function test_courses_students_and_non_grading_resources_are_isolated(): void
    {
        $question = [['prompt' => 'Criterion', 'cpmk' => 'CPMK-01', 'points' => 100]];
        session(['learning.items' => [
            10 => $this->assessment(10, 1, $question),
            11 => $this->assessment(11, 2, $question),
            12 => array_replace($this->assessment(12, 1, $question), ['type' => 'materi']),
            13 => array_replace($this->assessment(13, 1, $question), ['type' => 'pengumuman']),
        ], 'academic.item_grades.10.1.points' => [90], 'academic.item_grades.11.1.points' => [20],
            'academic.item_grades.10.2.points' => [30], 'academic.item_grades.12.1.points' => [0]]);

        $first = AcademicPreview::breakdown(1);
        $this->assertSame([10], array_column($first['items'], 'id'));
        $this->assertSame(90.0, $first['items'][0]['score']);
        $this->assertSame(20.0, AcademicPreview::breakdown(2)['items'][0]['score']);
        $this->assertSame(30.0, AcademicPreview::breakdown(1, 2)['items'][0]['score']);
        $this->assertNull(AcademicPreview::breakdown(1, 3)['items'][0]['score']);
        $this->assertSame(90.0, AcademicPreview::scores(1)['tugas']);
        $this->assertSame(0, $first['cpmk'][1]['max']);
        $this->assertNull($first['cpmk'][1]['score']);
        $this->assertFalse($first['complete']);
        $this->assertNull($first['passed']);
    }

    public function test_items_average_equally_but_cpmk_aggregates_points_across_items(): void
    {
        session(['learning.items' => [
            10 => $this->assessment(10, 1, [['prompt' => 'Small', 'cpmk' => 'CPMK-01', 'points' => 20]]),
            11 => $this->assessment(11, 1, [['prompt' => 'Large', 'cpmk' => 'CPMK-01', 'points' => 100]]),
        ], 'academic.item_grades.10.1.points' => [20], 'academic.item_grades.11.1.points' => [50]]);

        $this->assertSame(75.0, AcademicPreview::scores(1)['tugas']);
        $outcome = AcademicPreview::breakdown(1)['cpmk'][0];
        $this->assertEquals(70, $outcome['earned']);
        $this->assertEquals(120, $outcome['max']);
        $this->assertSame(58.33, $outcome['score']);
    }

    public function test_manual_component_coverage_cannot_hide_ungraded_items(): void
    {
        session(['academic.scores.1.1' => array_fill_keys(['tugas', 'kuis', 'uts', 'uas', 'proyek', 'partisipasi'], 100)]);
        $result = AcademicPreview::result(1);
        $this->assertEquals(100, $result['coverage']);
        $this->assertSame(100.0, $result['total']);
        $this->assertSame(100.0, $result['average']);
        $this->assertFalse($result['complete']);

        session(['academic.item_grades.1.1.points' => [15, 15, 15, 20, 20, 15]]);
        $this->assertTrue(AcademicPreview::result(1)['complete']);
    }

    public function test_seeded_legacy_mappings_and_old_session_thresholds(): void
    {
        $config = AcademicPreview::config(3);
        foreach ($config['cpmk'] as &$outcome) {
            unset($outcome['threshold']);
        }
        session(['academic.config.3' => $config, 'academic.item_grades.5.1.points' => [25, 20, 30, 25]]);

        $result = AcademicPreview::breakdown(3);
        $this->assertSame([65, 65], array_column($result['cpmk'], 'threshold'));
        $this->assertSame(['CPMK-01', 'CPMK-01', 'CPMK-02', 'CPMK-02'], array_column($result['items'][0]['questions'], 'cpmk'));
        $this->assertSame([45, 55], array_column($result['cpmk'], 'max'));
        $this->assertTrue($result['complete']);
        $this->assertTrue($result['passed']);
    }

    public function test_questionless_assessments_use_item_points_without_inventing_a_mapping(): void
    {
        session(['academic.item_grades.4.1.points' => [75]]);
        $result = AcademicPreview::breakdown(2);
        $this->assertCount(1, $result['items'][0]['questions']);
        $this->assertSame(100.0, $result['items'][0]['questions'][0]['weight']);
        $this->assertSame(75.0, $result['items'][0]['score']);
        $this->assertSame([null, null], array_column($result['cpmk'], 'score'));
        $this->assertFalse($result['complete']);
        $this->assertNull($result['passed']);
    }
}
