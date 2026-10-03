<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD-CLASS-LIFECYCLE-MANAGEMENT (Bab 3):
 * - Status peserta pada pivot class_section_student (enrolled / dropped_self / kicked)
 *   beserta jejak pengeluaran, penghitung kick, dan kunci verifikasi Admin Prodi.
 * - Status arsip pada class_sections.
 * - Tabel permohonan Verifikasi Peserta (class_enrollment_appeals).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_section_student', function (Blueprint $table) {
            $table->enum('status', ['enrolled', 'dropped_self', 'kicked'])->default('enrolled')->after('mahasiswa_id');
            $table->unsignedTinyInteger('kick_count')->default(0)->after('status');
            $table->timestamp('kicked_at')->nullable()->after('kick_count');
            $table->foreignId('kicked_by')->nullable()->after('kicked_at')->constrained('users')->nullOnDelete();
            $table->string('kick_reason', 255)->nullable()->after('kicked_by');
            $table->timestamp('dropped_at')->nullable()->after('kick_reason');
            $table->boolean('is_locked')->default(false)->after('dropped_at');

            $table->index(['class_section_id', 'status'], 'css_section_status_index');
        });

        Schema::table('class_sections', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('learning_payload');
            $table->foreignId('archived_by')->nullable()->after('archived_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('class_enrollment_appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_section_id')->constrained('class_sections')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('student_notes');
            $table->string('attachment_path', 255)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_cea_status');
            $table->index(['class_section_id', 'mahasiswa_id'], 'idx_cea_class_mahasiswa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_enrollment_appeals');

        Schema::table('class_sections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('archived_by');
            $table->dropColumn('archived_at');
        });

        Schema::table('class_section_student', function (Blueprint $table) {
            $table->dropIndex('css_section_status_index');
            $table->dropConstrainedForeignId('kicked_by');
            $table->dropColumn(['status', 'kick_count', 'kicked_at', 'kick_reason', 'dropped_at', 'is_locked']);
        });
    }
};
