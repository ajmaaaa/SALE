<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Ai\AiErrorCode;
use App\Services\Ai\AiTutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiTutorV2Phase2AdapterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'ai.enabled' => true,
            'ai.v2' => true,
            'ai.key' => 'test-gemini-key',
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

    // ==========================================
    // 1. PENGUJIAN USAGE TOKEN AKTUAL PER PROVIDER
    // ==========================================

    public function test_google_provider_returns_actual_usage_tokens_including_cache_and_thoughts(): void
    {
        $user = $this->student();
        SystemSetting::updateOrCreate(['key' => 'ai_provider'], ['value' => 'Google AI']);
        SystemSetting::updateOrCreate(['key' => 'ai_model'], ['value' => 'gemini-3.6-flash']);
        SystemSetting::updateOrCreate(['key' => 'ai_api_key'], ['value' => 'test-gemini-key']);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => 'Halo dari Gemini!']]]]],
                'usageMetadata' => [
                    'promptTokenCount' => 120,
                    'cachedContentTokenCount' => 40,
                    'candidatesTokenCount' => 60,
                    'thoughtsTokenCount' => 15,
                    'totalTokenCount' => 195,
                ],
                'modelVersion' => 'gemini-3.6-flash-001',
            ], 200),
        ]);

        /** @var AiTutor $tutor */
        $tutor = app(AiTutor::class);
        $result = $tutor->sendProviderRequest($user->id, 'System instructions', ['question' => 'Halo'], 100, false, 'answer', []);

        $this->assertSame('Halo dari Gemini!', $result['text']);
        $this->assertSame(120, $result['usage']['input_tokens']);
        $this->assertSame(40, $result['usage']['cached_tokens']);
        $this->assertSame(60, $result['usage']['output_tokens']);
        $this->assertSame(15, $result['usage']['thinking_tokens']);
        $this->assertSame(195, $result['usage']['total_tokens']);
        $this->assertSame('google', $result['provider']);
        $this->assertSame('gemini-3.6-flash', $result['model']);
        $this->assertSame('STOP', $result['finish_reason']);
    }

    public function test_openai_provider_returns_actual_usage_tokens_including_cached_and_reasoning(): void
    {
        $user = $this->student();
        SystemSetting::updateOrCreate(['key' => 'ai_provider'], ['value' => 'OpenAI']);
        SystemSetting::updateOrCreate(['key' => 'ai_model'], ['value' => 'gpt-4o-mini']);
        SystemSetting::updateOrCreate(['key' => 'ai_api_key'], ['value' => 'test-openai-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['finish_reason' => 'stop', 'message' => ['content' => 'Halo dari OpenAI!']]],
                'usage' => [
                    'prompt_tokens' => 150,
                    'completion_tokens' => 70,
                    'total_tokens' => 220,
                    'prompt_tokens_details' => ['cached_tokens' => 50],
                    'completion_tokens_details' => ['reasoning_tokens' => 20],
                ],
                'model' => 'gpt-4o-mini-2024-07-18',
            ], 200),
        ]);

        /** @var AiTutor $tutor */
        $tutor = app(AiTutor::class);
        $result = $tutor->sendProviderRequest($user->id, 'System instructions', ['question' => 'Halo'], 100, false, 'answer', []);

        $this->assertSame('Halo dari OpenAI!', $result['text']);
        $this->assertSame(150, $result['usage']['input_tokens']);
        $this->assertSame(50, $result['usage']['cached_tokens']);
        $this->assertSame(70, $result['usage']['output_tokens']);
        $this->assertSame(20, $result['usage']['thinking_tokens']);
        $this->assertSame(220, $result['usage']['total_tokens']);
        $this->assertSame('openai', $result['provider']);
        $this->assertSame('gpt-4o-mini', $result['model']);
    }

    public function test_deepseek_provider_returns_actual_usage_tokens_including_cache_hit_and_reasoning(): void
    {
        $user = $this->student();
        SystemSetting::updateOrCreate(['key' => 'ai_provider'], ['value' => 'DeepSeek']);
        SystemSetting::updateOrCreate(['key' => 'ai_model'], ['value' => 'deepseek-chat']);
        SystemSetting::updateOrCreate(['key' => 'ai_api_key'], ['value' => 'test-deepseek-key']);

        Http::fake([
            'https://api.deepseek.com/chat/completions' => Http::response([
                'choices' => [['finish_reason' => 'stop', 'message' => ['content' => 'Halo dari DeepSeek!']]],
                'usage' => [
                    'prompt_tokens' => 180,
                    'completion_tokens' => 85,
                    'total_tokens' => 265,
                    'prompt_cache_hit_tokens' => 64,
                    'completion_tokens_details' => ['reasoning_tokens' => 30],
                ],
                'model' => 'deepseek-chat',
            ], 200),
        ]);

        /** @var AiTutor $tutor */
        $tutor = app(AiTutor::class);
        $result = $tutor->sendProviderRequest($user->id, 'System instructions', ['question' => 'Halo'], 100, false, 'answer', []);

        $this->assertSame('Halo dari DeepSeek!', $result['text']);
        $this->assertSame(180, $result['usage']['input_tokens']);
        $this->assertSame(64, $result['usage']['cached_tokens']);
        $this->assertSame(85, $result['usage']['output_tokens']);
        $this->assertSame(30, $result['usage']['thinking_tokens']);
        $this->assertSame(265, $result['usage']['total_tokens']);
        $this->assertSame('deepseek', $result['provider']);
    }

    // ==========================================
    // 2. PENGUJIAN MODE JSON / STRUCTURED OUTPUT
    // ==========================================

    public function test_google_provider_supports_json_mode_and_response_schema(): void
    {
        $user = $this->student();
        SystemSetting::updateOrCreate(['key' => 'ai_provider'], ['value' => 'Google AI']);
        SystemSetting::updateOrCreate(['key' => 'ai_api_key'], ['value' => 'test-gemini-key']);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function ($request) {
                $data = $request->data();
                $this->assertSame('application/json', $data['generationConfig']['responseMimeType'] ?? null);
                $this->assertIsArray($data['generationConfig']['responseSchema'] ?? null);

                return Http::response([
                    'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"verdict":"ok","hint_level":1,"reply":"Bagus!"}']]]]],
                    'usageMetadata' => ['totalTokenCount' => 80],
                ], 200);
            },
        ]);

        /** @var AiTutor $tutor */
        $tutor = app(AiTutor::class);
        $customSchema = [
            'type' => 'OBJECT',
            'properties' => [
                'verdict' => ['type' => 'STRING'],
                'hint_level' => ['type' => 'INTEGER'],
                'reply' => ['type' => 'STRING'],
            ],
            'required' => ['verdict', 'reply'],
        ];

        $result = $tutor->sendProviderRequest($user->id, 'Instruction', ['data' => 'test'], 200, $customSchema, 'custom_stage', []);
        $this->assertSame('{"verdict":"ok","hint_level":1,"reply":"Bagus!"}', $result['text']);
    }

    public function test_openai_and_deepseek_support_json_mode_response_format(): void
    {
        $user = $this->student();
        SystemSetting::updateOrCreate(['key' => 'ai_provider'], ['value' => 'OpenAI']);
        SystemSetting::updateOrCreate(['key' => 'ai_api_key'], ['value' => 'test-openai-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => function ($request) {
                $data = $request->data();
                $this->assertSame(['type' => 'json_object'], $data['response_format'] ?? null);
                $this->assertStringContainsString('JSON', $data['messages'][0]['content']);

                return Http::response([
                    'choices' => [['finish_reason' => 'stop', 'message' => ['content' => '{"allow":true}']]],
                    'usage' => ['total_tokens' => 50],
                ], 200);
            },
        ]);

        /** @var AiTutor $tutor */
        $tutor = app(AiTutor::class);
        $result = $tutor->sendProviderRequest($user->id, 'Classification', ['q' => 'test'], 100, true, 'gate', []);
        $this->assertSame('{"allow":true}', $result['text']);
    }

    // ==========================================
    // 3. PEMBEDAAN ERROR: 429, 5XX, TIMEOUT, SAFETY FILTER
    // ==========================================

    public function test_error_distinction_429_returns_rate_limit_reason(): void
    {
        $user = $this->student();
        SystemSetting::updateOrCreate(['key' => 'ai_provider'], ['value' => 'OpenAI']);
        SystemSetting::updateOrCreate(['key' => 'ai_api_key'], ['value' => 'test-openai-key']);

        Http::fake([
            'https://api.openai.com/*' => Http::response(['error' => ['message' => 'Rate limit']], 429, ['Retry-After' => '0.05']),
        ]);

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Pertanyaan']);
        $response->assertStatus(503)
            ->assertHeader('X-AI-Error', AiErrorCode::ProviderBusy->value)
            ->assertHeader('X-AI-Reason', 'rate_limit')
            ->assertJson([
                'error' => AiErrorCode::ProviderBusy->value,
                'message' => AiErrorCode::ProviderBusy->message(),
            ]);

        $this->assertDatabaseHas('ai_api_calls', [
            'status' => 'rate_limited',
            'provider' => 'openai',
        ]);
    }

    public function test_error_distinction_5xx_returns_server_error_reason(): void
    {
        $user = $this->student();
        SystemSetting::updateOrCreate(['key' => 'ai_provider'], ['value' => 'DeepSeek']);
        SystemSetting::updateOrCreate(['key' => 'ai_api_key'], ['value' => 'test-deepseek-key']);

        Http::fake([
            'https://api.deepseek.com/*' => Http::response(['error' => 'Server error'], 502),
        ]);

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Pertanyaan']);
        $response->assertStatus(503)
            ->assertHeader('X-AI-Error', AiErrorCode::ProviderBusy->value)
            ->assertHeader('X-AI-Reason', 'server_error')
            ->assertJson([
                'error' => AiErrorCode::ProviderBusy->value,
                'message' => AiErrorCode::ProviderBusy->message(),
            ]);

        $this->assertDatabaseHas('ai_api_calls', [
            'status' => 'provider_error',
            'provider' => 'deepseek',
        ]);
    }

    public function test_error_distinction_timeout_returns_timeout_reason(): void
    {
        $this->student();

        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Pertanyaan']);
        $response->assertStatus(503)
            ->assertHeader('X-AI-Error', AiErrorCode::ProviderBusy->value)
            ->assertHeader('X-AI-Reason', 'timeout');

        $this->assertDatabaseHas('ai_api_calls', [
            'status' => 'timeout',
        ]);
    }

    public function test_error_distinction_safety_filter_returns_safety_reason_and_refunds_tokens(): void
    {
        $user = $this->student();
        $day = now('UTC')->toDateString();

        // Gemini mengembalikan finishReason SAFETY dengan balasan kosong
        Http::fake([
            '*' => Http::response([
                'candidates' => [['finishReason' => 'SAFETY', 'content' => ['parts' => []]]],
                'usageMetadata' => ['totalTokenCount' => 45],
            ], 200),
        ]);

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Pertanyaan tidak aman']);
        $response->assertStatus(503)
            ->assertHeader('X-AI-Error', AiErrorCode::InternalError->value)
            ->assertHeader('X-AI-Reason', 'safety_filter')
            ->assertJson([
                'error' => AiErrorCode::InternalError->value,
                'message' => 'AI tidak menghasilkan jawaban lengkap yang aman ditampilkan.',
            ]);

        $this->assertDatabaseHas('ai_api_calls', [
            'status' => 'blocked_safety',
            'finish_reason' => 'SAFETY',
            'usage_source' => 'refunded',
            'total_tokens' => 0,
        ]);

        // Token yang direservasi harus di-refund penuh
        $used = (int) DB::table('ai_usage')->where('scope', 'user:'.$user->id)->where('day', $day)->value('tokens');
        $this->assertSame(0, $used);
    }

    public function test_error_distinction_empty_response_returns_empty_response_reason(): void
    {
        $user = $this->student();
        $day = now('UTC')->toDateString();

        // OpenAI mengembalikan 200 tetapi konten teks kosong
        SystemSetting::updateOrCreate(['key' => 'ai_provider'], ['value' => 'OpenAI']);
        SystemSetting::updateOrCreate(['key' => 'ai_api_key'], ['value' => 'test-openai-key']);

        Http::fake([
            '*' => Http::response([
                'choices' => [['finish_reason' => 'stop', 'message' => ['content' => '   ']]],
                'usage' => ['total_tokens' => 20],
            ], 200),
        ]);

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Pertanyaan']);
        $response->assertStatus(503)
            ->assertHeader('X-AI-Error', AiErrorCode::InternalError->value)
            ->assertHeader('X-AI-Reason', 'empty_response')
            ->assertJson([
                'error' => AiErrorCode::InternalError->value,
                'message' => 'AI tidak memberikan jawaban.',
            ]);

        $this->assertDatabaseHas('ai_api_calls', [
            'status' => 'empty_response',
            'usage_source' => 'refunded',
            'total_tokens' => 0,
        ]);

        $used = (int) DB::table('ai_usage')->where('scope', 'user:'.$user->id)->where('day', $day)->value('tokens');
        $this->assertSame(0, $used);
    }

    public function test_openai_content_filter_finish_reason_is_recognized_as_safety_blocked(): void
    {
        $this->student();
        SystemSetting::updateOrCreate(['key' => 'ai_provider'], ['value' => 'OpenAI']);
        SystemSetting::updateOrCreate(['key' => 'ai_api_key'], ['value' => 'test-openai-key']);

        Http::fake([
            '*' => Http::response([
                'choices' => [['finish_reason' => 'content_filter', 'message' => ['content' => '']]],
                'usage' => ['total_tokens' => 30],
            ], 200),
        ]);

        $response = $this->postJson('/ai/tasks/1', ['question' => 'Pertanyaan']);
        $response->assertStatus(503)
            ->assertHeader('X-AI-Error', AiErrorCode::InternalError->value)
            ->assertHeader('X-AI-Reason', 'safety_filter')
            ->assertJson([
                'error' => AiErrorCode::InternalError->value,
                'message' => 'AI tidak menghasilkan jawaban lengkap yang aman ditampilkan.',
            ]);

        $this->assertDatabaseHas('ai_api_calls', [
            'status' => 'blocked_safety',
            'finish_reason' => 'CONTENT_FILTER',
        ]);
    }
}
