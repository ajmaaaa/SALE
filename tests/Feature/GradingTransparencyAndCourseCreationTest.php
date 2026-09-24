<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StudentAssessmentScore;
use App\Models\User;
use Database\Seeders\AcademicDemoSeeder;
use Database\Seeders\DemoLearningContentSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GradingTransparencyAndCourseCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;

    private User $student;

    private ClassSection $section;

    private Assessment $gradedTask;

    private Assessment $ungradedTask;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $dosenRole = Role::where('name', Role::DOSEN)->firstOrFail();
        $mhsRole = Role::where('name', Role::MAHASISWA)->firstOrFail();

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Teknik Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $mataKuliah = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'IF204', 'name' => 'Struktur Data dan Algoritma', 'sks' => 3]);

        $this->dosen = User::create([
            'name' => 'Dr. Budi Santoso, M.Kom.',
            'email' => 'budi@example.test',
            'password' => Hash::make('password'),
            'role_id' => $dosenRole->id,
            'nim_nidn' => '198501012010121001',
        ]);

        $this->student = User::create([
            'name' => 'Ahmad Maulana',
            'email' => 'ahmad.maulana@student.test',
            'password' => Hash::make('password'),
            'role_id' => $mhsRole->id,
            'nim_nidn' => '231011401234',
        ]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'TESTIF01',
        ]);

        $this->section->students()->attach($this->student->id);

        $this->gradedTask = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-01',
            'name' => 'Tugas 1: Binary Search Tree',
            'type' => 'tugas',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDays(5),
        ]);

        $this->ungradedTask = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-02',
            'name' => 'Tugas 2: Graph Traversal',
            'type' => 'tugas',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDays(10),
        ]);

        StudentAssessmentScore::create([
            'assessment_id' => $this->gradedTask->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 88.50,
            'feedback' => 'Implementasi traversal sudah sangat baik dan efisien.',
            'graded_by' => $this->dosen->id,
            'graded_at' => now(),
        ]);
    }

    public function test_student_assignment_page_displays_scores_and_proper_status(): void
    {
        $response = $this->actingAs($this->student)->get(route('mahasiswa.assignment.index'));

        $response->assertOk();
        $response->assertSee('Tugas 1: Binary Search Tree');
        $response->assertSee('89/100');
        $response->assertDontSee('Sudah dinilai (89/100)');
        $response->assertDontSee('Implementasi traversal sudah sangat baik');
        $response->assertSee('Tugas 2: Graph Traversal');
        $response->assertSee('Belum dikumpulkan');
        $response->assertSee('Tenggat');
    }

    public function test_student_assignment_tab_nilai_only_shows_graded_items(): void
    {
        $response = $this->actingAs($this->student)->get(route('mahasiswa.assignment.index', ['tab' => 'nilai']));

        $response->assertOk();
        $response->assertSee('Tugas 1: Binary Search Tree');
        $response->assertSee('89/100');
        $response->assertDontSee('Sudah dinilai (89/100)');
        $response->assertDontSee('Tugas 2: Graph Traversal');
    }

    public function test_student_item_detail_displays_grade_in_status_without_lecturer_feedback(): void
    {
        $response = $this->actingAs($this->student)->get(route('mahasiswa.course.item', [
            $this->section->id,
            $this->gradedTask->id,
        ]));

        $response->assertOk();
        $response->assertSee('89/100');
        $response->assertDontSee('Sudah dinilai');
        $response->assertDontSee('Catatan Dosen');
        $response->assertDontSee('Implementasi traversal sudah sangat baik dan efisien.');
    }

    public function test_graded_quiz_shows_only_automatic_score_status_without_lecturer_note(): void
    {
        $quiz = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'KUIS-01',
            'name' => 'Kuis Otomatis Struktur Data',
            'type' => 'kuis',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDay(),
        ]);
        StudentAssessmentScore::create([
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 92,
            'feedback' => 'Catatan ini tidak perlu ditampilkan untuk kuis otomatis.',
            'graded_by' => $this->dosen->id,
            'graded_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('mahasiswa.course.item', [
            $this->section->id,
            $quiz->id,
        ]));

        $response->assertOk();
        $response->assertSee('92/100');
        $response->assertDontSee('Sudah dinilai');
        $response->assertDontSee('Catatan Dosen');
        $response->assertDontSee('Catatan ini tidak perlu ditampilkan');
    }

    public function test_lecturer_can_create_course_with_video_and_view_it_without_error(): void
    {
        Storage::fake('local');
        $videoFile = UploadedFile::fake()->create('intro.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($this->dosen)->post(route('dosen.course.store'), [
            'code' => 'IF301',
            'title' => 'Pemrograman Web Lanjut',
            'lecturer' => $this->dosen->name,
            'description' => 'Mata kuliah pengembangan web modern berbasis arsitektur komponen.',
            'video_file' => $videoFile,
        ]);

        $newSection = ClassSection::whereHas('mataKuliah', fn ($q) => $q->where('code', 'IF301'))->first();
        $this->assertNotNull($newSection);
        $this->assertSame($this->dosen->id, $newSection->dosen_id);

        $response->assertRedirect(route('dosen.course.show', $newSection->id));

        $viewResponse = $this->actingAs($this->dosen)->get(route('dosen.course.show', $newSection->id));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Pemrograman Web Lanjut');
    }

    public function test_database_demo_courses_keep_pushed_youtube_and_mp4_examples(): void
    {
        Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'MATERI-YOUTUBE',
            'name' => 'Video Pengantar',
            'type' => 'materi',
            'final_weight' => 0,
            'learning_payload' => [
                'pin_video' => true,
                'video' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                'video_type' => 'url',
                'video_title' => 'Video Pengantar: Struktur Data dan Algoritma (YouTube)',
            ],
            'status' => 'published',
        ]);

        $youtubeCourse = $this->actingAs($this->student)->get(route('mahasiswa.course.show', $this->section));
        $youtubeCourse->assertOk()
            ->assertSee('youtube-nocookie.com/embed/aqz-KE-bpKQ', false);

        $secondCourse = MataKuliah::create([
            'prodi_id' => $this->section->mataKuliah->prodi_id,
            'code' => 'IF218',
            'name' => 'Interaksi Manusia dan Komputer',
            'sks' => 3,
        ]);
        $secondSection = ClassSection::create([
            'mata_kuliah_id' => $secondCourse->id,
            'semester_id' => $this->section->semester_id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'TESTIF02',
        ]);
        $secondSection->students()->attach($this->student->id);

        Assessment::create([
            'class_section_id' => $secondSection->id,
            'code' => 'MATERI-MP4',
            'name' => 'Video Materi Perkuliahan (.mp4)',
            'type' => 'materi',
            'final_weight' => 0,
            'learning_payload' => [
                'pin_video' => true,
                'video' => '00000000-0000-4000-8000-000000000001',
                'video_type' => 'file',
                'video_title' => 'Video Materi Perkuliahan (.mp4)',
            ],
            'status' => 'published',
        ]);

        $mp4Course = $this->actingAs($this->student)->get(route('mahasiswa.course.show', $secondSection));
        $mp4Course->assertOk()
            ->assertSee('<video', false)
            ->assertSee('video-pembelajaran-kuliah.mp4');

        // Course baru tanpa materi TIDAK boleh memiliki video
        $emptyCourse = MataKuliah::create([
            'prodi_id' => $this->section->mataKuliah->prodi_id,
            'code' => 'IF300',
            'name' => 'Mata Kuliah Baru Tanpa Materi',
            'sks' => 3,
        ]);
        $emptySection = ClassSection::create([
            'mata_kuliah_id' => $emptyCourse->id,
            'semester_id' => $this->section->semester_id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'TESTEMPTY',
        ]);
        $emptySection->students()->attach($this->student->id);
        $emptyView = $this->actingAs($this->student)->get(route('mahasiswa.course.show', $emptySection));
        $emptyView->assertOk()
            ->assertDontSee('id="course-video-card"', false);
    }

    public function test_forum_discussion_renders_without_missing_lecturer_key_error(): void
    {
        $response = $this->actingAs($this->student)->get(route('mahasiswa.discussion.index'));

        $response->assertOk();
        $response->assertSee('Forum Diskusi Perkuliahan');
        $response->assertSee('Struktur Data dan Algoritma');
        $response->assertSee('Pengampu: Dr. Budi Santoso, M.Kom.');
    }

    public function test_pdf_and_image_preview_are_rendered_on_material_and_task_views(): void
    {
        $response = $this->actingAs($this->student)->get(route('mahasiswa.course.item', [1, 2]));

        $response->assertOk();
        $response->assertSee('Modul-01-Pengantar-Struktur-Data.pdf');
    }

    public function test_database_material_and_late_task_render_generated_pdf_and_image_files(): void
    {
        $this->seed(DemoLearningContentSeeder::class);

        $material = Assessment::where('class_section_id', $this->section->id)
            ->where('code', 'MATERI-DEMO')
            ->firstOrFail();
        $task = Assessment::where('class_section_id', $this->section->id)
            ->where('code', 'TGS-LAMPIRAN')
            ->firstOrFail();

        $this->actingAs($this->student)
            ->get(route('mahasiswa.course.item', [$this->section->id, $material->id]))
            ->assertOk()
            ->assertSee('Modul-01-Pengantar-Struktur-Data.pdf');

        $this->actingAs($this->student)
            ->get(route('mahasiswa.course.item', [$this->section->id, $task->id]))
            ->assertOk()
            ->assertSee('Panduan-Evaluasi-Usability-Partisipan.pdf')
            ->assertSee('Lembar observasi dan wireframe pendukung tugas');

        $this->actingAs($this->student)
            ->get(route('mahasiswa.assignment.index'))
            ->assertOk()
            ->assertSee('Tugas Evaluasi Struktur Data dengan Lampiran')
            ->assertSee('Terlambat');
    }

    public function test_demo_database_adds_five_courses_in_admin_prodi_only(): void
    {
        $this->seed(AcademicDemoSeeder::class);

        $newCodes = ['IF218', 'IF221', 'IF240', 'IF250', 'IF260'];
        $this->assertSame(5, MataKuliah::whereIn('code', $newCodes)->count());

        $this->actingAs($this->student)
            ->get(route('mahasiswa.course.index'))
            ->assertOk()
            ->assertDontSee('IF260-A')
            ->assertDontSee('Pemrograman Web');

        $this->actingAs($this->dosen)
            ->get(route('dosen.course.index'))
            ->assertOk()
            ->assertDontSee('IF260-A')
            ->assertDontSee('Pemrograman Web');

        $adminProdi = User::where('email', 'adminprodi@example.test')->first();
        if ($adminProdi) {
            $this->actingAs($adminProdi)
                ->get(route('admin-prodi.akademik.matakuliah'))
                ->assertOk()
                ->assertSee('IF260')
                ->assertSee('Pemrograman Web');
        }
    }

    public function test_notifications_page_renders_all_tasks_and_allows_marking_read(): void
    {
        $response = $this->actingAs($this->student)->get(route('mahasiswa.notifications'));

        $response->assertOk();
        $response->assertSee('Notifikasi Pembelajaran');
        $response->assertSee('Semua');
        $response->assertSee('Tugas & Kuis');
        $response->assertSee('bg-brand-dark font-semibold text-white', false);
        $response->assertSee('notifikasi belum dibaca', false);

        $readResponse = $this->actingAs($this->student)->post(route('mahasiswa.notifications.read', 'pending_1'));
        $readResponse->assertRedirect();
        $this->assertContains('pending_1', session('learning.read_notifications', []));

        $allReadResponse = $this->actingAs($this->student)->post(route('mahasiswa.notifications.read', 'all'));
        $allReadResponse->assertRedirect();
        $this->assertContains('all', session('learning.read_notifications', []));
    }

    public function test_submitted_assignment_cannot_unsubmit_and_shows_diserahkan_state(): void
    {
        $this->actingAs($this->student);
        $this->post(route('mahasiswa.course.submit', [$this->section->id, $this->ungradedTask->id]), [
            'answer' => 'Jawaban saya yang sudah diserahkan.',
        ])->assertRedirect();

        $page = $this->get(route('mahasiswa.course.item', [$this->section->id, $this->ungradedTask->id]));
        $page->assertOk()
            ->assertSee('Sudah Diserahkan')
            ->assertDontSee('Batalkan Penyerahan')
            ->assertDontSee('+ Tambah atau buat');

        $cancel = $this->post(route('mahasiswa.course.submission.cancel', [$this->section->id, $this->ungradedTask->id]));
        $cancel->assertSessionHasErrors('submission');
    }

    public function test_quiz_room_completed_view_shows_answer_correctness_and_keys(): void
    {
        $this->actingAs($this->student);
        // Submit quiz 5 (Kuis Evaluasi Model)
        $this->post(route('mahasiswa.course.submit', [3, 5]), [
            'from_quiz_room' => 1,
            'question_answers' => [
                0 => ['choices' => ['F1-Score dan ROC-AUC']], // Correct (+25)
                1 => ['boolean_choice' => 'Benar'], // Wrong (Correct is Salah)
                2 => ['matching' => [0 => 'Salah Pasangan']], // Wrong
                3 => ['text' => 'Trade off precision dan recall...'], // Uraian
            ],
        ])->assertRedirect(route('mahasiswa.quiz.room', [3, 5]));

        $quizRoom = $this->get(route('mahasiswa.quiz.room', [3, 5]));
        $quizRoom->assertOk()
            ->assertSee('Nilai Perolehan Kuis:')
            ->assertSee('Kuis Terkunci (Telah Selesai)')
            ->assertDontSee('Lembar Jawaban Terkumpul')
            ->assertDontSee('Kuis Telah Berhasil Dikumpulkan!')
            ->assertSee('Hasil Pemeriksaan Lembar Jawaban')
            ->assertSee('Benar (+25 Poin)')
            ->assertSee('Salah (0 Poin)')
            ->assertSee('✓ Kunci Jawaban Benar')
            ->assertSee('Jawaban Anda (Benar)')
            ->assertSee('Jawaban Anda (Salah)');
    }

    public function test_database_quiz_loads_all_six_question_variations_and_renders_clean_evaluation(): void
    {
        $this->seed(\Database\Seeders\DemoLearningContentSeeder::class);

        $quiz = Assessment::where('class_section_id', $this->section->id)
            ->where('code', 'KUIS-01')
            ->firstOrFail();

        $this->actingAs($this->student);

        // 1. Ruang Ujian CBT memuat 6 variasi soal
        $response = $this->get(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));
        $response->assertOk()
            ->assertSee('Daftar Soal')
            ->assertSee('Pilihan Ganda')
            ->assertSee('Pilihan Ganda Kompleks')
            ->assertSee('Benar / Salah')
            ->assertSee('Mencocokkan Pasangan')
            ->assertSee('Pemrograman')
            ->assertSee('Uraian / Esai');

        // 2. Submit jawaban kuis
        $this->post(route('mahasiswa.course.submit', [$this->section->id, $quiz->id]), [
            'from_quiz_room' => 1,
            'question_answers' => [
                0 => ['choices' => ['Simpul 12 berada di subtree kiri dan simpul 18 berada di subtree kanan']],
                1 => ['choices' => [
                    'Traversal In-order pada BST akan menghasilkan urutan data terurut menaik (ascending)',
                    'Kompleksitas pencarian rata-rata pada balanced BST adalah O(log n)',
                ]],
                2 => ['boolean_choice' => 'Benar'],
                3 => ['matching' => [0 => 'Pre-order: Kunjungan Akar → Kiri → Kanan']],
                4 => ['text' => 'def insert(self, val): pass'],
                5 => ['text' => 'Pohon seimbang menjaga tinggi tetap O(log n).'],
            ],
        ])->assertRedirect(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));

        // 3. Layar evaluasi purna pengumpulan menampilkan ke-6 soal tanpa pill AI pudar
        $review = $this->get(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));
        $review->assertOk()
            ->assertSee('Nilai Perolehan Kuis:')
            ->assertSee('Kuis Terkunci (Telah Selesai)')
            ->assertDontSee('Lembar Jawaban Terkumpul')
            ->assertDontSee('Kuis Telah Berhasil Dikumpulkan!')
            ->assertSee('Hasil Pemeriksaan Lembar Jawaban')
            ->assertSee('6 Butir Soal')
            ->assertSee('Benar (+15 Poin)')
            ->assertSee('Praktikum Coding')
            ->assertSee('Uraian / Essay')
            ->assertDontSee('bg-emerald-100/70 border-emerald-200');
    }

    public function test_course_card_shows_red_deadline_for_lecturer_and_student(): void
    {
        $this->actingAs($this->student);
        $studentCourses = $this->get(route('mahasiswa.course.index'));
        $studentCourses->assertOk()
            ->assertSee('Tenggat:')
            ->assertSee('text-rose-600', false);

        $this->actingAs($this->dosen);
        $dosenCourses = $this->get(route('dosen.course.index'));
        $dosenCourses->assertOk()
            ->assertSee('Tenggat:')
            ->assertSee('text-rose-600', false)
            ->assertSee('QR');
    }

    public function test_lecturer_item_form_has_no_cpmk_summary_and_has_clean_total_points_badge(): void
    {
        $this->actingAs($this->dosen);
        $form = $this->get(route('dosen.item.create', $this->section->id));
        $form->assertOk()
            ->assertDontSee('data-toggle-cpmk-summary', false)
            ->assertDontSee('Ringkasan CPMK')
            ->assertDontSee('data-cpmk-summary-panel', false)
            ->assertSee('data-question-empty-state', false)
            ->assertSee('data-total-points-badge', false)
            ->assertDontSee('bg-slate-50 text-slate-700 border-line/80" data-total-points-badge', false);
    }

    public function test_matching_question_builder_has_three_clean_formats_and_randomized_answers(): void
    {
        $this->actingAs($this->dosen);
        $form = $this->get(route('dosen.item.create', $this->section->id));
        $form->assertOk()
            ->assertSee('Format Pasangan Menjodohkan:')
            ->assertSee('Teks ↔ Teks')
            ->assertDontSee('Gambar ↔ Teks')
            ->assertSee('Teks ↔ Gambar')
            ->assertSee('Gambar ↔ Gambar')
            ->assertSee('data-pair-mode', false);

        $coursePage = $this->get(route('dosen.course.show', $this->section->id));
        $coursePage->assertOk()
            ->assertSee('lg:sticky lg:top-20 z-20 self-start w-full', false)
            ->assertSee('id="diskusi-kelas"', false);
    }

    public function test_lecturer_can_pin_photo_to_course_and_student_sees_it(): void
    {
        Storage::fake('local');
        $imageFile = UploadedFile::fake()->image('diagram-arsitektur.png', 800, 600);

        $this->actingAs($this->dosen)->post(route('dosen.item.store', $this->section->id), [
            'type' => 'materi',
            'title' => 'Diagram Arsitektur Sistem',
            'module' => 'Minggu 1 · Pengantar',
            'body' => 'Pelajari diagram arsitektur sistem berikut.',
            'question_type' => 'uraian',
            'cpmk' => 'CPMK-01',
            'formats' => ['file', 'image'],
            'pin_video' => '1',
            'attachments' => [$imageFile],
        ])->assertRedirect(route('dosen.course.show', $this->section->id));

        $coursePage = $this->actingAs($this->student)->get(route('mahasiswa.course.show', $this->section->id));
        $coursePage->assertOk()
            ->assertSee('id="course-video-card"', false)
            ->assertSee('Media Foto Utama')
            ->assertSee('diagram-arsitektur.png');
    }

    public function test_matching_choices_differ_between_students(): void
    {
        $task = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-MATCH-TEST',
            'name' => 'Latihan Menjodohkan Beragam',
            'type' => 'tugas',
            'final_weight' => 10,
            'learning_payload' => [
                'module' => 'Minggu 2',
                'body' => 'Jodohkan istilah berikut.',
                'questions' => [[
                    'type' => 'mencocokkan',
                    'prompt' => 'Jodohkan istilah struktur data dengan definisinya:',
                    'points' => 30,
                    'options' => "Stack = LIFO (Last In First Out)\nQueue = FIFO (First In First Out)\nTree = Struktur Data Hierarkis Non-Linear\nGraph = Kumpulan Simpul dan Sisi\nArray = Elemen Kontigu Berindeks",
                ]],
            ],
            'status' => 'published',
        ]);

        $secondStudent = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi.santoso@student.test',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::MAHASISWA)->first()->id,
            'nim_nidn' => '231011409999',
        ]);
        $this->section->students()->attach($secondStudent->id);

        $resp1 = $this->actingAs($this->student)->get(route('mahasiswa.course.item', [$this->section->id, $task->id]));
        $resp1->assertOk();
        $html1 = $resp1->getContent();
        $resp2 = $this->actingAs($secondStudent)->get(route('mahasiswa.course.item', [$this->section->id, $task->id]));
        $resp2->assertOk();
        $html2 = $resp2->getContent();

        // Extract option order in dropdown for both students
        preg_match_all('/<option\s+value="([^"]+)"[^>]*>/i', $html1, $matches1);
        preg_match_all('/<option\s+value="([^"]+)"[^>]*>/i', $html2, $matches2);

        $this->assertNotEmpty($matches1[1]);
        $this->assertNotEmpty($matches2[1]);
        // The first non-empty option should not just be the un-shuffled first premise
        $this->assertNotEquals($matches1[1], $matches2[1]);
    }
}
