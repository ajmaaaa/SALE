<?php

namespace Tests\Feature;

use App\Services\Ai\CodeRunner;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CodeRunnerTest extends TestCase
{
    public function test_unconfigured_runner_does_not_execute_or_send_code(): void
    {
        Http::preventStrayRequests();
        config(['ai.piston_url' => '']);
        $result = app(CodeRunner::class)->run(1, 'print("hello")');
        $this->assertNotNull($result['error']);
        Http::assertNothingSent();
    }

    public function test_configured_runner_preserves_both_output_streams(): void
    {
        config(['ai.piston_url' => 'http://runner.test/api/v2/piston']);
        Http::fake(['runner.test/*' => Http::response(['version' => '3.12', 'run' => [
            'stdout' => "hello\n", 'stderr' => "OK\n", 'code' => 0, 'signal' => null,
        ]])]);
        $result = app(CodeRunner::class)->run(1, 'print("hello")');
        $this->assertSame("hello\nOK\n", $result['output']);
        $this->assertNull($result['error']);
        Http::assertSent(fn ($request) => $request['run_timeout'] === 10000 && $request['run_memory_limit'] === 134217728);
    }

    public function test_provider_failures_are_reported(): void
    {
        config(['ai.piston_url' => 'http://runner.test']);
        Http::fake(['*' => Http::response('unauthorized', 401)]);
        $this->assertNotNull(app(CodeRunner::class)->run(1, 'pass')['error']);
        Http::fake(['*' => Http::response(['run' => null])]);
        $this->assertNotNull(app(CodeRunner::class)->run(1, 'pass')['error']);
    }

    public function test_anonymous_users_cannot_use_server_runner(): void
    {
        $this->postJson('/code/run/1', ['code' => 'pass'])->assertUnauthorized();
    }
}
