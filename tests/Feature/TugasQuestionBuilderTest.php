<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TugasQuestionBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private ClassSection $section;
    private MataKuliah $mataKuliah;
    private Cpmk $cpmk1;
    private Cpmk $cpmk2;

    protected function setUp(): void
    {
        parent::setUp();

        $dosenRole = Role::firstOrCreate(
            ['name' => Role::DOSEN],
            ['label' => 'Dosen']
        );

        $this->dosen = User::factory()->create([
            'role_id' => $dosenRole->id,
            'email' => 'dosen.tugas@example.test',
        ]);

        $prodi = \App\Models\Prodi::create([
            'code' => 'TI',
            'name' => 'Teknik Informatika',
        ]);

        $this->mataKuliah = MataKuliah::create([
            'prodi_id' => $prodi->id,
            'code' => 'MK-TGS101',
            'name' => 'Struktur Data dan Algoritma',
            'sks' => 3,
            'semester_paket' => 3,
        ]);

        $this->cpmk1 = Cpmk::create([
            'prodi_id' => $prodi->id,
            'mata_kuliah_id' => $this->mataKuliah->id,
            'code' => 'CPMK-1',
            'description' => 'Mampu menganalisis algoritma',
        ]);

        $this->cpmk2 = Cpmk::create([
            'prodi_id' => $prodi->id,
            'mata_kuliah_id' => $this->mataKuliah->id,
            'code' => 'CPMK-2',
            'description' => 'Mampu menerapkan struktur data',
        ]);

        $this->mataKuliah->cpmks()->attach([$this->cpmk1->id, $this->cpmk2->id]);

        $semester = \App\Models\Semester::create([
            'code' => '20261',
            'name' => 'Ganjil 2026/2027',
            'academic_year' => '2026/2027',
            'term' => 1,
            'is_active' => true,
        ]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $this->mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'TGSTEST1',
        ]);
    }

    public function test_item_form_has_manual_cpmk_settings_for_regular_task_and_stepper_for_coding(): void
    {
        $response = $this->actingAs($this->dosen)->get(route('dosen.item.create', [
            'course' => $this->section->id,
            'type' => 'tugas',
        ]));

        $response->assertOk();

        // 1. Pengaturan bobot CPMK manual untuk tugas biasa (file/link/text)
        $response->assertSee('data-manual-cpmk-settings', false);
        $response->assertSee('CPMK dan persentase tugas');
        $response->assertSee('data-manual-cpmk-weight', false);

        // 2. Tahapan pemrograman memiliki layout identik kuis (toolbar, target count, badge skor, dan tab navigasi)
        $response->assertSee('data-coding-step-builder', false);
        $response->assertSee('data-coding-target-count', false);
        $response->assertSee('data-coding-total-points-badge', false);
        $response->assertSee('data-coding-step-tabs', false);
        $response->assertSee('Susun Soal (Pemrograman)');
        $response->assertSee('data-cpmk-select-picker', false);

        // 3. Tombol navigasi stepper
        $response->assertSee('data-content-progress', false);
        $response->assertSee('data-next-to-questions', false);
        $response->assertSee('data-back-to-setup', false);
    }

    public function test_lecturer_can_store_regular_task_with_manual_cpmk_weights(): void
    {
        $payload = [
            'type' => 'tugas',
            'task_mode' => 'regular',
            'title' => 'Tugas Makalah Analisis Algoritma',
            'module' => 'Minggu 3: Teori Kompleksitas',
            'body' => 'Kumpulkan laporan analisis dalam bentuk PDF atau dokumen.',
            'question_type' => 'uraian',
            'cpmk' => $this->cpmk1->code,
            'formats' => ['file', 'text'],
            'manual_cpmk_weights' => [
                'CPMK-1' => 60,
                'CPMK-2' => 40,
            ],
        ];

        $response = $this->actingAs($this->dosen)->post(
            route('dosen.item.store', $this->section->id),
            $payload
        );

        $response->assertRedirect(route('dosen.course.show', $this->section->id))
            ->assertSessionHasNoErrors();

        $assessment = Assessment::where('name', 'Tugas Makalah Analisis Algoritma')->firstOrFail();
        $this->assertSame('tugas', $assessment->type);

        $payloadData = $assessment->learning_payload;
        $this->assertSame('manual_cpmk', $payloadData['scoring_mode']);

        $cpmkPivot = $assessment->cpmks()->get()->keyBy('code');
        $this->assertEqualsWithDelta(60.0, (float) $cpmkPivot['CPMK-1']->pivot->weight, 0.01);
        $this->assertEqualsWithDelta(40.0, (float) $cpmkPivot['CPMK-2']->pivot->weight, 0.01);
    }

    public function test_lecturer_can_store_coding_task_with_steps_and_proportional_cpmk(): void
    {
        // 4 tahapan coding: 2 tahap CPMK-1, 2 tahap CPMK-2 (masing-masing 25 poin)
        $payload = [
            'type' => 'tugas',
            'task_mode' => 'coding',
            'title' => 'Tugas Praktik Sorting Algorithm',
            'module' => 'Minggu 4: Praktik Sorting',
            'body' => 'Selesaikan seluruh tahapan pemrograman berikut.',
            'question_type' => 'coding',
            'cpmk' => $this->cpmk1->code,
            'formats' => ['text'],
            'coding_steps' => [
                [
                    'title' => 'Tahap 1: Setup Lingkungan dan Dataset',
                    'body' => 'Persiapkan array acak sepanjang 1000 elemen.',
                    'cpmk' => $this->cpmk1->code,
                    'points' => 25,
                ],
                [
                    'title' => 'Tahap 2: Implementasi Quicksort',
                    'body' => 'Tuliskan fungsi partisi dan rekursi sorting.',
                    'cpmk' => $this->cpmk1->code,
                    'points' => 25,
                ],
                [
                    'title' => 'Tahap 3: Implementasi Mergesort',
                    'body' => 'Tuliskan fungsi merge dan split array.',
                    'cpmk' => $this->cpmk2->code,
                    'points' => 25,
                ],
                [
                    'title' => 'Tahap 4: Benchmarking Waktu Eksekusi',
                    'body' => 'Bandingkan waktu eksekusi kedua algoritma.',
                    'cpmk' => $this->cpmk2->code,
                    'points' => 25,
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)->post(
            route('dosen.item.store', $this->section->id),
            $payload
        );

        $response->assertRedirect(route('dosen.course.show', $this->section->id))
            ->assertSessionHasNoErrors();

        $assessment = Assessment::where('name', 'Tugas Praktik Sorting Algorithm')->firstOrFail();
        $this->assertSame('tugas', $assessment->type);

        $payloadData = $assessment->learning_payload;
        $this->assertCount(4, $payloadData['coding_steps']);
        $this->assertSame('automatic_cpmk', $payloadData['scoring_mode']);
        $this->assertSame(100, $payloadData['points']);

        // Bobot CPMK otomatis proporsional (2 dari 4 tahap = 50% masing-masing)
        $cpmkPivot = $assessment->cpmks()->get();
        $this->assertCount(2, $cpmkPivot);
        foreach ($cpmkPivot as $cpmk) {
            $this->assertEqualsWithDelta(50.0, (float) $cpmk->pivot->weight, 0.01);
        }
    }

    public function test_lecturer_can_store_coding_task_with_up_to_100_steps(): void
    {
        $steps = [];
        for ($i = 1; $i <= 100; $i++) {
            $steps[] = [
                'title' => "Tahap Pemrograman {$i}",
                'body' => "Instruksi pengerjaan tahap {$i}.",
                'cpmk' => ($i % 2 === 0) ? $this->cpmk2->code : $this->cpmk1->code,
                'points' => 1,
            ];
        }

        $payload = [
            'type' => 'tugas',
            'task_mode' => 'coding',
            'title' => 'Praktikum 100 Tahapan Algoritma',
            'module' => 'Minggu 8: Pemrograman Intensif',
            'body' => 'Kerjakan 100 tahapan praktik coding secara berurutan.',
            'question_type' => 'coding',
            'cpmk' => $this->cpmk1->code,
            'formats' => ['text'],
            'coding_steps' => $steps,
        ];

        $response = $this->actingAs($this->dosen)->post(
            route('dosen.item.store', $this->section->id),
            $payload
        );

        $response->assertRedirect(route('dosen.course.show', $this->section->id))
            ->assertSessionHasNoErrors();

        $assessment = Assessment::where('name', 'Praktikum 100 Tahapan Algoritma')->firstOrFail();
        $this->assertCount(100, $assessment->learning_payload['coding_steps']);
        $this->assertSame(100, $assessment->learning_payload['points']);
    }

    public function test_coding_step_template_has_tambahkan_pendukung(): void
    {
        $response = $this->actingAs($this->dosen)->get(route('dosen.item.create', [
            'course' => $this->section->id,
            'type' => 'tugas',
        ]));

        $response->assertOk();
        $response->assertSee('data-step-addon-menu', false);
        $response->assertSee('data-step-addon="files"', false);
        $response->assertSee('data-step-addon="link"', false);
        $response->assertSee('data-step-file-preview', false);
    }

    public function test_student_sees_coding_step_attachment_card(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('panduan_lab.pdf', 500, 'application/pdf');
        $attachment = \App\Models\Attachment::create([
            'user_id' => $this->dosen->id,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'panduan_lab.pdf',
            'path' => 'testing/panduan_lab.pdf',
            'mime' => 'application/pdf',
            'size' => 512000,
        ]);

        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-CODE-ATT',
            'name' => 'Praktikum dengan Lampiran',
            'type' => 'tugas',
            'final_weight' => 10,
            'status' => 'published',
            'learning_payload' => [
                'task_mode' => 'coding',
                'language' => 'python',
                'coding_steps' => [
                    [
                        'title' => 'Tahap 1: Setup',
                        'body' => 'Baca panduan lab terlampir.',
                        'cpmk' => $this->cpmk1->code,
                        'points' => 100,
                        'attachment' => $attachment->uuid,
                        'link' => 'https://youtu.be/ANPUoFuWpEo',
                    ],
                ],
            ],
        ]);

        $studentRole = Role::firstOrCreate(['name' => Role::MAHASISWA], ['label' => 'Mahasiswa']);
        $student = User::factory()->create([
            'role_id' => $studentRole->id,
            'email' => 'student.code@example.test',
        ]);
        $this->section->students()->attach($student->id);

        $response = $this->actingAs($student)->get(route('course.assignment.code', [$this->section->id, $assessment->id]));
        $response->assertOk();
        $response->assertSee('panduan_lab.pdf');
        $response->assertSee('PDF');
        $response->assertSee('Tonton Video');
    }

    public function test_lecturer_can_store_regular_task_even_if_hidden_empty_question_payload_is_present(): void
    {
        $payload = [
            'type' => 'tugas',
            'task_mode' => 'regular',
            'title' => 'Tugas Normal Tanpa Question',
            'module' => 'Minggu 5: Struktur Data Linear',
            'body' => 'Selesaikan instruksi tugas berikut secara mandiri.',
            'question_type' => 'uraian',
            'cpmk' => $this->cpmk1->code,
            'formats' => ['file', 'text'],
            'manual_cpmk_weights' => [
                'CPMK-1' => 100,
            ],
            // Simulasi input hidden question builder yang ikut terkirim dari DOM browser
            'questions' => [
                [
                    'type' => 'pilihan',
                    'prompt' => '',
                    'points' => 100,
                    'cpmk' => $this->cpmk1->code,
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)->post(
            route('dosen.item.store', $this->section->id),
            $payload
        );

        $response->assertRedirect(route('dosen.course.show', $this->section->id))
            ->assertSessionHasNoErrors();

        $assessment = Assessment::where('name', 'Tugas Normal Tanpa Question')->firstOrFail();
        $this->assertSame('tugas', $assessment->type);
        $this->assertSame('manual_cpmk', $assessment->learning_payload['scoring_mode']);

        $cpmkPivot = $assessment->cpmks()->get()->keyBy('code');
        $this->assertEqualsWithDelta(100.0, (float) $cpmkPivot['CPMK-1']->pivot->weight, 0.01);
    }

    public function test_lecturer_can_update_regular_task_even_if_hidden_empty_question_payload_is_present(): void
    {
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-EXISTING',
            'name' => 'Tugas Awal',
            'type' => 'tugas',
            'final_weight' => 10,
            'status' => 'published',
            'learning_payload' => [
                'type' => 'tugas',
                'task_mode' => 'regular',
                'title' => 'Tugas Awal',
                'module' => 'Minggu 1',
                'body' => 'Instruksi awal',
                'scoring_mode' => 'manual_cpmk',
                'manual_cpmk_weights' => ['CPMK-1' => 100],
            ],
        ]);

        $payload = [
            'type' => 'tugas',
            'task_mode' => 'regular',
            'title' => 'Tugas Awal Diperbarui',
            'module' => 'Minggu 1 Update',
            'body' => 'Instruksi diperbarui',
            'question_type' => 'uraian',
            'cpmk' => $this->cpmk1->code,
            'formats' => ['file', 'text'],
            'manual_cpmk_weights' => [
                'CPMK-1' => 100,
            ],
            'questions' => [
                [
                    'type' => 'pilihan',
                    'prompt' => '',
                    'points' => 100,
                    'cpmk' => $this->cpmk1->code,
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)->put(
            route('dosen.item.update', [$this->section->id, $assessment->id]),
            $payload
        );

        $response->assertRedirect(route('dosen.course.item', [$this->section->id, $assessment->id]))
            ->assertSessionHasNoErrors();

        $assessment->refresh();
        $this->assertSame('Tugas Awal Diperbarui', $assessment->name);
    }

    public function test_quiz_creation_still_validates_questions_prompt(): void
    {
        $payload = [
            'type' => 'kuis',
            'title' => 'Kuis Algoritma',
            'module' => 'Minggu 6: Kuis',
            'body' => 'Kerjakan kuis berikut.',
            'question_type' => 'uraian',
            'cpmk' => $this->cpmk1->code,
            'formats' => ['text'],
            'questions' => [
                [
                    'type' => 'pilihan',
                    'prompt' => '', // kosong, wajib gagal validasi untuk kuis
                    'points' => 100,
                    'cpmk' => $this->cpmk1->code,
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)->post(
            route('dosen.item.store', $this->section->id),
            $payload
        );

        $response->assertSessionHasErrors(['questions.0.prompt']);
    }

    public function test_task_deadline_is_optional_on_create(): void
    {
        $payload = [
            'type' => 'tugas',
            'task_mode' => 'regular',
            'title' => 'Tugas Tanpa Tenggat',
            'module' => 'Minggu 7: Analisis Algoritma',
            'body' => 'Kerjakan tugas tanpa batasan tenggat.',
            'question_type' => 'uraian',
            'cpmk' => $this->cpmk1->code,
            'formats' => ['file', 'text'],
            'manual_cpmk_weights' => [
                'CPMK-1' => 100,
            ],
            // 'due' tidak dikirim sama sekali (toggle nonaktif)
        ];

        $response = $this->actingAs($this->dosen)->post(
            route('dosen.item.store', $this->section->id),
            $payload
        );

        $response->assertRedirect(route('dosen.course.show', $this->section->id))
            ->assertSessionHasNoErrors();

        $assessment = Assessment::where('name', 'Tugas Tanpa Tenggat')->firstOrFail();
        $this->assertNull($assessment->due_at);
        $this->assertNull($assessment->learning_payload['due'] ?? null);

        // Pastikan tampilan course dapat dirender dan menampilkan label "Tugas perkuliahan"
        $courseResponse = $this->actingAs($this->dosen)->get(route('dosen.course.show', $this->section->id));
        $courseResponse->assertOk();
        $courseResponse->assertSee('Tugas Tanpa Tenggat');
        $courseResponse->assertSee('Tugas perkuliahan');
    }

    public function test_task_deadline_is_optional_on_update(): void
    {
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-DUE',
            'name' => 'Tugas Ber-Tenggat Semula',
            'type' => 'tugas',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDays(3),
            'learning_payload' => [
                'type' => 'tugas',
                'task_mode' => 'regular',
                'title' => 'Tugas Ber-Tenggat Semula',
                'module' => 'Minggu 2',
                'body' => 'Instruksi tugas',
                'due' => now()->addDays(3)->format('Y-m-d\TH:i'),
                'scoring_mode' => 'manual_cpmk',
                'manual_cpmk_weights' => ['CPMK-1' => 100],
            ],
        ]);

        $payload = [
            'type' => 'tugas',
            'task_mode' => 'regular',
            'title' => 'Tugas Ber-Tenggat Diubah Tanpa Tenggat',
            'module' => 'Minggu 2 Update',
            'body' => 'Instruksi tugas diperbarui',
            'question_type' => 'uraian',
            'cpmk' => $this->cpmk1->code,
            'formats' => ['file', 'text'],
            'manual_cpmk_weights' => [
                'CPMK-1' => 100,
            ],
            'due' => '', // kosong karena toggle dinonaktifkan
        ];

        $response = $this->actingAs($this->dosen)->put(
            route('dosen.item.update', [$this->section->id, $assessment->id]),
            $payload
        );

        $response->assertRedirect(route('dosen.course.item', [$this->section->id, $assessment->id]))
            ->assertSessionHasNoErrors();

        $assessment->refresh();
        $this->assertSame('Tugas Ber-Tenggat Diubah Tanpa Tenggat', $assessment->name);
        $this->assertNull($assessment->due_at);
        $this->assertNull($assessment->learning_payload['due']);
    }
}
