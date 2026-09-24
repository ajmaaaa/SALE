@php
    $cId = $courseId ?? (int) request()->query('course', 1);
    $typeStr = $activeType ?? (string) request()->query('type', 'tugas');
    $studentNumber = $stu['number'] ?? '';
    $studentName = $stu['name'] ?? 'Mahasiswa';
    $files = \App\Support\SubmissionPreview::files((int) $cId, (string) $typeStr, (string) $studentNumber);

    // Fallback contoh pratinjau tugas untuk mahasiswa yang sudah mengumpulkan di mode demo/contoh
    if (empty($files) && in_array($studentNumber, ['2024081001', '2024081003'], true)) {
        if ($studentNumber === '2024081001') {
            $sampleId = '00000000-0000-4000-8000-000000000004';
            $meta = \App\Support\LearningPreview::fileMeta($sampleId);
            if ($meta) {
                $files[] = [
                    'name' => 'Laporan-Tugas-Mahasiswa-AndiPratama.pdf',
                    'mime' => 'application/pdf',
                    'size' => '245.4 KB',
                    'time' => $stu['submitted_at'] ?? '05 Sep 2026, 10:30 WIB',
                    'assessment' => 'Tugas: Usability & Pengujian Antarmuka',
                    'url' => route('preview.file', ['file' => $sampleId, 'inline' => 1]),
                    'download' => route('preview.file', ['file' => $sampleId, 'download' => 1]),
                ];
            }
        } elseif ($studentNumber === '2024081003') {
            $sampleId = '00000000-0000-4000-8000-000000000003';
            $meta = \App\Support\LearningPreview::fileMeta($sampleId);
            if ($meta) {
                $files[] = [
                    'name' => 'diagram-pohon-biner-dan-traversal.png',
                    'mime' => 'image/png',
                    'size' => '118.2 KB',
                    'time' => $stu['submitted_at'] ?? '04 Sep 2026, 22:15 WIB',
                    'assessment' => 'Tugas: Diagram Evaluasi Struktur Data',
                    'url' => route('preview.file', ['file' => $sampleId, 'inline' => 1]),
                    'download' => route('preview.file', ['file' => $sampleId, 'download' => 1]),
                ];
            }
        }
    }
@endphp

<button type="button" class="assessment-document inline-flex items-center gap-1.5 text-xs font-semibold text-brand hover:text-brand-dark transition px-2 py-1 rounded hover:bg-brand-soft/40"
        data-submission-open
        data-student-name="{{ $studentName }}"
        data-student-number="{{ $studentNumber }}"
        data-files="{{ json_encode($files) }}"
        aria-haspopup="dialog" aria-controls="submission-preview"
        title="Lihat Tugas / Cek Tugas {{ $studentName }}">
    <svg class="h-3.5 w-3.5 shrink-0 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
        <circle cx="12" cy="12" r="3"/>
    </svg>
    <span>Lihat Tugas</span>
    <span class="sr-only">Cek Tugas</span>
</button>
