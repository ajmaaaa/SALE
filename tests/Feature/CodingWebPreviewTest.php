<?php

namespace Tests\Feature;

use Tests\TestCase;

class CodingWebPreviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->disableRoleGateForPreviewBehavior();
    }

    public function test_web_coding_assignment_renders_preview_tab_and_iframe(): void
    {
        session(['auth_user' => ['id' => 2, 'name' => 'Dr. Budi Santoso', 'role' => 'dosen']]);
        $this->post('/dosen/course/2/items', [
            'type' => 'coding', 'title' => 'Halaman profil HTML', 'module' => 'Minggu 1',
            'body' => 'Buat halaman profil dengan HTML, CSS, dan JavaScript.', 'question_type' => 'coding',
            'code_language' => 'web', 'cpmk' => 'Merancang antarmuka.', 'formats' => ['text'],
        ])->assertSessionHasNoErrors()->assertRedirect('/dosen/course/2');

        $itemId = max(array_keys(session('learning.items')));
        $response = $this->get("/mahasiswa/assignment/{$itemId}/code");
        $response->assertOk()
            ->assertSee('data-code-language="web"', false)
            ->assertSee('data-code-files-json', false)
            ->assertSee('data-file-tabs', false)
            ->assertSee('data-max-files="5"', false)
            ->assertSee('data-max-file-chars="8000"', false)
            ->assertSee('data-max-total-chars="20000"', false)
            ->assertSee('index.html')
            ->assertSee('data-output-tab="preview"', false)
            ->assertSee('data-preview-frame', false)
            ->assertSee('sandbox="allow-scripts allow-forms"', false)
            ->assertSee('\u003C!DOCTYPE html', false);
    }

    public function test_python_coding_assignment_keeps_console_only_layout(): void
    {
        $response = $this->get('/mahasiswa/assignment/1/code');
        $response->assertOk()
            ->assertSee('data-code-language="python"', false)
            ->assertSee('Output Python / Terminal')
            ->assertSee('data-output-tab="console"', false);
    }

    public function test_default_language_is_python_for_new_coding_tasks(): void
    {
        $this->post('/dosen/course/1/items', [
            'type' => 'coding', 'title' => 'Fungsi Python', 'module' => 'Minggu 2',
            'body' => 'Tulis fungsi.', 'question_type' => 'coding', 'cpmk' => 'Membuat fungsi.', 'formats' => ['text'],
        ])->assertSessionHasNoErrors();

        $itemId = max(array_keys(session('learning.items')));
        $item = session('learning.items')[$itemId];
        $this->assertSame('python', $item['language']);
        $this->get("/mahasiswa/assignment/{$itemId}/code")->assertSee('data-code-language="python"', false);
    }

    public function test_language_follows_question_type_so_uraian_items_never_get_web_mode(): void
    {
        session(['auth_user' => ['id' => 2, 'name' => 'Dr. Budi Santoso', 'role' => 'dosen']]);
        $this->post('/dosen/course/2/items', [
            'type' => 'coding', 'title' => 'Essay kode', 'module' => 'Minggu 1',
            'body' => 'Jelaskan kode.', 'question_type' => 'uraian',
            'code_language' => 'web', 'cpmk' => 'Menjelaskan.', 'formats' => ['text'],
        ])->assertSessionHasNoErrors();

        $itemId = max(array_keys(session('learning.items')));
        $item = session('learning.items')[$itemId];
        $this->assertSame('python', $item['language']);
        $this->get("/mahasiswa/assignment/{$itemId}/code")
            ->assertSee('data-code-language="python"', false)
            ->assertSee('data-file-tabs', false);
    }
}
