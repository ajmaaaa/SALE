<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rooms Table (Kelas / Ruang Diskusi)
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('course_id')->nullable()->index();
            $table->foreignId('class_section_id')->nullable()->constrained('class_sections')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Room Members Table (Anggota Room & Role)
        Schema::create('room_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20)->default('mahasiswa'); // 'dosen', 'mahasiswa'
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['room_id', 'user_id']);
            $table->index(['room_id', 'role']);
        });

        // 3. Messages Table
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->text('attachment_url')->nullable();
            $table->foreignId('reply_to_message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Sesuai desain-sistem-chat-diskusi.md: Index gabungan untuk query pesan terbaru room
            $table->index(['room_id', 'created_at']);
            $table->index('reply_to_message_id');
            $table->index(['room_id', 'is_pinned']);
        });

        // 4. Message Mentions Table (@mention)
        Schema::create('message_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignId('mentioned_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('mentioned_user_id');
            $table->unique(['message_id', 'mentioned_user_id']);
        });

        // 5. Chat Notifications Table
        Schema::create('chat_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 50)->default('mention'); // 'mention', 'reply'
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_notifications');
        Schema::dropIfExists('message_mentions');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('room_members');
        Schema::dropIfExists('rooms');
    }
};
