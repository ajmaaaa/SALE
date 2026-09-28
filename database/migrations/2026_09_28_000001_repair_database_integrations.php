<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mata_kuliahs', function (Blueprint $table) {
            if (! Schema::hasColumn('mata_kuliahs', 'is_lintas_prodi')) {
                $table->boolean('is_lintas_prodi')->default(false)->after('sks')->index();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'managing_prodi_id')) {
                $table->foreignId('managing_prodi_id')
                    ->nullable()
                    ->after('prodi_id')
                    ->constrained('prodis')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('must_change_password')->index();
            }
        });

        if (! Schema::hasTable('user_notification_states')) {
            Schema::create('user_notification_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('notification_key', 191);
                $table->timestamp('read_at')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'notification_key']);
                $table->index(['user_id', 'read_at', 'deleted_at']);
            });
        }

        if (! Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->string('key', 100)->primary();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 500);
                $table->json('context')->nullable();
                $table->timestamps();

                $table->index(['actor_id', 'created_at']);
            });
        }

        // Existing lecturer accounts without a prodi are assigned to the one
        // prodi they already teach for. Ambiguous lecturers remain unassigned
        // and must be resolved explicitly by an administrator.
        $lecturers = DB::table('users')->whereNull('prodi_id')->get(['id']);
        foreach ($lecturers as $lecturer) {
            $prodiIds = DB::table('class_sections')
                ->join('mata_kuliahs', 'mata_kuliahs.id', '=', 'class_sections.mata_kuliah_id')
                ->where(function ($query) use ($lecturer) {
                    $query->where('class_sections.dosen_id', $lecturer->id)
                        ->orWhere('class_sections.dosen_pendamping_id', $lecturer->id);
                })
                ->distinct()
                ->pluck('mata_kuliahs.prodi_id');

            if ($prodiIds->count() === 1) {
                DB::table('users')->where('id', $lecturer->id)->update([
                    'managing_prodi_id' => $prodiIds->first(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('user_notification_states');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
