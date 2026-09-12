<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:grant {email} {assignment=1} {--task-file= : JSON file containing title and body for a new task}', function () {
    $email = strtolower(trim($this->argument('email')));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Email tidak valid.');

        return 1;
    }
    $id = (int) $this->argument('assignment');
    if ($id < 1) {
        $this->error('ID tugas harus positif.');

        return 1;
    }
    $task = DB::table('ai_tasks')->where('id', $id)->first();
    if (! $task) {
        $file = $this->option('task-file');
        $data = $file ? json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR) : ($id === 1 ? [
            'title' => 'Praktikum Binary Tree',
            'body' => 'Lengkapi metode insert() pada Binary Search Tree. Jelaskan penanganan cabang kiri, kanan, dan nilai duplikat.',
        ] : []);
        Validator::make($data, ['title' => 'required|string|max:255', 'body' => 'required|string|max:8000'])->validate();
        DB::table('ai_tasks')->insert(['id' => $id, 'title' => $data['title'], 'body' => $data['body']]);
    }
    $user = User::where('email', $email)->first();
    if (! $user) {
        $password = $this->secret('Password akun AI (minimal 12 karakter)');
        if (mb_strlen($password ?? '') < 12) {
            $this->error('Password minimal 12 karakter.');

            return 1;
        }
        $user = User::create(['name' => $email, 'email' => $email, 'password' => $password]);
    }
    DB::table('ai_access')->insertOrIgnore(['user_id' => $user->id, 'task_id' => $id]);
    $this->info('Akses AI diberikan. Masuk melalui panel Lumina AI di room tugas.');
})->purpose('Create a password-protected AI account and grant access to an authoritative task');

Artisan::command('ai:revoke {email} {assignment}', function () {
    $user = User::where('email', strtolower(trim($this->argument('email'))))->first();
    if ($user) {
        DB::table('ai_access')->where('user_id', $user->id)->where('task_id', (int) $this->argument('assignment'))->delete();
    }
    $this->info('Akses tugas dicabut. Riwayat dan kuota tetap disimpan.');
})->purpose('Revoke access without resetting assistance history or usage');

Artisan::command('ai:reset {email?} {--all : Reset seluruh kuota dan riwayat AI}', function () {
    if ($this->option('all')) {
        DB::table('ai_turns')->truncate();
        DB::table('ai_usage')->truncate();
        Cache::flush();
        $this->info('Seluruh riwayat chat dan kuota AI berhasil di-reset.');

        return 0;
    }

    $email = $this->argument('email') ? strtolower(trim($this->argument('email'))) : 'coba.ai.9c0f0c@sale.test';
    $user = User::where('email', $email)->first();
    if (! $user) {
        $this->error("User dengan email {$email} tidak ditemukan.");

        return 1;
    }

    DB::table('ai_turns')->where('user_id', $user->id)->delete();
    DB::table('ai_usage')->where('scope', 'user:'.$user->id)->update(['tokens' => 0]);
    DB::table('ai_usage')->where('scope', 'global')->update(['tokens' => 0]);
    Cache::forget('ai:user:'.$user->id);
    Cache::forget('ai:budget');

    $this->info("Kuota token dan riwayat percakapan untuk {$email} berhasil di-reset.");

    return 0;
})->purpose('Reset AI token usage and conversation turns for testing');
