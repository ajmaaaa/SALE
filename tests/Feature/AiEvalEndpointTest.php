<?php

namespace Tests\Feature;

use App\Models\AiMessage;
use App\Models\AiThread;
use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiEvalEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_TOKEN = 'sale-eval-secret-token-32-chars-long-evaluation-key';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.v2' => true,
            'ai.single_call' => true,
            'ai.context' => true,
            'ai.threads' => true,
            'ai.key' => 'fake-test-key',
            'ai.eval_token' => self::VALID_TOKEN,
        ]);

        $adminRole = Role::create(['name' => Role::ADMIN, 'label' => 'System Admin']);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->actingAs($this->admin);
    }

    public function test_endpoint_returns_404_in_production_and_staging_environments(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $resProd = $this->postJson('/internal/ai-eval', [
            'question' => 'apa itu bst?',
        ], [
            'X-Eval-Token' => self::VALID_TOKEN,
        ]);
        $resProd->assertStatus(404);

        $this->app->detectEnvironment(fn () => 'staging');

        $resStaging = $this->postJson('/internal/ai-eval', [
            'question' => 'apa itu bst?',
        ], [
            'X-Eval-Token' => self::VALID_TOKEN,
        ]);
        $resStaging->assertStatus(404);
    }

    public function test_endpoint_fails_closed_when_token_is_short_or_uses_sample_value(): void
    {
        config(['ai.eval_token' => 'short-token']);
        $this->postJson('/internal/ai-eval', [
            'question' => 'apa itu bst?',
        ], [
            'X-Eval-Token' => 'short-token',
        ])->assertStatus(503);

        config(['ai.eval_token' => 'sale-eval-secret-token']);
        $this->postJson('/internal/ai-eval', [
            'question' => 'apa itu bst?',
        ], [
            'X-Eval-Token' => 'sale-eval-secret-token',
        ])->assertStatus(503);
    }

    public function test_endpoint_returns_401_when_eval_token_is_missing(): void
    {
        $response = $this->postJson('/internal/ai-eval', [
            'question' => 'apa itu bst?',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['message' => 'Unauthorized. Invalid or missing X-Eval-Token.']);
    }

    public function test_endpoint_rejects_unauthenticated_requests_even_with_valid_token(): void
    {
        auth()->logout();

        $this->postJson('/internal/ai-eval', [
            'question' => 'apa itu bst?',
        ], [
            'X-Eval-Token' => self::VALID_TOKEN,
        ])->assertStatus(401)
            ->assertJsonFragment(['message' => 'Unauthenticated. AI evaluation requires a System Admin session.']);
    }

    public function test_endpoint_returns_401_when_eval_token_is_invalid(): void
    {
        $response = $this->postJson('/internal/ai-eval', [
            'question' => 'apa itu bst?',
        ], [
            'X-Eval-Token' => 'wrong-token-that-is-at-least-32-chars-long-here',
        ]);

        $response->assertStatus(401);
    }

    public function test_endpoint_rejects_authenticated_non_admin_users(): void
    {
        $studentRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);
        $student = User::factory()->create(['role_id' => $studentRole->id]);

        $this->actingAs($student)->postJson('/internal/ai-eval', [
            'question' => 'apa itu bst?',
        ], [
            'X-Eval-Token' => self::VALID_TOKEN,
        ])->assertStatus(403);
    }

    public function test_endpoint_validates_assessment_existence_and_tenant_scope(): void
    {
        // 1. Non-existent assessment returns 404 for System Admin
        $this->postJson('/internal/ai-eval', [
            'assessment_id' => 999999,
            'question' => 'apa itu bst?',
        ], [
            'X-Eval-Token' => self::VALID_TOKEN,
        ])->assertStatus(404);

        // 2. Setup prodi and assessment
        $adminProdiRole = Role::create(['name' => Role::ADMIN_PRODI, 'label' => 'Admin Prodi']);
        $prodiA = Prodi::create(['code' => 'TI', 'name' => 'Teknologi Informasi']);
        $prodiB = Prodi::create(['code' => 'SI', 'name' => 'Sistem Informasi']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026', 'is_active' => true]);
        $mk = MataKuliah::create(['code' => 'TI101', 'name' => 'Struktur Data', 'prodi_id' => $prodiA->id, 'sks' => 3]);
        $section = ClassSection::create(['mata_kuliah_id' => $mk->id, 'semester_id' => $semester->id, 'section_code' => 'TI-A']);
        $assessment = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TUGAS-BST-01',
            'name' => 'Tugas BST',
            'type' => 'assignment',
            'final_weight' => 10,
        ]);

        $adminProdiB = User::factory()->create([
            'role_id' => $adminProdiRole->id,
            'prodi_id' => $prodiB->id,
        ]);

        // Cross-tenant access by Admin Prodi B is forbidden
        $this->actingAs($adminProdiB)->postJson('/internal/ai-eval', [
            'assessment_id' => $assessment->id,
            'question' => 'apa itu bst?',
        ], [
            'X-Eval-Token' => self::VALID_TOKEN,
        ])->assertStatus(403);
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
            'question' => 'apa itu BST?',
            'code' => '',
            'console_output' => '',
        ], [
            'X-Eval-Token' => self::VALID_TOKEN,
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
            'X-Eval-Token' => self::VALID_TOKEN,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'verdict' => 'off_topic',
            'reply' => 'Pertanyaan ini di luar tugas ini. Coba tanyakan hal yang terkait tugas.',
        ]);
    }
}
