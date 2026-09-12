<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('title');
            $table->text('body');
            $table->boolean('enabled')->default(true);
        });
        Schema::create('ai_access', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('task_id');
            $table->foreign('task_id')->references('id')->on('ai_tasks')->cascadeOnDelete();
            $table->primary(['user_id', 'task_id']);
        });
        Schema::create('ai_usage', function (Blueprint $table) {
            $table->string('scope');
            $table->date('day');
            $table->unsignedBigInteger('tokens')->default(0);
            $table->primary(['scope', 'day']);
        });
        Schema::create('ai_turns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('task_id');
            $table->text('question');
            $table->text('code');
            $table->text('answer')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'task_id']);
        });
    }

    public function down(): void
    {
        foreach (['ai_turns', 'ai_usage', 'ai_access', 'ai_tasks'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
