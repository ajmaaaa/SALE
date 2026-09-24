<?php

namespace Tests\Feature;

use App\Services\Ai\AiUsageRecorder;
use App\Services\Ai\GeminiEvaluationBenchmark;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class AiEvaluationBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'ai.enabled' => true,
            'ai.key' => 'test-secret',
            'ai.model' => 'gemini-3.6-flash',
            'ai.pricing.input_per_million_usd' => 0.75,
            'ai.pricing.cached_input_per_million_usd' => 0.075,
            'ai.pricing.output_per_million_usd' => 3.75,
        ]);
        Http::preventStrayRequests();
    }

    private function providerResult(float $score = 80, string $finishReason = 'STOP'): array
    {
        return [
            'candidates' => [[
                'finishReason' => $finishReason,
                'content' => ['parts' => [['text' => json_encode([
                    'score' => $score,
                    'feedback' => ['Periksa kembali edge case.'],
                    'rationale' => 'Sebagian besar rubrik terpenuhi.',
                ], JSON_THROW_ON_ERROR)]]],
            ]],
            'usageMetadata' => [
                'promptTokenCount' => 200,
                'cachedContentTokenCount' => 50,
                'candidatesTokenCount' => 40,
                'thoughtsTokenCount' => 10,
                'totalTokenCount' => 250,
            ],
            'modelVersion' => 'benchmark-test-model',
        ];
    }

    private function sample(): array
    {
        return [
            'id' => 'code-test',
            'type' => 'code',
            'question' => 'Buat fungsi genap.',
            'rubric' => 'Kebenaran 100.',
            'answer' => 'return n % 2 == 0',
            'max_score' => 100,
            'expected_score' => 80,
        ];
    }

    public function test_cost_formula_separates_cached_input_and_billed_thinking_tokens(): void
    {
        $cost = app(AiUsageRecorder::class)->estimatedCost(200, 50, 40, 10);

        $this->assertEqualsWithDelta(0.00030375, $cost, 0.000000001);
    }

    public function test_evaluation_clamps_score_records_provider_usage_and_never_writes_grade(): void
    {
        Http::fake(['*' => Http::response($this->providerResult(140))]);

        $result = app(GeminiEvaluationBenchmark::class)->evaluate($this->sample(), 'compact');

        $this->assertSame(100.0, $result['score']);
        $this->assertDatabaseHas('ai_api_calls', [
            'feature' => 'evaluation_benchmark',
            'stage' => 'code:compact',
            'status' => 'completed',
            'input_tokens' => 200,
            'cached_tokens' => 50,
            'output_tokens' => 40,
            'thinking_tokens' => 10,
            'total_tokens' => 250,
        ]);
        $this->assertDatabaseCount('student_assessment_scores', 0);
        $this->assertDatabaseCount('student_rubric_scores', 0);
    }

    public function test_incomplete_provider_output_is_recorded_and_rejected(): void
    {
        Http::fake(['*' => Http::response($this->providerResult(80, 'MAX_TOKENS'))]);

        try {
            app(GeminiEvaluationBenchmark::class)->evaluate($this->sample(), 'detailed');
            $this->fail('Incomplete output should not be accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('tidak lengkap', $exception->getMessage());
        }

        $this->assertDatabaseHas('ai_api_calls', [
            'stage' => 'code:detailed',
            'status' => 'incomplete',
            'finish_reason' => 'MAX_TOKENS',
        ]);
    }

    public function test_transient_503_is_retried_with_each_attempt_recorded(): void
    {
        Http::fakeSequence()
            ->push(['error' => ['message' => 'temporary']], 503)
            ->push($this->providerResult(), 200);

        $result = app(GeminiEvaluationBenchmark::class)->evaluate($this->sample(), 'compact');

        $this->assertSame(2, $result['attempts']);
        Http::assertSentCount(2);
        $this->assertDatabaseHas('ai_api_calls', ['status' => 'provider_error', 'total_tokens' => 0]);
        $this->assertDatabaseHas('ai_api_calls', ['status' => 'completed', 'total_tokens' => 250]);
    }

    public function test_dry_run_never_calls_provider_and_missing_key_blocks_live_run(): void
    {
        $this->artisan('ai:benchmark', ['--strategy' => 'compact'])
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();
        Http::assertNothingSent();

        config(['ai.key' => '']);
        $this->artisan('ai:benchmark', ['--strategy' => 'compact', '--live' => true])
            ->expectsOutputToContain('GEMINI_API_KEY')
            ->assertFailed();
        Http::assertNothingSent();
    }

    public function test_live_command_runs_all_samples_and_saves_an_auditable_report(): void
    {
        Storage::fake('local');
        Http::fake(fn (Request $request) => Http::response($this->providerResult()));

        $this->artisan('ai:benchmark', ['--strategy' => 'compact', '--repeat' => 1, '--live' => true])
            ->expectsOutputToContain('Total estimasi')
            ->expectsOutputToContain('Mean absolute error skor')
            ->assertSuccessful();

        Http::assertSentCount(6);
        $this->assertDatabaseCount('ai_api_calls', 6);
        $files = Storage::disk('local')->allFiles('ai-benchmarks');
        $this->assertCount(1, $files);
        $report = json_decode(Storage::disk('local')->get($files[0]), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('gemini-3.6-flash', $report['model']);
        $this->assertCount(6, $report['results']);
        $this->assertSame('compact', $report['results'][0]['strategy']);
    }

    public function test_benchmark_prompt_marks_student_content_as_data_and_uses_structured_output(): void
    {
        Http::fake(['*' => Http::response($this->providerResult())]);

        app(GeminiEvaluationBenchmark::class)->evaluate($this->sample(), 'compact');

        Http::assertSent(function (Request $request) {
            $payload = $request->data();
            $studentData = json_decode($payload['contents'][0]['parts'][0]['text'], true);

            return str_contains($payload['systemInstruction']['parts'][0]['text'], 'untrusted data')
                && $payload['generationConfig']['responseMimeType'] === 'application/json'
                && $payload['generationConfig']['responseSchema']['properties']['score']['type'] === 'NUMBER'
                && $studentData['student_answer'] === $this->sample()['answer'];
        });

        $columns = DB::getSchemaBuilder()->getColumnListing('ai_api_calls');
        $this->assertNotContains('question', $columns);
        $this->assertNotContains('answer', $columns);
        $this->assertNotContains('rubric', $columns);
    }
}
