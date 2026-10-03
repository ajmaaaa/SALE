<?php

namespace App\Services\Ai;

use App\Models\Assessment;
use Illuminate\Support\Str;

class ContextBuilder
{
    /**
     * Build the structured context for AI tutoring from server-side source of truth and sanitized client input.
     *
     * @param  Assessment|object|array|null  $assessment
     * @param  array  $input  [question, code, console_output, selected_line]
     * @return array{
     *     assessment_type: string,
     *     title: string,
     *     description: string,
     *     coding_steps: string,
     *     linked_materials: string,
     *     code: string,
     *     console_output: string,
     *     selected_line: string,
     *     question: string
     * }
     */
    public function build($assessment, array $input): array
    {
        $type = $this->resolveType($assessment);
        $title = $this->resolveTitle($assessment);
        $description = $this->resolveDescription($assessment);
        $codingSteps = $this->buildCodingSteps($assessment);
        $linkedMaterials = $this->buildLinkedMaterials($assessment);

        $sanitizedInput = $this->sanitizeStudentInput($input);

        return [
            'assessment_type' => $type,
            'title' => $title,
            'description' => $description,
            'coding_steps' => $codingSteps,
            'linked_materials' => $linkedMaterials,
            'code' => $sanitizedInput['code'],
            'console_output' => $sanitizedInput['console_output'],
            'selected_line' => $sanitizedInput['selected_line'],
            'question' => $sanitizedInput['question'],
        ];
    }

    protected function resolveType($assessment): string
    {
        if ($assessment instanceof Assessment) {
            return $assessment->type ?: 'tugas';
        }

        if (is_array($assessment)) {
            return (string) ($assessment['type'] ?? 'tugas');
        }

        if (is_object($assessment)) {
            return (string) ($assessment->type ?? 'tugas');
        }

        return 'tugas';
    }

    protected function resolveTitle($assessment): string
    {
        if ($assessment instanceof Assessment) {
            return (string) $assessment->name;
        }

        if (is_array($assessment)) {
            return (string) ($assessment['name'] ?? $assessment['title'] ?? 'Praktikum Pemrograman');
        }

        if (is_object($assessment)) {
            return (string) ($assessment->name ?? $assessment->title ?? 'Praktikum Pemrograman');
        }

        return 'Praktikum Pemrograman';
    }

    protected function resolveDescription($assessment): string
    {
        $raw = '';
        if ($assessment instanceof Assessment) {
            $raw = (string) ($assessment->description ?? ($assessment->learning_payload['body'] ?? ''));
        } elseif (is_array($assessment)) {
            $raw = (string) ($assessment['description'] ?? $assessment['body'] ?? '');
        } elseif (is_object($assessment)) {
            $raw = (string) ($assessment->description ?? $assessment->body ?? '');
        }

        return trim(strip_tags($raw));
    }

    protected function buildCodingSteps($assessment): string
    {
        $steps = [];
        if ($assessment instanceof Assessment) {
            $steps = $assessment->learning_payload['coding_steps'] ?? [];
        } elseif (is_array($assessment)) {
            $steps = $assessment['coding_steps'] ?? ($assessment['learning_payload']['coding_steps'] ?? []);
        } elseif (is_object($assessment) && isset($assessment->learning_payload)) {
            $payload = is_array($assessment->learning_payload) ? $assessment->learning_payload : json_decode($assessment->learning_payload, true);
            $steps = $payload['coding_steps'] ?? [];
        }

        if (empty($steps) || ! is_array($steps)) {
            return '(Tidak ada tahapan terstruktur)';
        }

        $formatted = [];
        foreach ($steps as $idx => $step) {
            if (! is_array($step)) {
                continue;
            }
            $num = $idx + 1;
            $title = $step['title'] ?? "Tahap {$num}";
            $cpmk = ! empty($step['cpmk']) ? " [Target CPMK: {$step['cpmk']}]" : '';
            // Only read instructions, NEVER solutions or answer keys
            $instruction = trim(strip_tags((string) ($step['body'] ?? $step['instruction'] ?? '')));

            $line = "{$num}. {$title}{$cpmk}";
            if ($instruction !== '') {
                $line .= ": {$instruction}";
            }
            $formatted[] = $line;
        }

        return ! empty($formatted) ? implode("\n", $formatted) : '(Tidak ada tahapan terstruktur)';
    }

    protected function buildLinkedMaterials($assessment): string
    {
        $sectionId = null;
        $linkedIds = [];

        if ($assessment instanceof Assessment) {
            $sectionId = $assessment->class_section_id;
            $linkedIds = $assessment->learning_payload['linked_material_ids'] ?? [];
        } elseif (is_array($assessment)) {
            $sectionId = $assessment['class_section_id'] ?? $assessment['course'] ?? null;
            $linkedIds = $assessment['linked_material_ids'] ?? ($assessment['learning_payload']['linked_material_ids'] ?? []);
        } elseif (is_object($assessment)) {
            $sectionId = $assessment->class_section_id ?? $assessment->course ?? null;
            if (isset($assessment->learning_payload)) {
                $payload = is_array($assessment->learning_payload) ? $assessment->learning_payload : json_decode($assessment->learning_payload, true);
                $linkedIds = $payload['linked_material_ids'] ?? [];
            }
        }

        if (! is_array($linkedIds)) {
            $linkedIds = [];
        }
        $linkedIds = array_values(array_filter(array_map('intval', $linkedIds)));

        $maxTotalChars = 6000;
        $maxPerMaterialChars = 1500;

        // 1. Jika linked_material_ids ditentukan dosen
        if (! empty($linkedIds)) {
            $materials = Assessment::whereIn('id', $linkedIds)
                ->where('type', 'materi')
                ->where('status', Assessment::STATUS_PUBLISHED)
                ->get(['id', 'name', 'description', 'learning_payload']);

            $results = [];
            $currentChars = 0;
            foreach ($materials as $mat) {
                $body = $mat->learning_payload['body'] ?? $mat->description ?? '';
                $clean = trim(strip_tags((string) $body));
                $trimmed = mb_substr($clean, 0, $maxPerMaterialChars);
                $entry = "- {$mat->name}: {$trimmed}";

                if ($currentChars + mb_strlen($entry) > $maxTotalChars) {
                    $available = max(0, $maxTotalChars - $currentChars);
                    if ($available > 20) {
                        $results[] = mb_substr($entry, 0, $available).'...';
                    }
                    break;
                }

                $results[] = $entry;
                $currentChars += mb_strlen($entry) + 1;
            }

            if (! empty($results)) {
                return implode("\n", $results);
            }
        }

        // 2. Fallback: judul + ringkasan maks 120 karakter dari maks 30 materi terbit di kelas yang sama
        if ($sectionId) {
            $materials = Assessment::where('class_section_id', $sectionId)
                ->where('type', 'materi')
                ->where('status', Assessment::STATUS_PUBLISHED)
                ->orderBy('id')
                ->limit(30)
                ->get(['id', 'name', 'description', 'learning_payload']);

            if ($materials->isNotEmpty()) {
                $results = [];
                $currentChars = 0;
                foreach ($materials as $mat) {
                    $body = $mat->learning_payload['body'] ?? $mat->description ?? '';
                    $clean = trim(strip_tags((string) $body));
                    $summary = Str::limit($clean, 120, '...');
                    $entry = "- {$mat->name}: {$summary}";

                    if ($currentChars + mb_strlen($entry) > $maxTotalChars) {
                        break;
                    }

                    $results[] = $entry;
                    $currentChars += mb_strlen($entry) + 1;
                }

                if (! empty($results)) {
                    return implode("\n", $results);
                }
            }
        }

        return '(Tidak ada materi kuliah terkait)';
    }

    protected function sanitizeStudentInput(array $input): array
    {
        $rawCode = (string) ($input['code'] ?? '');
        $rawConsole = (string) ($input['console_output'] ?? '');
        $rawQuestion = (string) ($input['question'] ?? '');
        $rawSelectedLine = $input['selected_line'] ?? null;

        // Strip delimiters & prompt injection delimiter impersonation tokens
        $cleanCode = $this->stripInjectionTokens($rawCode);
        $cleanConsole = $this->stripInjectionTokens($rawConsole);
        $cleanQuestion = $this->stripInjectionTokens($rawQuestion);

        // Code: maks 8.000 karakter
        $code = mb_substr($cleanCode, 0, 8000);

        // Console output: maks 2.000 karakter, prioritaskan bagian akhir / traceback
        $console = mb_strlen($cleanConsole) > 2000
            ? mb_substr($cleanConsole, -2000)
            : $cleanConsole;

        // Selected line: baris yang dipilih
        $selectedLine = (is_numeric($rawSelectedLine) && (int) $rawSelectedLine > 0)
            ? (string) (int) $rawSelectedLine
            : '-';

        return [
            'code' => $code,
            'console_output' => $console,
            'selected_line' => $selectedLine,
            'question' => $cleanQuestion,
        ];
    }

    public function stripInjectionTokens(string $text): string
    {
        // Hapus token format <data_{nonce}>, </data_{nonce}>, <riwayat_{nonce}>, <balasan_{nonce}>
        $cleaned = preg_replace('/<\/?(?:data|riwayat|balasan)_[a-zA-Z0-9_\-]+>/i', '', $text);

        // Hapus juga penanda token acak telanjang data_[hex] / riwayat_[hex]
        return preg_replace('/(?:data|riwayat|balasan)_[a-f0-9]{4,32}/i', '', (string) $cleaned);
    }
}
