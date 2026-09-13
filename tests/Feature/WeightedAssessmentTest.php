<?php

namespace Tests\Feature;

use App\Support\AcademicPreview;
use Tests\TestCase;

class WeightedAssessmentTest extends TestCase
{
    private function configure(array $components = ['tugas'=>100]): void
    {
        $config = [
            'cpl'=>[
                ['code'=>'CPL-01','description'=>'CPL 1'],
                ['code'=>'CPL-02','description'=>'CPL 2'],
            ],
            'cpmk'=>[
                ['code'=>'CPMK-01','cpl'=>'CPL-01','description'=>'CPMK 1','threshold'=>65],
                ['code'=>'CPMK-02','cpl'=>'CPL-02','description'=>'CPMK 2','threshold'=>65],
            ],
            'components'=>[],
        ];
        foreach ($components as $code=>$weight) {
            $config['components'][] = ['code'=>$code, 'name'=>$code, 'weight'=>$weight];
        }
        session(['academic.config.1'=>$config]);
    }

    private function assessment(array $points, string $component = 'tugas', float $weight = 1): array
    {
        $questions = [];
        foreach ($points as $index=>$max) {
            $questions[] = ['prompt'=>"Criterion $index", 'points'=>$max, 'cpmk'=>sprintf('CPMK-%02d', $index + 1)];
        }

        return ['course'=>1, 'title'=>'Assessment', 'type'=>'tugas', 'component'=>$component,
            'assessment_weight'=>$weight, 'questions'=>$questions];
    }

    public function test_raw_points_and_per_assessment_outcomes_remain_separate(): void
    {
        $this->configure();
        session(['learning.items'=>[10=>$this->assessment([40, 60])],
            'academic.item_grades.10.1.points'=>[32, 42]]);

        $breakdown = AcademicPreview::breakdown(1);
        $item = $breakdown['items'][0];
        $this->assertSame(74.0, $item['score']);
        $this->assertSame([80.0, 70.0], array_column($item['cpmk'], 'score'));
        $this->assertEquals([40, 60], array_column($item['cpmk'], 'weight'));
        $this->assertEquals([40, 60], array_column($item['cpmk'], 'course_weight'));
        $this->assertSame(['CPL-01', 'CPL-02'], array_column($item['cpmk'], 'cpl'));
        $this->assertSame(74.0, AcademicPreview::result(1)['total']);
        $this->assertSame([32, 42], session('academic.item_grades.10.1.points'));
        $this->assertNull(AcademicPreview::breakdown(1, 2)['items'][0]['score']);
    }

    public function test_relative_weights_and_point_scale_do_not_pool_raw_points(): void
    {
        $this->configure(['tugas'=>30]);
        $items = [10=>$this->assessment([20]), 11=>$this->assessment([100])];
        session(['learning.items'=>$items, 'academic.item_grades.10.1.points'=>[16],
            'academic.item_grades.11.1.points'=>[60]]);
        $this->assertSame(70.0, AcademicPreview::breakdown(1)['cpmk'][0]['score']);
        $this->assertSame(70.0, AcademicPreview::scores(1)['tugas']);

        $items[11]['assessment_weight'] = 2;
        session(['learning.items'=>$items]);
        $outcome = AcademicPreview::breakdown(1)['cpmk'][0];
        $this->assertEqualsWithDelta(66.6666666667, $outcome['contribution'] / $outcome['weight'] * 100, 0.00000001);
        $this->assertEquals([10, 20], array_column(AcademicPreview::breakdown(1)['items'], 'course_weight'));
        $this->assertSame(66.67, AcademicPreview::scores(1)['tugas']);
        $this->assertSame(20.0, AcademicPreview::result(1)['total']);
        $this->assertFalse(AcademicPreview::result(1)['complete']);

        $items[10]['questions'][0]['points'] = 100;
        session(['learning.items'=>$items, 'academic.item_grades.10.1.points'=>[80]]);
        $this->assertSame($outcome['score'], AcademicPreview::breakdown(1)['cpmk'][0]['score']);
        $this->assertSame(20.0, AcademicPreview::result(1)['total']);
    }

    public function test_cross_component_outcomes_use_effective_course_point_shares(): void
    {
        $this->configure(['tugas'=>20, 'kuis'=>80]);
        session(['learning.items'=>[10=>$this->assessment([20, 80]), 11=>$this->assessment([80, 20], 'quiz')],
            'academic.item_grades.10.1.points'=>[20, 80], 'academic.item_grades.11.1.points'=>[0, 0]]);

        $breakdown = AcademicPreview::breakdown(1);
        $this->assertSame([5.88, 50.0], array_column($breakdown['cpmk'], 'score'));
        $this->assertEquals([68, 32], array_column($breakdown['cpmk'], 'weight'));
        $this->assertEquals([4, 16], array_column($breakdown['cpmk'], 'contribution'));
        $this->assertSame(20.0, AcademicPreview::result(1)['total']);
        $this->assertSame('kuis', $breakdown['items'][1]['component']);
    }

    public function test_missing_assessments_have_no_manual_override_or_full_component_coverage(): void
    {
        $this->configure(['tugas'=>30, 'uts'=>70]);
        session(['learning.items'=>[10=>$this->assessment([100]), 11=>$this->assessment([100], 'tugas', 2)],
            'academic.scores.1.1'=>['tugas'=>100, 'uts'=>80], 'academic.item_grades.10.1.points'=>[80]]);
        $result = AcademicPreview::result(1);
        $this->assertSame(80.0, $result['scores']['tugas']);
        $this->assertEquals(80, $result['coverage']);
        $this->assertSame(64.0, $result['total']);
        $this->assertSame(80.0, $result['average']);
        $this->assertFalse($result['complete']);
        $this->assertNull(AcademicPreview::breakdown(1)['cpmk'][0]['score']);

        session(['academic.item_grades.10.1.points'=>[null]]);
        $this->assertNull(AcademicPreview::scores(1)['tugas']);
        session(['academic.item_grades.10.1.points'=>[0], 'academic.item_grades.11.1.points'=>[0]]);
        $this->assertSame(0.0, AcademicPreview::scores(1)['tugas']);
        $this->assertTrue(AcademicPreview::result(1)['complete']);
        $this->assertSame(0.0, AcademicPreview::breakdown(1)['cpmk'][0]['score']);
    }

    public function test_unused_outcomes_and_components_do_not_invent_evidence(): void
    {
        $this->configure(['tugas'=>30, 'uts'=>70]);
        session(['learning.items'=>[10=>$this->assessment([100])]]);
        $this->assertSame(['tugas'=>null], AcademicPreview::scores(1));
        $this->assertNull(AcademicPreview::result(1)['total']);
        $this->assertEquals(0, AcademicPreview::result(1)['coverage']);

        session(['academic.item_grades.10.1.points'=>[80]]);
        $breakdown = AcademicPreview::breakdown(1);
        $this->assertNull($breakdown['cpmk'][1]['score']);
        $this->assertEquals(0, $breakdown['cpmk'][1]['weight']);
        $this->assertFalse($breakdown['complete']);
        $this->assertNull($breakdown['passed']);
        $this->assertEquals(30, AcademicPreview::result(1)['coverage']);
        $this->assertFalse(AcademicPreview::result(1)['complete']);
    }

    public function test_arbitrary_configured_outcomes_and_full_precision_thresholds(): void
    {
        $this->configure();
        $config = AcademicPreview::config(1);
        $config['cpmk'][] = ['code'=>'ANALYSIS', 'cpl'=>'CPL-01', 'description'=>'Analyze', 'threshold'=>80];
        session(['academic.config.1'=>$config]);
        $item = $this->assessment([100]);
        $item['questions'][0]['cpmk'] = 'ANALYSIS';
        session(['learning.items'=>[10=>$item], 'academic.item_grades.10.1.points'=>[79.999]]);
        $breakdown = AcademicPreview::breakdown(1);
        $this->assertSame(80.0, $breakdown['cpmk'][2]['score']);
        $this->assertFalse($breakdown['cpmk'][2]['passed']);
        $this->assertFalse($breakdown['items'][0]['cpmk'][0]['passed']);
        $this->assertFalse($breakdown['passed']);
    }

    public function test_legacy_helpers_and_invalid_mappings_preserve_safe_numeric_grades(): void
    {
        $this->configure();
        $item = ['course'=>1, 'type'=>'coding', 'title'=>'Legacy', 'points'=>20];
        $this->assertSame('tugas', AcademicPreview::component($item));
        $this->assertSame('proyek', AcademicPreview::component(['component'=>'project']));
        $this->assertSame('CPMK-01', AcademicPreview::cpmkCode('CPMK 1'));
        $this->assertNull(AcademicPreview::cpmkCode(null));
        $this->assertCount(1, AcademicPreview::questions($item));
        foreach ([null, 'UNKNOWN'] as $code) {
            $item['cpmk'] = $code;
            session(['learning.items'=>[10=>$item], 'academic.item_grades.10.1.points'=>[15]]);
            $breakdown = AcademicPreview::breakdown(1);
            $this->assertSame(75.0, $breakdown['items'][0]['score']);
            $this->assertFalse($breakdown['items'][0]['mapping_valid']);
            $this->assertFalse($breakdown['complete']);
            $this->assertNull($breakdown['passed']);
            $this->assertSame(75.0, AcademicPreview::result(1)['total']);
            $this->assertFalse(AcademicPreview::result(1)['complete']);
        }
    }

    public function test_invalid_relative_weights_and_component_mappings_cannot_complete(): void
    {
        $this->configure();
        foreach ([['tugas', 0], ['tugas', -1], ['missing', 1]] as [$component, $weight]) {
            session(['learning.items'=>[10=>$this->assessment([100], $component, $weight)],
                'academic.item_grades.10.1.points'=>[100]]);
            $this->assertFalse(AcademicPreview::breakdown(1)['items'][0]['mapping_valid']);
            $this->assertFalse(AcademicPreview::result(1)['complete']);
            $this->assertNotSame(true, AcademicPreview::breakdown(1)['passed']);
        }
    }

    public function test_aggregates_do_not_reuse_rounded_assessment_scores(): void
    {
        $this->configure();
        session(['learning.items'=>[10=>$this->assessment([100]), 11=>$this->assessment([100])],
            'academic.item_grades.10.1.points'=>[0.0049], 'academic.item_grades.11.1.points'=>[0.005]]);
        $breakdown = AcademicPreview::breakdown(1);
        $this->assertSame([0.0, 0.01], array_column($breakdown['items'], 'score'));
        $this->assertSame(0.0, $breakdown['cpmk'][0]['score']);
        $this->assertSame(0.0, AcademicPreview::cpl(1)[0]['score']);
        $this->assertSame(0.0, AcademicPreview::scores(1)['tugas']);
        $this->assertSame(0.0, AcademicPreview::result(1)['total']);
    }

    public function test_zero_weight_criteria_and_components_do_not_block_weighted_completion(): void
    {
        $this->configure(['tugas'=>100, 'kuis'=>0]);
        $item = $this->assessment([40, 60, 0]);
        $item['questions'][2]['cpmk'] = 'CPMK-01';
        session(['learning.items'=>[10=>$item, 11=>$this->assessment([100], 'kuis')],
            'academic.item_grades.10.1.points'=>[32, 48, null]]);
        $breakdown = AcademicPreview::breakdown(1);
        $this->assertTrue($breakdown['items'][0]['complete']);
        $this->assertFalse($breakdown['items'][1]['complete']);
        $this->assertTrue($breakdown['complete']);
        $this->assertTrue($breakdown['passed']);
        $this->assertSame(80.0, $breakdown['cpmk'][0]['score']);
        $this->assertTrue(AcademicPreview::result(1)['complete']);
    }

    public function test_perfect_quarter_course_is_provisional_and_zero_is_not_missing(): void
    {
        $this->configure(['tugas'=>25, 'uts'=>75]);
        session(['learning.items'=>[10=>$this->assessment([40, 60])],
            'academic.item_grades.10.1.points'=>[40, 60]]);
        $result = AcademicPreview::result(1);
        $this->assertEquals(25, $result['coverage']);
        $this->assertSame(25.0, $result['total']);
        $this->assertSame(100.0, $result['average']);
        $this->assertFalse($result['complete']);
        $this->assertTrue(AcademicPreview::breakdown(1)['passed']);

        session(['academic.scores.1.1'=>['uts'=>null]]);
        $this->assertFalse(AcademicPreview::result(1)['complete']);
        session(['academic.scores.1.1'=>['uts'=>0]]);
        $result = AcademicPreview::result(1);
        $this->assertEquals(100, $result['coverage']);
        $this->assertSame(25.0, $result['average']);
        $this->assertTrue($result['complete']);
        $this->assertEquals([10, 15], array_column(AcademicPreview::cpl(1), 'weight'));
    }

    public function test_full_matrix_reconstructs_course_and_cpl_with_40_60_weights(): void
    {
        $this->configure(['tugas'=>40, 'kuis'=>60]);
        session(['learning.items'=>[10=>$this->assessment([40, 60]), 11=>$this->assessment([40, 60], 'kuis')],
            'academic.item_grades.10.1.points'=>[32, 42], 'academic.item_grades.11.1.points'=>[32, 42]]);
        $result = AcademicPreview::result(1);
        $breakdown = AcademicPreview::breakdown(1);
        $cpl = AcademicPreview::cpl(1);
        $this->assertSame(['items', 'cpmk', 'complete', 'passed'], array_keys($breakdown));
        $this->assertSame(['code', 'description', 'weight', 'score', 'complete'], array_keys($cpl[0]));
        $this->assertEquals([40, 60], array_column($cpl, 'weight'));
        $this->assertSame([80.0, 70.0], array_column($cpl, 'score'));
        $this->assertSame([true, true], array_column($cpl, 'complete'));
        $this->assertSame(74.0, $result['total']);
        $this->assertSame(74.0, $result['average']);
        $this->assertEquals(100, $result['coverage']);
        $this->assertTrue($result['complete']);
        $this->assertEqualsWithDelta($result['total'], array_sum(array_column($breakdown['cpmk'], 'contribution')), 0.000001);
        $this->assertEqualsWithDelta($result['total'], array_sum(array_map(fn ($outcome) => $outcome['score'] * $outcome['weight'] / 100, $cpl)), 0.000001);
        $this->assertSame([null, null], array_column(AcademicPreview::cpl(1, 2), 'score'));

        $config = AcademicPreview::config(1);
        $config['cpmk'][1]['cpl'] = 'CPL-01';
        session(['academic.config.1'=>$config]);
        $cpl = AcademicPreview::cpl(1);
        $this->assertEquals([100, 0], array_column($cpl, 'weight'));
        $this->assertSame([74.0, null], array_column($cpl, 'score'));
        $this->assertSame([true, false], array_column($cpl, 'complete'));
    }

    public function test_cpl_requires_evidence_and_preserves_zero_without_inventing_mappings(): void
    {
        $this->configure();
        session(['learning.items'=>[10=>$this->assessment([40, 60])],
            'academic.item_grades.10.1.points'=>[0, null]]);
        $this->assertSame([0.0, null], array_column(AcademicPreview::cpl(1), 'score'));
        $this->assertSame([true, false], array_column(AcademicPreview::cpl(1), 'complete'));
        $this->assertFalse(AcademicPreview::result(1)['complete']);
        session(['academic.item_grades.10.1.points'=>[0, 0]]);
        $this->assertSame([0.0, 0.0], array_column(AcademicPreview::cpl(1), 'score'));
        $this->assertTrue(AcademicPreview::result(1)['complete']);

        $config = AcademicPreview::config(1);
        $config['cpmk'][1]['cpl'] = 'UNKNOWN';
        session(['academic.config.1'=>$config]);
        $this->assertEquals([40, 0], array_column(AcademicPreview::cpl(1), 'weight'));
        $this->assertSame([0.0, null], array_column(AcademicPreview::cpl(1), 'score'));

        $config['cpmk'][1]['cpl'] = 'CPL-01';
        session(['academic.config.1'=>$config, 'learning.items'=>[10=>$this->assessment([100])]]);
        $this->assertSame([null, null], array_column(AcademicPreview::cpl(1), 'score'));
        $this->assertFalse(AcademicPreview::breakdown(1)['complete']);
    }

    public function test_full_coverage_tolerance_does_not_allow_invalid_weighted_mapping(): void
    {
        $this->configure(['tugas'=>99.9999]);
        session(['learning.items'=>[10=>$this->assessment([40, 60])],
            'academic.item_grades.10.1.points'=>[40, 60]]);
        $this->assertTrue(AcademicPreview::result(1)['complete']);
        $this->configure(['tugas'=>99.99]);
        $this->assertFalse(AcademicPreview::result(1)['complete']);
        $this->configure();
        $item = $this->assessment([40, 60]);
        $item['questions'][1]['cpmk'] = 'UNKNOWN';
        session(['learning.items'=>[10=>$item]]);
        $this->assertEquals(100, AcademicPreview::result(1)['coverage']);
        $this->assertFalse(AcademicPreview::result(1)['complete']);
    }
}
