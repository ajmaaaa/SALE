<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Attachment;
use App\Models\ClassSection;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StudentAssessmentCpmkScore;
use App\Models\StudentAssessmentScore;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Models\User;
use App\Services\DatabaseNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
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

        $prodi = Prodi::create([
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

        $semester = Semester::create([
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
        $response->assertSee('data-apply-coding-count', false);
        $response->assertSee('data-auto-distribute-coding-points', false);

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
        $file = UploadedFile::fake()->create('panduan_lab.pdf', 500, 'application/pdf');
        $attachment = Attachment::create([
            'user_id' => $this->dosen->id,
            'uuid' => (string) Str::uuid(),
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

    public function test_lecturer_can_store_content_even_if_hidden_empty_coding_steps_payload_is_present(): void
    {
        $payload = [
            'type' => 'tugas',
            'task_mode' => 'regular',
            'title' => 'Tugas Bebas Bug Coding Steps',
            'module' => 'Minggu 7: OOP',
            'body' => 'Selesaikan tugas analisis OOP berikut.',
            'question_type' => 'uraian',
            'cpmk' => $this->cpmk1->code,
            'formats' => ['file', 'text'],
            'manual_cpmk_weights' => [
                'CPMK-1' => 100,
            ],
            // Simulasi form browser yang mengirimkan row 0 coding_steps kosong
            'coding_steps' => [
                [
                    'title' => '',
                    'body' => '',
                    'cpmk' => '',
                    'points' => '100',
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)->post(
            route('dosen.item.store', $this->section->id),
            $payload
        );

        $response->assertRedirect(route('dosen.course.show', $this->section->id))
            ->assertSessionHasNoErrors();

        $assessment = Assessment::where('name', 'Tugas Bebas Bug Coding Steps')->firstOrFail();
        $this->assertSame('tugas', $assessment->type);
    }

    public function test_lecturer_can_store_coding_task_when_coding_steps_initial_row_is_empty(): void
    {
        $payload = [
            'type' => 'coding',
            'task_mode' => 'coding',
            'title' => 'Praktikum Coding Mandiri',
            'module' => 'Modul 8: Python OOP',
            'body' => 'Buat class Mahasiswa dengan method display info.',
            'question_type' => 'coding',
            'cpmk' => $this->cpmk1->code,
            'formats' => ['text'],
            // Row 0 kosong (user hanya mengisi modul dan body di step 1)
            'coding_steps' => [
                [
                    'title' => '',
                    'body' => '',
                    'cpmk' => '',
                    'points' => '100',
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)->post(
            route('dosen.item.store', $this->section->id),
            $payload
        );

        $response->assertRedirect(route('dosen.course.show', $this->section->id))
            ->assertSessionHasNoErrors();

        $assessment = Assessment::where('name', 'Praktikum Coding Mandiri')->firstOrFail();
        $this->assertCount(1, $assessment->learning_payload['coding_steps']);
        $this->assertSame('Modul 8: Python OOP', $assessment->learning_payload['coding_steps'][0]['title']);
        $this->assertSame('Buat class Mahasiswa dengan method display info.', $assessment->learning_payload['coding_steps'][0]['body']);
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

    public function test_lecturer_can_store_programming_material_with_multiple_steps_without_points_or_grades(): void
    {
        $payload = [
            'type' => 'materi',
            'material_mode' => 'coding',
            'title' => 'Tutorial Dasar Algoritma Pohon',
            'module' => 'Minggu 4: Pengantar BST',
            'body' => 'Pelajari konsep struktur pohon biner dan traversal secara bertahap.',
            'question_type' => 'uraian',
            'cpmk' => $this->cpmk1->code,
            'coding_steps' => [
                [
                    'title' => 'Halaman 1: Konsep Simpul',
                    'cpmk' => $this->cpmk1->code,
                    'body' => 'Penjelasan struktur simpul node dan pointer kiri-kanan.',
                    'points' => null,
                ],
                [
                    'title' => 'Halaman 2: Penyisipan Rekursif',
                    'cpmk' => $this->cpmk1->code,
                    'body' => 'Panduan implementasi fungsi insert pada BST.',
                    'points' => null,
                ],
                [
                    'title' => 'Halaman 3: In-Order Traversal',
                    'cpmk' => $this->cpmk1->code,
                    'body' => 'Cara kerja traversal in-order untuk mencetak data terurut.',
                    'points' => null,
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)->post(
            route('dosen.item.store', $this->section->id),
            $payload
        );

        $response->assertSessionHasNoErrors();
        $assessment = Assessment::where('class_section_id', $this->section->id)
            ->where('name', 'Tutorial Dasar Algoritma Pohon')
            ->first();

        $this->assertNotNull($assessment);
        $this->assertSame('materi', $assessment->type);
        $this->assertSame('coding', $assessment->learning_payload['material_mode']);
        $this->assertCount(3, $assessment->learning_payload['coding_steps']);
        // Tidak ada questions atau asesmen tugas
        $this->assertEmpty($assessment->learning_payload['questions'] ?? []);
    }

    public function test_coding_task_single_step_fallback_and_multi_step_grading_rekap_and_student_views(): void
    {
        $mhsRole = Role::firstOrCreate(['name' => Role::MAHASISWA], ['label' => 'Mahasiswa']);
        $student = User::factory()->create([
            'role_id' => $mhsRole->id,
            'email' => 'mahasiswa.coding@example.test',
            'nim_nidn' => '230101001',
        ]);
        $this->section->students()->attach($student->id);

        // 1. Dosen membuat Tugas Coding dengan 2 langkah / butir soal berbeda CPMK dan Poin
        $createPayload = [
            'type' => 'tugas',
            'task_mode' => 'coding',
            'title' => 'Tugas Coding BST',
            'module' => 'Praktikum Struktur Data',
            'body' => 'Selesaikan instruksi coding BST berikut.',
            'question_type' => 'coding',
            'cpmk' => $this->cpmk1->code,
            'coding_steps' => [
                [
                    'title' => 'Soal 1: Kelas Node',
                    'cpmk' => $this->cpmk1->code,
                    'body' => 'Buat kelas Node.',
                    'points' => 40,
                ],
                [
                    'title' => 'Soal 2: Metode Insert',
                    'cpmk' => $this->cpmk2->code,
                    'body' => 'Implementasi metode insert.',
                    'points' => 60,
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)->post(
            route('dosen.item.store', $this->section->id),
            $createPayload
        );
        $response->assertSessionHasNoErrors();

        $assessment = Assessment::where('class_section_id', $this->section->id)
            ->where('name', 'Tugas Coding BST')
            ->firstOrFail();

        $this->assertSame('tugas', $assessment->type);
        $this->assertCount(2, $assessment->learning_payload['coding_steps']);
        $this->assertSame(100, $assessment->learning_payload['points']);
        $this->assertSame('automatic_cpmk', $assessment->learning_payload['scoring_mode']);

        // Verifikasi bobot CPMK proporsional terhadap poin (40% dan 60%)
        $assessment->load('cpmks');
        $this->assertCount(2, $assessment->cpmks);
        $pivotCpmk1 = $assessment->cpmks->firstWhere('id', $this->cpmk1->id);
        $pivotCpmk2 = $assessment->cpmks->firstWhere('id', $this->cpmk2->id);
        $this->assertEquals(40.0, (float) $pivotCpmk1->pivot->weight);
        $this->assertEquals(60.0, (float) $pivotCpmk2->pivot->weight);

        // 2. Mahasiswa mengumpulkan jawaban coding
        $submission = Submission::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'mahasiswa_id' => $student->id,
            'attempt' => 1,
            'version' => 1,
            'status' => 'submitted',
            'submitted_at' => now(),
            'answer' => json_encode([
                ['step' => 1, 'name' => 'solution_soal_1.py', 'code' => 'class Node: pass'],
                ['step' => 2, 'name' => 'solution_soal_2.py', 'code' => 'def insert(): pass'],
            ]),
        ]);

        // 3. Dosen menginput nilai coding (Soal 1: 35/40, Soal 2: 55/60 -> Total: 90/100)
        $gradeResponse = $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.student.coding_scores', [$this->section->id, $assessment->id, $student->id]),
            [
                'scores' => [
                    '1' => 35,
                    '2' => 55,
                ],
            ]
        );
        $gradeResponse->assertRedirect();
        $gradeResponse->assertSessionHasNoErrors();

        // Verifikasi SubmissionAnswer tersimpan
        $ans1 = SubmissionAnswer::where('submission_id', $submission->id)->where('question_id', '1')->firstOrFail();
        $ans2 = SubmissionAnswer::where('submission_id', $submission->id)->where('question_id', '2')->firstOrFail();
        $this->assertEquals(35.0, (float) $ans1->earned_score);
        $this->assertEquals(55.0, (float) $ans2->earned_score);

        // Verifikasi StudentAssessmentScore dan StudentAssessmentCpmkScore
        $totalScoreRec = StudentAssessmentScore::where('assessment_id', $assessment->id)
            ->where('mahasiswa_id', $student->id)
            ->firstOrFail();
        $this->assertEquals(90.0, (float) $totalScoreRec->score);
        $this->assertSame(StudentAssessmentScore::STATUS_PUBLISHED, $totalScoreRec->status);

        $cpmk1Score = StudentAssessmentCpmkScore::where('assessment_id', $assessment->id)
            ->where('cpmk_id', $this->cpmk1->id)
            ->where('mahasiswa_id', $student->id)
            ->firstOrFail();
        $cpmk2Score = StudentAssessmentCpmkScore::where('assessment_id', $assessment->id)
            ->where('cpmk_id', $this->cpmk2->id)
            ->where('mahasiswa_id', $student->id)
            ->firstOrFail();
        $this->assertEquals(35.0, (float) $cpmk1Score->score);
        $this->assertEquals(55.0, (float) $cpmk2Score->score);

        // 4. Verifikasi Tampilan Nilai di Rekap Dosen
        $rekapView = $this->actingAs($this->dosen)->get(route('dosen.penilaian.rekap', $this->section->id));
        $rekapView->assertOk();
        $rekapView->assertSee((string) $student->name);
        $rekapView->assertSee('35');
        $rekapView->assertSee('55');

        // 5. Verifikasi Tampilan Nilai di Mahasiswa
        // a. Halaman Detail Item
        $itemView = $this->actingAs($student)->get(route('mahasiswa.course.item', [$this->section->id, $assessment->id]));
        $itemView->assertOk();
        $itemView->assertSee('Nilai Tugas:');
        $itemView->assertSee('90/100 Poin');

        // b. Halaman Transkrip KHS
        $gradesView = $this->actingAs($student)->get(route('mahasiswa.nilai'));
        $gradesView->assertOk();
        $gradesView->assertSee('Tugas Coding BST');
        $gradesView->assertSee('90.0');

        // c. Halaman Editor Kode Mahasiswa
        $codeView = $this->actingAs($student)->get(route('course.assignment.code', [$this->section->id, $assessment->id]));
        $codeView->assertOk();
        $codeView->assertSee('90/100');
    }

    public function test_single_step_coding_task_fallback_preserves_100_points_and_integrates_correctly(): void
    {
        $mhsRole = Role::firstOrCreate(['name' => Role::MAHASISWA], ['label' => 'Mahasiswa']);
        $student = User::factory()->create([
            'role_id' => $mhsRole->id,
            'email' => 'mahasiswa.single@example.test',
            'nim_nidn' => '230101002',
        ]);
        $this->section->students()->attach($student->id);

        // Dosen membuat Tugas Coding tanpa menambah coding_steps (fallback tunggal otomatis)
        $payload = [
            'type' => 'tugas',
            'task_mode' => 'coding',
            'title' => 'Single Task Python',
            'module' => 'Minggu 1: Python Dasar',
            'body' => 'Buat fungsi print hello world.',
            'question_type' => 'coding',
            'cpmk' => $this->cpmk1->code,
        ];

        $res = $this->actingAs($this->dosen)->post(
            route('dosen.item.store', $this->section->id),
            $payload
        );
        $res->assertSessionHasNoErrors();

        $assessment = Assessment::where('class_section_id', $this->section->id)
            ->where('name', 'Single Task Python')
            ->firstOrFail();

        $this->assertCount(1, $assessment->learning_payload['coding_steps']);
        $this->assertSame(100, $assessment->learning_payload['coding_steps'][0]['points']);
        $this->assertSame(100, $assessment->learning_payload['points']);

        // Dosen menilai tugas tunggal ini dengan nilai 88
        $gradeRes = $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.student.coding_scores', [$this->section->id, $assessment->id, $student->id]),
            [
                'scores' => [
                    '1' => 88,
                ],
            ]
        );
        $gradeRes->assertRedirect();

        $totalScoreRec = StudentAssessmentScore::where('assessment_id', $assessment->id)
            ->where('mahasiswa_id', $student->id)
            ->firstOrFail();
        $this->assertEquals(88.0, (float) $totalScoreRec->score);

        // Mahasiswa melihat nilai di editor kode
        $codeView = $this->actingAs($student)->get(route('course.assignment.code', [$this->section->id, $assessment->id]));
        $codeView->assertOk();
        $codeView->assertSee('88/100');
    }

    public function test_task_score_color_reflects_cpmk_threshold_and_sidebar_zero_badge_is_hidden(): void
    {
        $mhsRole = Role::firstOrCreate(['name' => Role::MAHASISWA], ['label' => 'Mahasiswa']);
        $student = User::factory()->create([
            'role_id' => $mhsRole->id,
            'email' => 'mhs.score.color@example.test',
            'nim_nidn' => '230101003',
        ]);
        $this->section->students()->attach($student->id);

        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-TEST-01',
            'name' => 'Tugas Uji Ambang Batas CPMK',
            'type' => 'tugas',
            'final_weight' => 10.0,
            'status' => 'published',
            'learning_payload' => [
                'points' => 100,
                'task_mode' => 'coding',
                'body' => 'Uji warna nilai ambang batas',
                'coding_steps' => [
                    ['title' => 'Langkah 1', 'points' => 100, 'body' => 'Kode'],
                ],
            ],
        ]);
        // Set CPMK threshold 65
        $this->cpmk1->update(['threshold' => 65.0]);
        $assessment->cpmks()->attach($this->cpmk1->id, ['weight' => 100]);

        // 1. Kasus Nilai Rendah (50 < 65) -> Warna MERAH (text-rose-600 / bg-rose-50)
        StudentAssessmentScore::updateOrCreate(
            ['assessment_id' => $assessment->id, 'mahasiswa_id' => $student->id],
            ['score' => 50, 'status' => StudentAssessmentScore::STATUS_PUBLISHED]
        );

        $itemViewLow = $this->actingAs($student)->get(route('mahasiswa.course.item', [$this->section->id, $assessment->id]));
        $itemViewLow->assertOk();
        $itemViewLow->assertSee('text-rose-600');
        $this->assertStringContainsString('text-rose-600 font-mono">50/100 Poin', $itemViewLow->getContent());

        $codeViewLow = $this->actingAs($student)->get(route('course.assignment.code', [$this->section->id, $assessment->id]));
        $codeViewLow->assertOk();
        $codeViewLow->assertSee('bg-rose-50 border-rose-200 text-rose-700', false);

        // 2. Kasus Nilai Tinggi (85 >= 65) -> Warna HIJAU (text-emerald-600 / bg-emerald-50)
        StudentAssessmentScore::updateOrCreate(
            ['assessment_id' => $assessment->id, 'mahasiswa_id' => $student->id],
            ['score' => 85, 'status' => StudentAssessmentScore::STATUS_PUBLISHED]
        );

        $itemViewHigh = $this->actingAs($student)->get(route('mahasiswa.course.item', [$this->section->id, $assessment->id]));
        $itemViewHigh->assertOk();
        $itemViewHigh->assertSee('text-emerald-600');
        $this->assertStringContainsString('text-emerald-600 font-mono">85/100 Poin', $itemViewHigh->getContent());

        $codeViewHigh = $this->actingAs($student)->get(route('course.assignment.code', [$this->section->id, $assessment->id]));
        $codeViewHigh->assertOk();
        $codeViewHigh->assertSee('bg-emerald-50 border-emerald-200 text-emerald-800', false);

        // 3. Verifikasi badge notifikasi di sidebar jika 0 tidak muncul / hidden
        $notifService = app(DatabaseNotificationService::class);
        $notifs = $notifService->forUser($student, 'mahasiswa');
        $notifService->markRead($student, array_column($notifs, 'id'));

        $dashboardView = $this->actingAs($student)->get(route('mahasiswa.dashboard'));
        $dashboardView->assertOk();
        $dashHtml = $dashboardView->getContent();

        // Pastikan badge mhs-notif dan mhs-forum memiliki display: none !important; dan tidak berisi angka 0
        $this->assertMatchesRegularExpression('/id="sidebar-mhs-notif-badge"[^>]*style="[^"]*display:\s*none\s*!important;[^"]*"/', $dashHtml);
        $this->assertMatchesRegularExpression('/id="sidebar-mhs-forum-badge"[^>]*style="[^"]*display:\s*none\s*!important;[^"]*"/', $dashHtml);
        $this->assertDoesNotMatchRegularExpression('/id="sidebar-mhs-notif-badge"[^>]*>\s*0\s*<\/span>/', $dashHtml);
        $this->assertDoesNotMatchRegularExpression('/id="sidebar-mhs-forum-badge"[^>]*>\s*0\s*<\/span>/', $dashHtml);
    }

    public function test_empty_quiz_shows_disabled_button_and_does_not_open_new_page(): void
    {
        $mhsRole = Role::firstOrCreate(['name' => Role::MAHASISWA], ['label' => 'Mahasiswa']);
        $student = User::factory()->create([
            'role_id' => $mhsRole->id,
            'email' => 'mhs.quiz@example.test',
        ]);
        $this->section->students()->attach($student->id, ['status' => 'enrolled']);

        // Buat kuis tanpa butir soal
        $quiz = Assessment::create([
            'class_section_id' => $this->section->id,
            'name' => 'Kuis Logika Tanpa Soal',
            'code' => 'KUIS-001',
            'type' => 'kuis',
            'final_weight' => 10,
            'status' => 'published',
            'learning_payload' => [
                'type' => 'kuis',
                'task_mode' => 'quiz',
                'body' => 'Silakan kerjakan kuis berikut.',
                'questions' => [],
            ],
        ]);

        // 1. Pada halaman item mahasiswa, tombol kerjakan berstatus disabled dan pudar (opacity-50)
        $itemView = $this->actingAs($student)->get(route('mahasiswa.course.item', [$this->section->id, $quiz->id]));
        $itemView->assertOk();
        $itemView->assertSee('opacity-50 cursor-not-allowed', false);
        $itemView->assertSee('disabled', false);
        $itemView->assertSee('title="Soal belum tersedia"', false);
        $itemView->assertDontSee('href="'.route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]).'"', false);

        // 2. Jika mahasiswa langsung mengakses URL quiz-room, tidak menampilkan page "Soal Belum Tersedia"
        // melainkan diarahkan kembali (redirect) ke halaman item
        $quizRoomResponse = $this->actingAs($student)->get(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));
        $quizRoomResponse->assertRedirect(route('mahasiswa.course.item', [$this->section->id, $quiz->id]));
        $quizRoomResponse->assertSessionHas('notice', 'Soal kuis belum tersedia.');
    }

    public function test_legacy_coding_assessment_prefills_step_and_allows_editing_multiple_steps_with_cpmk(): void
    {
        // 1. Buat tugas pemrograman lama tanpa array coding_steps di payload (seperti Assessment 69)
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'PRAK-LEGACY',
            'name' => 'Praktikum Algoritma Legacy',
            'type' => 'coding',
            'final_weight' => 10,
            'status' => 'published',
            'learning_payload' => [
                'title' => 'Praktikum Algoritma Legacy',
                'type' => 'coding',
                'module' => 'Modul 1: Algoritma',
                'body' => 'Selesaikan implementasi algoritma pencarian.',
                'language' => 'python',
                'ai_enabled' => true,
                'cpmk' => $this->cpmk1->code,
                'points' => 100,
            ],
        ]);

        // 2. Akses halaman edit oleh dosen
        $editResponse = $this->actingAs($this->dosen)->get(route('dosen.item.edit', [
            'course' => $this->section->id,
            'item' => $assessment->id,
        ]));
        $editResponse->assertOk();

        // Verifikasi bahwa data lama (judul, instruksi, CPMK, poin) ter-prefill ke dalam data-old-coding-steps
        $editResponse->assertSee('Praktikum Algoritma Legacy');
        $editResponse->assertSee('Selesaikan implementasi algoritma pencarian.');
        $editResponse->assertSee($this->cpmk1->code);
        $editResponse->assertSee('data-apply-coding-count', false);
        $editResponse->assertSee('data-auto-distribute-coding-points', false);

        // 3. Simpan perubahan dengan 2 butir soal dan CPMK berbeda
        $updatePayload = [
            'type' => 'coding',
            'task_mode' => 'coding',
            'question_type' => 'coding',
            'title' => 'Praktikum Algoritma Multi-Soal',
            'module' => 'Modul 1: Algoritma',
            'body' => 'Instruksi umum praktikum.',
            'coding_steps' => [
                [
                    'title' => 'Soal 1: Binary Search',
                    'body' => 'Implementasikan binary search.',
                    'cpmk' => $this->cpmk1->code,
                    'points' => 50,
                ],
                [
                    'title' => 'Soal 2: Linear Search',
                    'body' => 'Implementasikan linear search.',
                    'cpmk' => $this->cpmk2->code,
                    'points' => 50,
                ],
            ],
        ];

        $updateResponse = $this->actingAs($this->dosen)->put(
            route('dosen.item.update', ['course' => $this->section->id, 'item' => $assessment->id]),
            $updatePayload
        );

        $updateResponse->assertRedirect(route('dosen.course.item', [$this->section->id, $assessment->id]))
            ->assertSessionHasNoErrors();

        $assessment->refresh();
        $this->assertCount(2, $assessment->learning_payload['coding_steps']);
        $this->assertSame('Soal 1: Binary Search', $assessment->learning_payload['coding_steps'][0]['title']);
        $this->assertSame($this->cpmk1->code, $assessment->learning_payload['coding_steps'][0]['cpmk']);
        $this->assertSame(50, $assessment->learning_payload['coding_steps'][0]['points']);
        $this->assertSame('Soal 2: Linear Search', $assessment->learning_payload['coding_steps'][1]['title']);
        $this->assertSame($this->cpmk2->code, $assessment->learning_payload['coding_steps'][1]['cpmk']);
        $this->assertSame(50, $assessment->learning_payload['coding_steps'][1]['points']);
    }

    public function test_quiz_and_coding_assignment_timer_integration_persists_and_renders_timer(): void
    {
        $mhsRole = Role::firstOrCreate(['name' => Role::MAHASISWA], ['label' => 'Mahasiswa']);
        $student = User::factory()->create([
            'role_id' => $mhsRole->id,
            'email' => 'mhs.timer@example.test',
        ]);
        $this->section->students()->attach($student->id, ['status' => 'enrolled']);

        // 1. Dosen membuat Kuis dengan timer 45 menit
        $quizPayload = [
            'type' => 'kuis',
            'task_mode' => 'quiz',
            'question_type' => 'uraian',
            'title' => 'Kuis Algoritma Berwaktu',
            'module' => 'Modul 2: Kompleksitas',
            'body' => 'Kerjakan kuis dengan cermat sebelum timer habis.',
            'duration_mode' => 'enabled',
            'duration_minutes' => 45,
            'cpmk' => $this->cpmk1->code,
            'questions' => [
                [
                    'type' => 'uraian',
                    'prompt' => 'Jelaskan kompleksitas binary search.',
                    'cpmk' => $this->cpmk1->code,
                    'points' => 100,
                ],
            ],
        ];

        $quizStore = $this->actingAs($this->dosen)->post(
            route('dosen.item.store', $this->section->id),
            $quizPayload
        );
        $quizStore->assertRedirect(route('dosen.course.show', $this->section->id))
            ->assertSessionHasNoErrors();

        $quiz = Assessment::where('name', 'Kuis Algoritma Berwaktu')->firstOrFail();
        $this->assertTrue($quiz->learning_payload['duration_enabled']);
        $this->assertSame(45, $quiz->learning_payload['duration_minutes']);

        // Mahasiswa melihat halaman detail kuis dan countdown timer di quiz room
        $itemView = $this->actingAs($student)->get(route('mahasiswa.course.item', [$this->section->id, $quiz->id]));
        $itemView->assertOk();
        $itemView->assertSee('45 menit');

        $quizRoomView = $this->actingAs($student)->get(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));
        $quizRoomView->assertOk();
        $quizRoomView->assertSee('data-duration="2700"', false);

        // 2. Dosen membuat Tugas Pemrograman dengan timer 90 menit menggunakan duration_toggle
        $codingPayload = [
            'type' => 'tugas',
            'task_mode' => 'coding',
            'question_type' => 'coding',
            'title' => 'Praktikum Sorting Berwaktu',
            'module' => 'Modul 3: Sorting',
            'body' => 'Selesaikan implementasi sorting algoritma dalam waktu yang ditentukan.',
            'duration_toggle' => '1',
            'duration_minutes' => 90,
            'cpmk' => $this->cpmk1->code,
            'coding_steps' => [
                [
                    'title' => 'Soal 1: Quick Sort',
                    'body' => 'Tulis fungsi quicksort.',
                    'cpmk' => $this->cpmk1->code,
                    'points' => 100,
                ],
            ],
        ];

        $codingStore = $this->actingAs($this->dosen)->post(
            route('dosen.item.store', $this->section->id),
            $codingPayload
        );
        $codingStore->assertRedirect(route('dosen.course.show', $this->section->id))
            ->assertSessionHasNoErrors();

        $codingAssessment = Assessment::where('name', 'Praktikum Sorting Berwaktu')->firstOrFail();
        $this->assertTrue($codingAssessment->learning_payload['duration_enabled']);
        $this->assertSame(90, $codingAssessment->learning_payload['duration_minutes']);

        // Mahasiswa melihat halaman editor coding dengan timer 90 menit (5400 detik) dan deadline attempt akun pengguna
        $codeEditorView = $this->actingAs($student)->get(route('course.assignment.code', [$this->section->id, $codingAssessment->id]));
        $codeEditorView->assertOk();
        $codeEditorView->assertSee('id="code-countdown"', false);
        $codeEditorView->assertSee('data-duration="5400"', false);
        $codeEditorView->assertSee('data-deadline=', false);

        // Mahasiswa kedua memulai pengerjaan secara terpisah dan mendapatkan attempt waktu mandiri
        $studentRole = Role::firstOrCreate(['name' => Role::MAHASISWA], ['label' => 'Mahasiswa']);
        $student2 = User::factory()->create(['role_id' => $studentRole->id]);
        $this->section->students()->attach($student2->id, ['status' => 'enrolled']);

        $codeEditorView2 = $this->actingAs($student2)->get(route('course.assignment.code', [$this->section->id, $codingAssessment->id]));
        $codeEditorView2->assertOk();
        $codeEditorView2->assertSee('data-deadline=', false);

        $attempt1 = AssessmentAttempt::where('assessment_id', $codingAssessment->id)->where('mahasiswa_id', $student->id)->firstOrFail();
        $attempt2 = AssessmentAttempt::where('assessment_id', $codingAssessment->id)->where('mahasiswa_id', $student2->id)->firstOrFail();
        $this->assertSame($student->id, $attempt1->mahasiswa_id);
        $this->assertSame($student2->id, $attempt2->mahasiswa_id);

        // 3. Dosen mengubah tugas menjadi tanpa batas waktu (duration_mode = disabled)
        $codingPayload['duration_mode'] = 'disabled';
        unset($codingPayload['duration_toggle']);
        $updateResponse = $this->actingAs($this->dosen)->put(
            route('dosen.item.update', ['course' => $this->section->id, 'item' => $codingAssessment->id]),
            $codingPayload
        );
        $updateResponse->assertRedirect(route('dosen.course.item', [$this->section->id, $codingAssessment->id]))
            ->assertSessionHasNoErrors();

        $codingAssessment->refresh();
        $this->assertFalse($codingAssessment->learning_payload['duration_enabled']);
        $this->assertNull($codingAssessment->learning_payload['duration_minutes']);

        // 4. Verifikasi form dosen memuat stepper setup dan builder
        $createForm = $this->actingAs($this->dosen)->get(route('dosen.item.create', [
            'course' => $this->section->id,
            'type' => 'kuis',
        ]));
        $createForm->assertOk();
        $createForm->assertSee('data-content-progress', false);
        $createForm->assertSee('data-next-to-questions', false);
        $createForm->assertSee('data-back-to-setup', false);
        $html = $createForm->getContent();
        $setupPos = strpos($html, 'data-content-setup');
        $builderPos = strpos($html, 'data-question-builder');
        $codingBuilderPos = strpos($html, 'data-coding-step-builder');
        $this->assertNotFalse($setupPos);
        $this->assertNotFalse($builderPos);
        $this->assertNotFalse($codingBuilderPos);
        $this->assertTrue($setupPos < $builderPos, 'Setup information must be positioned above question builder.');
        $this->assertTrue($builderPos < $codingBuilderPos, 'Question builder and coding builder must follow downwards.');
    }
}
