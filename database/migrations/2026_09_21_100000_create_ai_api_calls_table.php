<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_api_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turn_id')->nullable()->constrained('ai_turns')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->string('feature', 50)->default('tutor');
            $table->string('stage', 50);
            $table->string('provider', 30)->default('gemini');
            $table->string('model', 100);
            $table->string('model_version', 100)->nullable();
            $table->string('status', 30);
            $table->string('usage_source', 20)->default('confirmed');
            $table->unsignedBigInteger('input_tokens')->default(0);
            $table->unsignedBigInteger('cached_tokens')->default(0);
            $table->unsignedBigInteger('output_tokens')->default(0);
            $table->unsignedBigInteger('thinking_tokens')->default(0);
            $table->unsignedBigInteger('total_tokens')->default(0);
            $table->decimal('estimated_cost_usd', 16, 10)->nullable();
            $table->unsignedInteger('latency_ms')->default(0);
            $table->string('finish_reason', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at', 'feature']);
            $table->index(['turn_id', 'stage']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_api_calls');
    }
};
