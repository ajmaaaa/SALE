<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mata_kuliahs')) {
            Schema::table('mata_kuliahs', function (Blueprint $table) {
                $table->dropUnique(['code']);
                $table->unique(['prodi_id', 'code'], 'mata_kuliahs_prodi_code_unique');
            });
        }

        if (Schema::hasTable('submissions')) {
            $duplicates = DB::table('submissions')
                ->select('assessment_id', 'user_id', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('assessment_id', 'user_id')
                ->having('aggregate', '>', 1)
                ->exists();

            if ($duplicates) {
                throw new RuntimeException('Duplicate submissions exist. Resolve duplicate assessment_id/user_id rows before applying the unique constraint.');
            }

            DB::table('submissions')->whereNull('mahasiswa_id')->update([
                'mahasiswa_id' => DB::raw('user_id'),
            ]);

            Schema::table('submissions', function (Blueprint $table) {
                $table->unique(['assessment_id', 'user_id'], 'submissions_assessment_user_unique');
            });
        }

        if (Schema::hasTable('system_settings')) {
            $setting = DB::table('system_settings')->where('key', 'ai_api_key')->first();
            $value = $setting?->value;
            if (filled($value) && ! Str::startsWith($value, 'encrypted:v1:')) {
                DB::table('system_settings')->where('key', 'ai_api_key')->update([
                    'value' => 'encrypted:v1:'.Crypt::encryptString($value),
                ]);
            }
        }

        if (! app()->environment(['local', 'testing']) && Schema::hasTable('users')) {
            $demoUserIds = DB::table('users')->where('email', 'demo.ai@sale.test')->pluck('id');
            if ($demoUserIds->isNotEmpty()) {
                DB::table('users')->whereIn('id', $demoUserIds)->update([
                    'is_active' => false,
                    'remember_token' => null,
                ]);
                if (Schema::hasTable('ai_access')) {
                    DB::table('ai_access')->whereIn('user_id', $demoUserIds)->delete();
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('submissions')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->dropUnique('submissions_assessment_user_unique');
            });
        }

        if (Schema::hasTable('mata_kuliahs')) {
            Schema::table('mata_kuliahs', function (Blueprint $table) {
                $table->dropUnique('mata_kuliahs_prodi_code_unique');
                $table->unique('code');
            });
        }

        if (Schema::hasTable('system_settings')) {
            $setting = DB::table('system_settings')->where('key', 'ai_api_key')->first();
            $value = $setting?->value;
            if (filled($value) && Str::startsWith($value, 'encrypted:v1:')) {
                DB::table('system_settings')->where('key', 'ai_api_key')->update([
                    'value' => Crypt::decryptString(Str::after($value, 'encrypted:v1:')),
                ]);
            }
        }
    }
};
