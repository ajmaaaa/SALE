<?php

namespace Tests\Feature;

use App\Models\AiMessage;
use App\Models\AiThread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiEvalEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.v2' => true,
            'ai.single_call' => true,
            'ai.context' => true,
            'ai.threads' => true,
            'ai.key' => 'fake-test-key',
            'ai.eval_token' => 'test-secret-eval-token',
        ]);
    }

    public function test_endpoint_returns_404_in_production_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->postJson('/internal/ai-eval', [
            'question' => 'apa itu bst?',
        ], [
            'X-Eval-Token' => 'test-secret-eval-token',
        ]);

        $response->assertStatus(404);
    }

    public function test_endpoint_returns_401_when_eval_token_is_missing(): void
    {
        $response = $this->postJson('/internal/ai-eval', [
            'question' => 'apa itu bst?',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['message' => 'Unauthorized. Invalid or missing X-Eval-Token.']);
    }

    public function test_endpoint_returns_401_when_eval_token_is_invalid(): void
    {
        $response = $this->postJson('/internal/ai-eval', [
            'question' => 'apa itu bst?',
        ], [
            'X-Eval-Token' => 'wrong-token',
        ]);

        $response->assertStatus(401);
    }

    public function test_endpoint_runs_evaluation_without_saving_thread_message_or_charging_quota(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'verdict' => 'ok',
                                        'hint_level' => 1,
                                        'reply' => 'Binary Search Tree adalah struktur data pohon biner di mana setiap simpul kiri lebih kecil dari induk dan kanan lebih besar.',
                                    ]),
                                ],
                            ],
                        ],
                        'finishReason' => 'STOP',
                    ],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 120,
                    'candidatesTokenCount' => 45,
                    'totalTokenCount' => 165,
                ],
            ], 200),
        ]);

        $initialThreadsCount = AiThread::count();
        $initialMessagesCount = AiMessage::count();
        $initialUsageCount = DB::table('ai_usage')->count();

        $response = $this->postJson('/internal/ai-eval', [
            'assessment_id' => 81,
            'question' => 'apa itu BST?',
            'code' => '',
            'console_output' => '',
        ], [
            'X-Eval-Token' => 'test-secret-eval-token',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'verdict' => 'ok',
            'hint_level' => 1,
            'reviewed' => false,
        ]);
        $this->assertStringContainsString('Binary Search Tree', $response->json('reply'));

        // Assert non-destructive behavior: no threads, messages, or quota increments
        $this->assertSame($initialThreadsCount, AiThread::count());
        $this->assertSame($initialMessagesCount, AiMessage::count());
        $this->assertSame($initialUsageCount, DB::table('ai_usage')->count());
    }

    public function test_endpoint_returns_refusal_when_model_outputs_off_topic_verdict(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'verdict' => 'off_topic',
                                        'hint_level' => 1,
                                        'reply' => 'resep rendang enak',
                                    ]),
                                ],
                            ],
                        ],
                        'finishReason' => 'STOP',
                    ],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 100,
                    'candidatesTokenCount' => 20,
                    'totalTokenCount' => 120,
                ],
            ], 200),
        ]);

        $response = $this->postJson('/internal/ai-eval', [
            'question' => 'resep rendang daging sapi',
        ], [
            'X-Eval-Token' => 'test-secret-eval-token',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'verdict' => 'off_topic',
            'reply' => 'Pertanyaan ini di luar tugas ini. Coba tanyakan hal yang terkait tugas.',
        ]);
    }
}
