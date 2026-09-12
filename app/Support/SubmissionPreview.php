<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class SubmissionPreview
{
    public static function files(int $course, string $type, string $student): array
    {
        $component = ['quiz' => 'kuis', 'project' => 'proyek'][$type] ?? $type;
        $files = [];
        foreach (LearningPreview::items() as $id => $item) {
            $itemComponent = $item['component'] ?? ($item['type'] === 'coding' ? 'tugas' : $item['type']);
            $submission = session("learning.submissions.$id", []);
            // Legacy submissions have no owner; never attribute them to a sample student.
            if ((int) $item['course'] !== $course || $itemComponent !== $component || ($submission['student_number'] ?? null) !== $student) {
                continue;
            }
            foreach ($submission['files'] ?? [] as $file) {
                $meta = session("learning.files.$file");
                if (!$meta || !Storage::disk('local')->exists($meta['path'])) {
                    continue;
                }
                $files[] = [
                    'name' => $meta['name'],
                    'mime' => $meta['mime'],
                    'size' => number_format(Storage::disk('local')->size($meta['path']) / 1024, 1).' KB',
                    'time' => $submission['time'] ?? 'Belum tersedia',
                    'assessment' => $item['title'],
                    'url' => route('preview.file', ['file' => $file, 'inline' => 1]),
                    'download' => route('preview.file', ['file' => $file, 'download' => 1]),
                ];
            }
        }

        return $files;
    }
}
