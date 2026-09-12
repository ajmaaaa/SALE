<button type="button" class="assessment-document text-xs font-semibold text-brand"
        data-submission-open
        data-student-name="{{ $stu['name'] }}"
        data-student-number="{{ $stu['number'] }}"
        data-files="{{ json_encode(\App\Support\SubmissionPreview::files($courseId, $activeType, $stu['number'])) }}"
        aria-haspopup="dialog" aria-controls="submission-preview">
    Cek Tugas
</button>
