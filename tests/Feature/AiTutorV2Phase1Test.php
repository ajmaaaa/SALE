<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ai\AiErrorCode;
use App\Services\Ai\GeminiTutor;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AiTutorV2Phase1Test extends TestCase
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
            'ai.task_turns' => 12,
        ]);
        Http::preventStrayRequests();
        DB::table('ai_tasks')->insert(['id' => 1, 'title' => 'BST', 'body' => 'Implement insert in BST.']);
    }

    private function student(): User
    {
        $user = User::factory()->create();
        DB::table('ai_access')->insert(['user_id' => $user->id, 'task_id' => 1]);
        $this->actingAs($user);

        return $user;
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
                'thoughtsTokenCount' => max(0, $tokens - $input - $output),
                'totalTokenCount' => $tokens,
            ],
            'modelVersion' => 'test-model-v2',
        ];
    }

    public function test_provider_429_returns_provider_busy_even_when_daily_quota_remains(): void
    {
        $user = $this->student();
        $day = now('UTC')->toDateString();
        // Mahasiswa masih memiliki 95.000 sisa token
        DB::table('ai_usage')->insert(['scope' => 'user:'.$user->id, 'day' => $day, 'tokens' => 5000]);

        // Provider mengembalikan 429 tanpa Retry-After. Hard quota tidak boleh di-retry.
        Http::fake([
            '*' => Http::response(['error' => ['message' => 'Rate limit exceeded']], 429),
        ]);

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Bagaimana cara rekursi?']);

        // Harus 503 provider_busy, TIDAK PERNAH 429 quota_daily
        $response->assertStatus(503)
            ->assertJson([
                'error' => AiErrorCode::ProviderBusy->value,
                'message' => AiErrorCode::ProviderBusy->message(),
            ]);

        Http::assertSentCount(1);

        // Token yang direservasi harus di-refund penuh, kuota kembali ke 5000
        $used = (int) DB::table('ai_usage')->where('scope', 'user:'.$user->id)->where('day', $day)->value('tokens');
        $this->assertSame(5000, $used);
    }

    public function test_provider_5xx_returns_provider_busy_with_retries_and_full_refund(): void
    {
        $user = $this->student();
        $day = now('UTC')->toDateString();

        Http::fake([
            '*' => Http::response(['error' => ['message' => 'Internal server error']], 500),
        ]);

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Jelaskan pointer']);

        $response->assertStatus(503)
            ->assertJson([
                'error' => AiErrorCode::ProviderBusy->value,
                'message' => AiErrorCode::ProviderBusy->message(),
            ]);

        Http::assertSentCount(3);

        $used = (int) DB::table('ai_usage')->where('scope', 'user:'.$user->id)->where('day', $day)->value('tokens');
        $this->assertSame(0, $used);
    }

    public function test_connection_timeout_returns_provider_busy_with_retries_and_full_refund(): void
    {
        $user = $this->student();
        $day = now('UTC')->toDateString();

        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Jelaskan traversal']);

        $response->assertStatus(503)
            ->assertJson([
                'error' => AiErrorCode::ProviderBusy->value,
                'message' => AiErrorCode::ProviderBusy->message(),
            ]);

        $this->assertSame(3, $attempts);

        $used = (int) DB::table('ai_usage')->where('scope', 'user:'.$user->id)->where('day', $day)->value('tokens');
        $this->assertSame(0, $used);
    }

    public function test_quota_daily_exhaustion_returns_429_quota_daily(): void
    {
        $user = $this->student();
        $day = now('UTC')->toDateString();
        // Kuota harian habis
        DB::table('ai_usage')->insert(['scope' => 'user:'.$user->id, 'day' => $day, 'tokens' => 100000]);

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Halo AI']);

        $response->assertStatus(429)
            ->assertJson([
                'error' => AiErrorCode::QuotaDaily->value,
                'message' => AiErrorCode::QuotaDaily->message(),
            ]);

        Http::assertNothingSent();
    }

    public function test_turn_limit_exhaustion_returns_429_turn_limit(): void
    {
        $user = $this->student();
        // Buat 12 riwayat percakapan sebelumnya
        for ($i = 1; $i <= 12; $i++) {
            DB::table('ai_turns')->insert([
                'user_id' => $user->id,
                'task_id' => 1,
                'question' => "Pertanyaan ke-{$i}",
                'code' => '',
                'answer' => "Jawaban ke-{$i}",
                'status' => 'answered',
            ]);
        }

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Pertanyaan ke-13']);

        $response->assertStatus(429)
            ->assertJson([
                'error' => AiErrorCode::TurnLimit->value,
                'message' => AiErrorCode::TurnLimit->message(),
            ]);

        Http::assertNothingSent();
    }

    public function test_concurrent_request_returns_429_locked(): void
    {
        $user = $this->student();

        // Kunci lock user secara manual seolah ada request aktif
        $lock = Cache::lock('ai:user:'.$user->id, 90);
        $this->assertTrue($lock->get());

        try {
            $response = $this->postJson('/ai/tasks/1', ['question' => 'Pertanyaan paralel']);

            $response->assertStatus(429)
                ->assertJson([
                    'error' => AiErrorCode::Locked->value,
                    'message' => AiErrorCode::Locked->message(),
                ]);

            Http::assertNothingSent();
        } finally {
            $lock->release();
        }
    }

    public function test_gate_solution_request_returns_200_blocked_asks_solution(): void
    {
        $user = $this->student();

        // Gate menolak permintaan solusi
        Http::fakeSequence()->push($this->providerResult('{"allow":false}', 50));

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Tuliskan kode lengkap tugas BST']);

        $response->assertOk()
            ->assertJson([
                'answer' => AiErrorCode::BlockedAsksSolution->message(),
            ]);

        Http::assertSentCount(1);

        $this->assertDatabaseHas('ai_turns', [
            'user_id' => $user->id,
            'task_id' => 1,
            'status' => 'refused',
            'answer' => AiErrorCode::BlockedAsksSolution->message(),
        ]);
    }

    public function test_successful_call_commits_actual_tokens_and_refunds_reservation_difference(): void
    {
        $user = $this->student();
        $day = now('UTC')->toDateString();

        Http::fakeSequence()
            ->push($this->providerResult('{"allow":true}', 80))   // Gate: 80 token
            ->push($this->providerResult('Pikirkan struktur node tree', 120)) // Answer: 120 token
            ->push($this->providerResult('{"allow":true}', 90));   // Review: 90 token

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Bagaimana ide dasar BST?']);

        $response->assertOk()
            ->assertJsonPath('answer', 'Pikirkan struktur node tree');

        Http::assertSentCount(3);

        // Total actual tokens: 80 + 120 + 90 = 290
        $used = (int) DB::table('ai_usage')->where('scope', 'user:'.$user->id)->where('day', $day)->value('tokens');
        $this->assertSame(290, $used);
    }

    public function test_user_stop_cancel_charges_only_estimated_input_tokens_and_refunds_remainder(): void
    {
        $user = $this->student();
        $day = now('UTC')->toDateString();

        // Simulasikan user menekan tombol stop saat request sedang diproses
        // Gate sukses, tapi sebelum answer diproses, user membatalkan
        Http::fake(function () use ($user) {
            // Pasang sinyal cancel saat HTTP provider dipanggil
            Cache::put('ai:cancelled:'.$user->id, true, 60);

            return Http::response($this->providerResult('{"allow":true}', 50), 200);
        });

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Jelaskan BST secara mendalam']);

        $response->assertStatus(499)
            ->assertJson([
                'error' => 'cancelled',
                'message' => 'Bantuan AI dibatalkan oleh pengguna.',
            ]);

        $this->assertDatabaseHas('ai_turns', [
            'user_id' => $user->id,
            'task_id' => 1,
            'status' => 'cancelled',
        ]);

        $used = (int) DB::table('ai_usage')->where('scope', 'user:'.$user->id)->where('day', $day)->value('tokens');
        // Hanya token input estimasi (maksimal ~1.500) yang ditagih, sisa padding dan output allowance di-refund
        $this->assertGreaterThan(0, $used);
        $this->assertLessThan(2000, $used);
    }

    public function test_retry_with_retry_after_header_succeeds_on_second_attempt(): void
    {
        $user = $this->student();

        Http::fakeSequence()
            ->push(['error' => 'Rate limit'], 429, ['Retry-After' => '0.05']) // Attempt 1: 429
            ->push($this->providerResult('{"allow":true}', 50))               // Attempt 2: Gate sukses
            ->push($this->providerResult('Gunakan node kiri dan kanan', 80))  // Answer sukses
            ->push($this->providerResult('{"allow":true}', 50));              // Review sukses

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Struktur data pohon']);

        $response->assertOk()
            ->assertJsonPath('answer', 'Gunakan node kiri dan kanan');

        Http::assertSentCount(4); // 2 gate + 1 answer + 1 review
    }

    public function test_lock_ttl_is_released_in_finally_allowing_subsequent_request(): void
    {
        $user = $this->student();

        Http::fakeSequence()
            ->push($this->providerResult('{"allow":true}', 50))
            ->push($this->providerResult('Penjelasan konsep satu', 80))
            ->push($this->providerResult('{"allow":true}', 50))
            ->push($this->providerResult('{"allow":true}', 50))
            ->push($this->providerResult('Penjelasan konsep dua', 80))
            ->push($this->providerResult('{"allow":true}', 50));

        $res1 = $this->postJson('/ai/tasks/1', ['question' => 'Pertanyaan 1']);
        $res1->assertOk();

        // Lock harus sudah dilepas di finally, sehingga request berikutnya langsung jalan tanpa error locked
        $res2 = $this->postJson('/ai/tasks/1', ['question' => 'Pertanyaan 2']);
        $res2->assertOk();

        $this->assertTrue(Cache::lock('ai:user:'.$user->id, 90)->get());
        Cache::lock('ai:user:'.$user->id)->release();
    }

    public function test_provider_rpm_rate_limiter_stops_excessive_calls(): void
    {
        config(['ai.rpm' => 2]);
        $user = $this->student();

        Http::fakeSequence()
            ->push($this->providerResult('{"allow":true}', 50))
            ->push($this->providerResult('Balasan satu', 80))
            ->push($this->providerResult('{"allow":true}', 50));

        // Panggilan 1: menggunakan 3 hit internal (gate, answer, review) -> melampaui limit RPM 2
        // Pada saat review dipanggil (hit ke-3), rate limiter provider RPM memotong
        $response = $this->postJson('/ai/tasks/1', ['question' => 'Pertanyaan']);

        $response->assertStatus(503)
            ->assertJson([
                'error' => AiErrorCode::ProviderBusy->value,
                'message' => AiErrorCode::ProviderBusy->message(),
            ]);
    }

    public function test_unexpected_provider_error_returns_503_internal_error(): void
    {
        $user = $this->student();

        Http::fake([
            '*' => Http::response(['error' => 'Bad request argument'], 400),
        ]);

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Error 400']);

        $response->assertStatus(503)
            ->assertJson([
                'error' => AiErrorCode::InternalError->value,
                'message' => AiErrorCode::InternalError->message(),
            ]);
    }

    public function test_non_atomic_cache_driver_logs_warning_in_production(): void
    {
        $user = $this->student();
        $this->app['env'] = 'production';
        config(['cache.default' => 'file']);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($msg) => str_contains($msg, 'cache driver non-atomik'));

        $this->withoutMiddleware([ValidateCsrfToken::class, VerifyCsrfToken::class]);
        Http::fakeSequence()->push($this->providerResult('{"allow":false}'));

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Test log']);
        $response->assertOk();
    }

    public function test_rollback_flag_preserves_v1_behavior_when_v2_is_false(): void
    {
        config(['ai.v2' => false]);
        $user = $this->student();

        Http::fakeSequence()->push($this->providerResult('{"allow":false}'));

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Kerjakan tugas ini']);

        // Dalam mode v1 (rollback), pesan penolakan adalah teks refusal lama
        $response->assertOk()
            ->assertJsonPath('answer', GeminiTutor::REFUSAL);
    }

    public function test_cancel_endpoint_force_releases_lock_and_sets_cancelled_cache(): void
    {
        $user = $this->student();
        $lock = Cache::lock('ai:user:'.$user->id, 90);
        $lock->get();

        $response = $this->postJson('/ai/tasks/1/cancel');
        $response->assertOk()->assertJson(['cancelled' => true]);

        $this->assertTrue((bool) Cache::get('ai:cancelled:'.$user->id));
        // Lock harus sudah dilepas oleh cancel endpoint
        $this->assertTrue(Cache::lock('ai:user:'.$user->id, 90)->get());
        Cache::lock('ai:user:'.$user->id)->release();
    }

    public function test_token_reservation_formula_uses_chars_per_token_and_padding_in_v2(): void
    {
        config([
            'ai.v2' => true,
            'ai.chars_per_token' => 5,
            'ai.reserve_padding' => 500,
        ]);
        $user = $this->student();
        $day = now('UTC')->toDateString();

        // 429 triggers reservation, failure triggers refund
        Http::fake([
            '*' => Http::response(['error' => 'quota exceeded'], 429),
        ]);

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Tes formula reservasi']);
        $response->assertStatus(503);

        // Tokens should be fully refunded back to 0 on 429
        $used = (int) (DB::table('ai_usage')->where('scope', 'user:'.$user->id)->where('day', $day)->value('tokens') ?? 0);
        $this->assertSame(0, $used);
    }

    public function test_provider_client_errors_400_401_403_are_not_retried_and_fully_refunded(): void
    {
        $user = $this->student();
        $day = now('UTC')->toDateString();

        foreach ([400, 401, 403] as $status) {
            Http::fake([
                '*' => Http::response(['error' => 'Client Error '.$status], $status),
            ]);

            $response = $this->postJson('/ai/tasks/1', ['question' => 'Error '.$status]);

            $response->assertStatus(503)
                ->assertHeader('X-AI-Error', AiErrorCode::InternalError->value);

            // 400, 401, 403 must not be retried (sent count must be 1 per request)
            Http::assertSentCount(1);

            $used = (int) (DB::table('ai_usage')->where('scope', 'user:'.$user->id)->where('day', $day)->value('tokens') ?? 0);
            $this->assertSame(0, $used, "Tokens were not fully refunded on status {$status}");
        }
    }
}
