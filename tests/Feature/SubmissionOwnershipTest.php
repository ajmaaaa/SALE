<?php

namespace Tests\Feature;

use App\Support\AdminPreview;
use App\Support\AcademicPreview;
use App\Support\LearningPreview;
use App\Support\SubmissionPreview;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionOwnershipTest extends TestCase
{
    public function test_students_keep_distinct_submissions_and_resubmission_only_clears_their_grade(): void
    {
        Storage::fake('local');
        $first = AdminPreview::users()[1];
        $second = array_replace($first, ['id' => 4, 'number' => 'STUDENT-4', 'name' => 'Student Two']);
        $this->withSession(['admin.users' => AdminPreview::users() + [4 => $second], 'auth_user' => $first])
            ->post('/mahasiswa/course/2/item/4/submission', ['files' => [UploadedFile::fake()->create('first.pdf', 10, 'application/pdf')]])
            ->assertSessionHasNoErrors();
        $firstFiles = LearningPreview::submission(4, 1)['files'];

        $this->withSession(['auth_user' => $second])->get('/mahasiswa/course/2/item/4')
            ->assertOk()->assertDontSee('first.pdf');
        $this->get('/mahasiswa/notifikasi')->assertOk()->assertSee('Belum ada notifikasi baru.');
        $this->get('/mahasiswa/course/2')->assertOk();
        $this->get('/mahasiswa/assignment')->assertOk();
        $this->post('/mahasiswa/course/2/item/4/submission', ['keep_files' => $firstFiles, 'answer' => 'Borrow'])
            ->assertUnprocessable();
        $this->post('/mahasiswa/course/2/item/4/submission', ['files' => [UploadedFile::fake()->create('second.pdf', 10, 'application/pdf')]])
            ->assertSessionHasNoErrors();
        $secondSubmission = LearningPreview::submission(4, 4);
        $this->assertCount(1, $secondSubmission['files']);
        $this->assertNotSame($firstFiles, $secondSubmission['files']);
        $this->assertSame('first.pdf', SubmissionPreview::files(2, 'tugas', $first['number'], 4)[0]['name']);
        $this->assertSame('second.pdf', SubmissionPreview::files(2, 'tugas', $second['number'], 4)[0]['name']);
        $items = LearningPreview::items();
        $items[7] = array_replace($items[4], ['id' => 7, 'title' => 'Another assessment']);
        session(['learning.items' => $items, 'learning.student_submissions.7.4' => $secondSubmission]);
        $this->assertCount(2, SubmissionPreview::files(2, 'tugas', $second['number']));
        $this->assertCount(1, SubmissionPreview::files(2, 'tugas', $second['number'], 4));

        $this->withSession(['auth_user' => $first, 'academic.item_grades.4' => [1 => ['score' => 80], 4 => ['score' => 90]]])
            ->post('/mahasiswa/course/2/item/4/submission', ['answer' => 'Revised'])->assertSessionHasNoErrors();
        $this->assertNull(session('academic.item_grades.4.1'));
        $this->assertSame(['score' => 90], session('academic.item_grades.4.4'));
        $this->assertSame($firstFiles, LearningPreview::submission(4)['files']);
        $this->assertSame($secondSubmission, LearningPreview::submission(4, 4));
        $this->get('/mahasiswa/course/2/item/4')->assertSee('first.pdf')->assertDontSee('second.pdf');
        $this->get('/mahasiswa/notifikasi')->assertOk()->assertSee('Jawaban Laporan Evaluasi Usability dikumpulkan')->assertDontSee('Another assessment');
    }

    public function test_legacy_requires_owner_and_new_storage_takes_precedence(): void
    {
        $this->withSession(['learning.submissions.4' => ['answer' => 'Anonymous']]);
        $this->assertNull(LearningPreview::submission(4));
        session(['learning.submissions.4.student_number' => AdminPreview::users()[1]['number']]);
        $this->assertSame('Anonymous', LearningPreview::submission(4, 1)['answer']);
        $this->assertNull(LearningPreview::submission(4, 4));
        session(['learning.submissions.4' => ['student_id' => 4, 'answer' => 'Owned']]);
        $this->assertSame('Owned', LearningPreview::submission(4, 4)['answer']);
        $this->assertNull(LearningPreview::submission(4, 1));
        session(['learning.student_submissions.4.4' => ['student_id' => 4, 'answer' => 'New']]);
        $this->assertSame('New', LearningPreview::submission(4, 4)['answer']);
    }

    public function test_authoring_validates_relative_weights_and_questionless_mapping(): void
    {
        $base = ['type' => 'coding', 'title' => 'Code', 'module' => 'Module', 'body' => 'Implement',
            'question_type' => 'coding', 'cpmk' => 'CPMK-01', 'formats' => ['text']];
        foreach ([0, -1, 1001, 'invalid'] as $weight) {
            $this->post('/dosen/course/1/items', $base + ['assessment_weight' => $weight])->assertSessionHasErrors('assessment_weight');
        }
        $this->post('/dosen/course/1/items', array_replace($base, ['cpmk' => 'Prose']))->assertSessionHasErrors('cpmk');
        foreach (['uts', 'uas', 'proyek'] as $component) {
            $this->post('/dosen/course/1/items', $base + ['component' => $component, 'assessment_weight' => 2.5])->assertSessionHasNoErrors();
            $item = collect(LearningPreview::items())->last();
            $this->assertSame($component, $item['component']);
            $this->assertSame('coding', $item['type']);
            $this->assertSame(2.5, $item['assessment_weight']);
        }
        $this->post('/dosen/course/1/items', $base + ['assessment_weight' => null])->assertSessionHasNoErrors();
        $item = collect(LearningPreview::items())->last();
        $this->assertSame(1.0, $item['assessment_weight']);
        $this->assertSame('tugas', $item['component']);
        $config = AcademicPreview::config(1);
        $config['components'] = array_values(array_filter($config['components'], fn ($component) => $component['code'] !== 'tugas'));
        $this->withSession(['academic.config.1' => $config])->post('/dosen/course/1/items', $base)->assertSessionHasErrors('component');
    }

    public function test_multi_cpmk_questions_keep_points_and_derive_cpl(): void
    {
        $this->post('/dosen/course/1/items', ['type' => 'kuis', 'title' => 'Mixed', 'module' => 'Module', 'body' => 'Answer',
            'question_type' => 'uraian', 'cpmk' => 'General metadata', 'formats' => ['text'], 'assessment_weight' => 3,
            'questions' => [
                ['type' => 'uraian', 'prompt' => 'First', 'points' => 20, 'cpmk' => 'CPMK-01'],
                ['type' => 'uraian', 'prompt' => 'Second', 'points' => 80, 'cpmk' => 'CPMK-02'],
            ],
        ])->assertSessionHasNoErrors();
        $item = collect(LearningPreview::items())->last();
        $this->assertSame(100, $item['points']);
        $this->assertSame('kuis', $item['component']);
        $this->assertSame(3.0, $item['assessment_weight']);
        $this->assertSame(['CPL-01', 'CPL-02'], array_column($item['questions'], 'cpl'));
        $this->assertSame(['CPMK-01', 'CPMK-02'], array_column($item['questions'], 'cpmk'));
        $this->get(route('dosen.item.create', 1))->assertOk()->assertSee('name="assessment_weight"', false);
    }
}
