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
use App\Services\Ai\AiErrorCode;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiTutorV2Phase3ThreadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'ai.enabled' => true,
            'ai.v2' => true,
            'ai.key' => 'test-secret',
            'ai.daily_tokens' => 100000,
            'ai.global_daily_tokens' => 1000000,
            'ai.threads' => true,
            'ai.history_messages' => 8,
            'ai.thread_retention_days' => 180,
        ]);
        $this->seed(RoleSeeder::class);
        Http::preventStrayRequests();
    }

    private function createEnvironment(string $assessmentType = 'tugas', string $studentStatus = 'enrolled', bool $isArchived = false): array
    {
        $prodi = Prodi::firstOrCreate(['code' => 'IF'], ['name' => 'Informatika']);
        $semester = Semester::firstOrCreate(['code' => '2026-1'], ['name' => 'Semester Gasal', 'is_active' => true]);
        $mataKuliah = MataKuliah::firstOrCreate(
            ['code' => 'IF101'],
            ['prodi_id' => $prodi->id, 'name' => 'Struktur Data', 'sks' => 3]
        );
        $dosen = User::create([
            'name' => 'Dosen Pengampu',
            'email' => 'dosen.'.uniqid().'@sale.test',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::DOSEN)->value('id'),
            'prodi_id' => $prodi->id,
            'nim_nidn' => 'DSN'.rand(100, 999),
        ]);
        $student = User::create([
            'name' => 'Mahasiswa Test',
            'email' => 'mhs.'.uniqid().'@sale.test',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::MAHASISWA)->value('id'),
            'prodi_id' => $prodi->id,
            'nim_nidn' => '2401'.rand(1000, 9999),
        ]);
        $section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $dosen->id,
            'section_code' => 'A'.rand(1, 999),
            'capacity' => 40,
            'archived_at' => $isArchived ? now()->subDay() : null,
        ]);

        $section->enrollmentRecords()->attach($student->id, ['status' => $studentStatus]);

        $assessment = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TUGAS-'.rand(10, 9999),
            'name' => 'Binary Search Tree Assignment',
            'type' => $assessmentType,
            'description' => 'Kerjakan tugas BST',
            'final_weight' => 10.0,
            'status' => Assessment::STATUS_PUBLISHED,
            'learning_payload' => [
                'type' => 'coding',
                'ai_enabled' => true,
            ],
        ]);

        return compact('prodi', 'semester', 'mataKuliah', 'dosen', 'student', 'section', 'assessment');
    }

    private function providerResult(string $text, int $tokens = 100): array
    {
        $input = (int) floor($tokens * 0.7);
        $output = (int) floor($tokens * 0.2);

        return [
            'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => $text]]]]],
            'usageMetadata' => [
                'promptTokenCount' => $input,
                'candidatesTokenCount' => $output,
                'thoughtsTokenCount' => $tokens - $input - $output,
                'totalTokenCount' => $tokens,
            ],
            'modelVersion' => 'gemini-3.6-flash',
        ];
    }

    private function fakeGateAndReview(string $answer = 'Penjelasan konsep BST.'): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent*' => function (HttpClientRequest $request) use ($answer) {
                $body = $request->body();
                if (str_contains($body, 'Classify this request')) {
                    return Http::response($this->providerResult('{"allow":true}', 50));
                }
                if (str_contains($body, 'independent reviewer')) {
                    return Http::response($this->providerResult('{"allow":true}', 50));
                }

                return Http::response($this->providerResult($answer, 150));
            },
        ]);
    }

    public function test_thread_is_unique_per_user_and_assessment(): void
    {
        $env = $this->createEnvironment();
        $student = $env['student'];
        $assessment = $env['assessment'];

        $this->fakeGateAndReview('Jawaban 1');
        $this->actingAs($student)->postJson("/ai/tasks/{$assessment->id}", [
            'question' => 'Pertanyaan 1',
        ])->assertOk();

        $this->fakeGateAndReview('Jawaban 2');
        $this->actingAs($student)->postJson("/ai/tasks/{$assessment->id}", [
            'question' => 'Pertanyaan 2',
        ])->assertOk();

        // Exactly 1 thread exists for this user and assessment
        $this->assertDatabaseCount('ai_threads', 1);
        $thread = AiThread::first();
        $this->assertEquals($student->id, $thread->user_id);
        $this->assertEquals($assessment->id, $thread->assessment_id);
        $this->assertEquals(2, $thread->turns);
        $this->assertDatabaseCount('ai_messages', 4); // 2 user + 2 assistant messages

        // Database unique constraint violation test
        try {
            AiThread::create([
                'user_id' => $student->id,
                'assessment_id' => $assessment->id,
                'class_section_id' => $env['section']->id,
            ]);
            $this->fail('Expected QueryException for duplicate unique(user_id, assessment_id)');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    public function test_one_thread_per_material_and_one_thread_per_task(): void
    {
        $envTask = $this->createEnvironment('tugas');
        $student = $envTask['student'];
        $taskAssessment = $envTask['assessment'];

        // Create a material in the same class section
        $materialAssessment = Assessment::create([
            'class_section_id' => $envTask['section']->id,
            'code' => 'MATERI-01',
            'name' => 'Materi Pohon Biner',
            'type' => 'materi',
            'description' => 'Materi bacaan BST',
            'final_weight' => 0.0,
            'status' => Assessment::STATUS_PUBLISHED,
            'learning_payload' => [
                'type' => 'coding',
                'material_mode' => 'coding',
                'ai_enabled' => true,
            ],
        ]);

        $this->fakeGateAndReview('Penjelasan tugas BST');
        $this->actingAs($student)->postJson("/ai/tasks/{$taskAssessment->id}", [
            'question' => 'Tanya soal tugas',
        ])->assertOk();

        $this->fakeGateAndReview('Penjelasan materi BST');
        $this->actingAs($student)->postJson("/ai/tasks/{$materialAssessment->id}", [
            'question' => 'Tanya soal materi',
        ])->assertOk();

        // One thread per task and one thread per material
        $this->assertDatabaseCount('ai_threads', 2);
        $this->assertDatabaseHas('ai_threads', [
            'user_id' => $student->id,
            'assessment_id' => $taskAssessment->id,
        ]);
        $this->assertDatabaseHas('ai_threads', [
            'user_id' => $student->id,
            'assessment_id' => $materialAssessment->id,
        ]);
    }

    public function test_user_cannot_view_or_delete_another_users_thread(): void
    {
        $env = $this->createEnvironment();
        $studentA = $env['student'];
        $assessment = $env['assessment'];

        // Student B in the same section
        $studentB = User::create([
            'name' => 'Mahasiswa Lain',
            'email' => 'mhs.b@sale.test',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::MAHASISWA)->value('id'),
            'prodi_id' => $env['prodi']->id,
            'nim_nidn' => '24019999',
        ]);
        $env['section']->enrollmentRecords()->attach($studentB->id, ['status' => 'enrolled']);

        // Student A creates a thread
        $this->fakeGateAndReview('Jawaban rahasia A');
        $this->actingAs($studentA)->postJson("/ai/tasks/{$assessment->id}", [
            'question' => 'Pertanyaan A',
        ])->assertOk();

        $threadA = AiThread::where('user_id', $studentA->id)->first();
        $this->assertNotNull($threadA);

        // Student B checks their thread - should return empty/null, not Student A's thread
        $resB = $this->actingAs($studentB)->getJson("/ai/tasks/{$assessment->id}/thread");
        $resB->assertOk();
        $this->assertNull($resB->json('thread'));
        $this->assertEmpty($resB->json('messages'));

        // Student B attempts to delete Student A's thread via delete endpoint
        $this->actingAs($studentB)->deleteJson("/ai/tasks/{$assessment->id}/thread")->assertStatus(404);

        // Thread A is intact
        $this->assertDatabaseHas('ai_threads', ['id' => $threadA->id]);
    }

    public function test_kicked_or_dropped_student_is_rejected(): void
    {
        // 1. Kicked student
        $envKicked = $this->createEnvironment('tugas', 'kicked');
        $this->actingAs($envKicked['student'])
            ->postJson("/ai/tasks/{$envKicked['assessment']->id}", ['question' => 'Bantu saya'])
            ->assertStatus(403);

        $this->actingAs($envKicked['student'])
            ->getJson("/ai/tasks/{$envKicked['assessment']->id}/thread")
            ->assertStatus(403);

        // 2. Dropped student
        $envDropped = $this->createEnvironment('tugas', 'dropped_self');
        $this->actingAs($envDropped['student'])
            ->postJson("/ai/tasks/{$envDropped['assessment']->id}", ['question' => 'Bantu saya'])
            ->assertStatus(403);

        $this->actingAs($envDropped['student'])
            ->getJson("/ai/tasks/{$envDropped['assessment']->id}/thread")
            ->assertStatus(403);
    }

    public function test_class_archived_is_read_only(): void
    {
        // Class is archived
        $env = $this->createEnvironment('tugas', 'enrolled', true);
        $student = $env['student'];
        $assessment = $env['assessment'];

        // Pre-seed an existing thread before archiving
        $thread = AiThread::create([
            'user_id' => $student->id,
            'assessment_id' => $assessment->id,
            'class_section_id' => $env['section']->id,
            'turns' => 1,
            'tokens_used' => 100,
        ]);
        AiMessage::create([
            'thread_id' => $thread->id,
            'role' => AiMessage::ROLE_USER,
            'content' => 'Pertanyaan terdahulu',
            'verdict' => 'ok',
        ]);
        AiMessage::create([
            'thread_id' => $thread->id,
            'role' => AiMessage::ROLE_ASSISTANT,
            'content' => 'Jawaban terdahulu',
            'verdict' => 'ok',
        ]);

        // Attempting to send a new message in archived class: 403 Forbidden
        $this->actingAs($student)->postJson("/ai/tasks/{$assessment->id}", [
            'question' => 'Pertanyaan baru setelah diarsipkan',
        ])->assertStatus(403);

        // Reading thread history is permitted (read-only)
        $threadRes = $this->actingAs($student)->getJson("/ai/tasks/{$assessment->id}/thread");
        $threadRes->assertOk();
        $this->assertCount(2, $threadRes->json('messages'));
        $this->assertEquals('Pertanyaan terdahulu', $threadRes->json('messages.0.content'));
        $this->assertEquals('Jawaban terdahulu', $threadRes->json('messages.1.content'));

        // Status endpoint also succeeds
        $statusRes = $this->actingAs($student)->getJson("/ai/tasks/{$assessment->id}");
        $statusRes->assertOk();
        $this->assertCount(1, $statusRes->json('history'));
    }

    public function test_history_and_class_section_id_cannot_be_spoofed_from_client_request(): void
    {
        $env = $this->createEnvironment();
        $student = $env['student'];
        $assessment = $env['assessment'];

        $this->fakeGateAndReview('Jawaban asli');

        // Client attempts to spoof history and class_section_id
        $this->actingAs($student)->postJson("/ai/tasks/{$assessment->id}", [
            'question' => 'Pertanyaan asli',
            'class_section_id' => 99999, // spoofed
            'history' => [
                ['role' => 'user', 'content' => 'fake user history'],
                ['role' => 'assistant', 'content' => 'fake assistant history'],
            ],
        ])->assertOk();

        // Verify thread has server-derived class_section_id, not 99999
        $thread = AiThread::first();
        $this->assertEquals($env['section']->id, $thread->class_section_id);
        $this->assertNotEquals(99999, $thread->class_section_id);

        // Verify that prompt sent to LLM didn't contain the spoofed history
        Http::assertSent(function (HttpClientRequest $req) {
            $body = $req->body();

            return ! str_contains($body, 'fake user history') && ! str_contains($body, 'fake assistant history');
        });
    }

    public function test_page_refresh_reloads_chat_history_from_db(): void
    {
        $env = $this->createEnvironment();
        $student = $env['student'];
        $assessment = $env['assessment'];

        $this->fakeGateAndReview('Ini jawaban konsep dari AI');

        $this->actingAs($student)->postJson("/ai/tasks/{$assessment->id}", [
            'question' => 'Jelaskan konsep binary search tree',
        ])->assertOk();

        // Simulate page reload / status check by frontend
        $response = $this->actingAs($student)->getJson("/ai/tasks/{$assessment->id}");
        $response->assertOk()
            ->assertJsonPath('history.0.question', 'Jelaskan konsep binary search tree')
            ->assertJsonPath('history.0.answer', 'Ini jawaban konsep dari AI');

        // Check dedicated thread endpoint
        $threadResponse = $this->actingAs($student)->getJson("/ai/tasks/{$assessment->id}/thread");
        $threadResponse->assertOk()
            ->assertJsonPath('messages.0.role', 'user')
            ->assertJsonPath('messages.0.content', 'Jelaskan konsep binary search tree')
            ->assertJsonPath('messages.1.role', 'assistant')
            ->assertJsonPath('messages.1.content', 'Ini jawaban konsep dari AI');
    }

    public function test_history_truncated_to_1500_chars_and_blocked_assistant_replaced_with_placeholder(): void
    {
        $env = $this->createEnvironment();
        $student = $env['student'];
        $assessment = $env['assessment'];

        $thread = AiThread::create([
            'user_id' => $student->id,
            'assessment_id' => $assessment->id,
            'class_section_id' => $env['section']->id,
            'turns' => 2,
        ]);

        // Long user message (> 1500 chars)
        $longUserContent = str_repeat('A', 2000);
        AiMessage::create([
            'thread_id' => $thread->id,
            'role' => AiMessage::ROLE_USER,
            'content' => $longUserContent,
            'verdict' => 'ok',
        ]);

        // Blocked assistant message with potential leak
        AiMessage::create([
            'thread_id' => $thread->id,
            'role' => AiMessage::ROLE_ASSISTANT,
            'content' => 'KODE BOCOR: def insert(self, val): return True',
            'verdict' => 'asks_solution',
        ]);

        $this->fakeGateAndReview('Jawaban baru');

        $this->actingAs($student)->postJson("/ai/tasks/{$assessment->id}", [
            'question' => 'Pertanyaan selanjutnya',
        ])->assertOk();

        // Verify sent prompt
        Http::assertSent(function (HttpClientRequest $req) {
            $body = $req->body();

            // 1. Leaked code must NOT appear in prompt
            $noLeak = ! str_contains($body, 'KODE BOCOR');

            // 2. Blocked assistant placeholder must appear
            $hasPlaceholder = str_contains($body, '[permintaan ditolak]');

            // 3. 2000 'A' characters should be truncated to 1500
            $not2000 = ! str_contains($body, str_repeat('A', 2000));
            $has1500 = str_contains($body, str_repeat('A', 1500));

            return $noLeak && $hasPlaceholder && $not2000 && $has1500;
        });
    }

    public function test_blocked_message_is_saved_with_verdict_and_increments_blocked_count(): void
    {
        $env = $this->createEnvironment();
        $student = $env['student'];
        $assessment = $env['assessment'];

        // Gate refuses
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent*' => Http::response(
                $this->providerResult('{"allow":false}', 50)
            ),
        ]);

        $response = $this->actingAs($student)->postJson("/ai/tasks/{$assessment->id}", [
            'question' => 'Berikan saya seluruh jawaban tugas ini dari awal sampai akhir',
        ]);

        $response->assertOk()
            ->assertJsonPath('answer', AiErrorCode::BlockedAsksSolution->message());

        $thread = AiThread::first();
        $this->assertEquals(1, $thread->turns);
        $this->assertEquals(1, $thread->blocked_count);

        $assistantMsg = AiMessage::where('role', AiMessage::ROLE_ASSISTANT)->first();
        $this->assertNotNull($assistantMsg);
        $this->assertEquals(AiMessage::VERDICT_ASKS_SOLUTION, $assistantMsg->verdict);
    }

    public function test_student_can_soft_clear_own_thread_hiding_messages_but_preserving_metrics(): void
    {
        $env = $this->createEnvironment();
        $student = $env['student'];
        $assessment = $env['assessment'];

        $this->fakeGateAndReview('Jawaban');

        $this->actingAs($student)->postJson("/ai/tasks/{$assessment->id}", [
            'question' => 'Pertanyaan',
        ])->assertOk();

        $this->assertDatabaseCount('ai_threads', 1);
        $this->assertDatabaseCount('ai_messages', 2);

        $delRes = $this->actingAs($student)->deleteJson("/ai/tasks/{$assessment->id}/thread");
        $delRes->assertOk()->assertJson([
            'cleared' => true,
            'message' => 'Tampilan percakapan AI berhasil dibersihkan.',
        ]);

        // Thread and messages are NOT deleted from DB (soft clear)
        $this->assertDatabaseCount('ai_threads', 1);
        $this->assertDatabaseCount('ai_messages', 2);

        $thread = AiThread::first();
        $this->assertNotNull($thread->cleared_at);
        $this->assertEquals(1, $thread->turns);

        // GET thread endpoint hides cleared messages
        $getRes = $this->actingAs($student)->getJson("/ai/tasks/{$assessment->id}/thread");
        $getRes->assertOk()
            ->assertJsonCount(0, 'messages')
            ->assertJsonCount(0, 'history');

        // GET status endpoint hides cleared turns but preserves remaining_turns based on thread->turns
        $statusRes = $this->actingAs($student)->getJson("/ai/tasks/{$assessment->id}");
        $statusRes->assertOk()
            ->assertJsonCount(0, 'history')
            ->assertJsonPath('remaining_turns', (int) config('ai.task_turns', 12) - 1);
    }

    public function test_thread_clearing_does_not_reset_turn_limit(): void
    {
        config(['ai.task_turns' => 2]);
        $env = $this->createEnvironment();
        $student = $env['student'];
        $assessment = $env['assessment'];

        $this->fakeGateAndReview('Jawaban 1');
        $this->actingAs($student)->postJson("/ai/tasks/{$assessment->id}", ['question' => 'Q1'])->assertOk();

        $this->fakeGateAndReview('Jawaban 2');
        $this->actingAs($student)->postJson("/ai/tasks/{$assessment->id}", ['question' => 'Q2'])->assertOk();

        // 2 turns used = limit reached
        $this->actingAs($student)->deleteJson("/ai/tasks/{$assessment->id}/thread")->assertOk();

        // Attempting to send another question must fail with 429 TurnLimit even after clearing thread
        $res = $this->actingAs($student)->postJson("/ai/tasks/{$assessment->id}", ['question' => 'Q3']);
        $res->assertStatus(429)
            ->assertJson([
                'error' => AiErrorCode::TurnLimit->value,
                'message' => AiErrorCode::TurnLimit->message(),
            ]);
    }

    public function test_prompt_history_is_maintained_after_soft_clear(): void
    {
        $env = $this->createEnvironment();
        $student = $env['student'];
        $assessment = $env['assessment'];

        $this->fakeGateAndReview('Jawaban pertama');
        $this->actingAs($student)->postJson("/ai/tasks/{$assessment->id}", [
            'question' => 'Pertanyaan awal yang penting',
        ])->assertOk();

        // Mahasiswa membersihkan tampilan chat
        $this->actingAs($student)->deleteJson("/ai/tasks/{$assessment->id}/thread")->assertOk();

        $this->fakeGateAndReview('Jawaban kedua');
        $this->actingAs($student)->postJson("/ai/tasks/{$assessment->id}", [
            'question' => 'Pertanyaan kedua',
        ])->assertOk();

        // Prompt yang dikirim ke LLM harus tetap mengandung "Pertanyaan awal yang penting"
        Http::assertSent(function (HttpClientRequest $req) {
            $body = $req->body();

            return str_contains($body, 'Pertanyaan awal yang penting');
        });
    }

    public function test_legacy_path_is_used_when_ai_threads_is_false(): void
    {
        config(['ai.threads' => false]);
        $env = $this->createEnvironment();
        $student = $env['student'];
        $assessment = $env['assessment'];

        $this->fakeGateAndReview('Jawaban legacy');
        $this->actingAs($student)->postJson("/ai/tasks/{$assessment->id}", [
            'question' => 'Pertanyaan legacy',
        ])->assertOk();

        // ai_threads dan ai_messages tidak dibuat
        $this->assertDatabaseCount('ai_threads', 0);
        $this->assertDatabaseCount('ai_messages', 0);

        // ai_turns dibuat
        $this->assertDatabaseHas('ai_turns', [
            'user_id' => $student->id,
            'question' => 'Pertanyaan legacy',
            'status' => 'answered',
        ]);
    }

    public function test_artisan_prune_threads_removes_old_threads_based_on_retention(): void
    {
        $env = $this->createEnvironment();
        $student = $env['student'];
        $assessment = $env['assessment'];

        // Thread 1: Old (200 days ago)
        $oldThread = AiThread::create([
            'user_id' => $student->id,
            'assessment_id' => $assessment->id,
            'class_section_id' => $env['section']->id,
            'turns' => 1,
        ]);
        DB::table('ai_threads')->where('id', $oldThread->id)->update([
            'created_at' => now()->subDays(200),
            'updated_at' => now()->subDays(200),
        ]);
        AiMessage::create([
            'thread_id' => $oldThread->id,
            'role' => AiMessage::ROLE_USER,
            'content' => 'Old message',
            'created_at' => now()->subDays(200),
        ]);

        // Run prune command with default 180 days
        $this->artisan('ai:prune-threads')
            ->expectsOutputToContain('Berhasil membersihkan 1 AI thread yang lebih lama dari 180 hari')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('ai_threads', ['id' => $oldThread->id]);
        $this->assertDatabaseCount('ai_messages', 0);
    }
}
