<?php

namespace App\Services\Ai;

class PromptTemplateRenderer
{
    protected ?string $systemTemplate = null;

    protected ?string $userTemplate = null;

    protected ?array $hintLevels = null;

    protected ?string $reviewerTemplate = null;

    public function __construct(
        protected ?string $templateDir = null
    ) {
        $this->templateDir = $templateDir ?: resource_path('ai');
    }

    /**
     * Generate an 8-character cryptographic hex nonce.
     */
    public function generateNonce(): string
    {
        return bin2hex(random_bytes(4));
    }

    /**
     * Resolve the hint level (1, 2, or 3) based on the number of turns.
     * Turn 1-3: level 1, Turn 4-7: level 2, Turn 8+: level 3.
     */
    public function resolveHintLevel(int $turns): int
    {
        if ($turns <= 3) {
            return 1;
        }

        if ($turns <= 7) {
            return 2;
        }

        return 3;
    }

    /**
     * Load and parse hint levels instructions from hint_levels.txt.
     *
     * @return array<int, string>
     */
    public function loadHintLevels(): array
    {
        if ($this->hintLevels !== null) {
            return $this->hintLevels;
        }

        $path = $this->templateDir.'/hint_levels.txt';
        $content = file_exists($path) ? file_get_contents($path) : '';

        $levels = [
            1 => 'Fokus pada konsep. Jelaskan ide dasarnya dan ajukan pertanyaan pemandu agar mahasiswa memikirkannya sendiri. Jangan menunjuk bagian kode tertentu.',
            2 => 'Arahkan mahasiswa ke tahap atau bagian yang bermasalah dan jelaskan arti error yang muncul. Tetap berupa pertanyaan pemandu dan penjelasan konsep, bukan perbaikan.',
            3 => 'Tunjuk fungsi atau tahap yang perlu diperiksa ulang dan beri analogi non-kode agar mahasiswa bisa memperbaikinya sendiri. Tetap dilarang menulis kode atau baris perbaikan.',
        ];

        if (preg_match('/###\s*LEVEL\s*1\s*\n(.*?)(?=###\s*LEVEL\s*2|$)/is', $content, $m1)) {
            $levels[1] = trim($m1[1]);
        }
        if (preg_match('/###\s*LEVEL\s*2\s*\n(.*?)(?=###\s*LEVEL\s*3|$)/is', $content, $m2)) {
            $levels[2] = trim($m2[1]);
        }
        if (preg_match('/###\s*LEVEL\s*3\s*\n(.*?)$/is', $content, $m3)) {
            $levels[3] = trim($m3[1]);
        }

        return $this->hintLevels = $levels;
    }

    /**
     * Format conversation history array into readable transcript.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function formatHistory(array $history): string
    {
        if (empty($history)) {
            return '(Belum ada percakapan sebelumnya)';
        }

        $lines = [];
        foreach ($history as $item) {
            $roleLabel = ($item['role'] ?? '') === 'assistant' ? 'AI' : 'Mahasiswa';
            $content = trim((string) ($item['content'] ?? ''));
            $lines[] = "{$roleLabel}: {$content}";
        }

        return implode("\n\n", $lines);
    }

    /**
     * Render the system and user prompts using strtr and random nonce.
     *
     * @param  array  $context  From ContextBuilder
     * @param  array  $history  Turn history from DB
     * @param  int  $turns  Thread turns count
     * @param  string|null  $nonceOverride  Optional nonce (useful for tests)
     * @return array{system: string, user: string, nonce: string, hint_level: int}
     */
    public function render(array $context, array $history, int $turns, ?string $nonceOverride = null): array
    {
        $nonce = $nonceOverride ?: $this->generateNonce();
        $hintLevel = $this->resolveHintLevel($turns);
        $hintInstruction = $this->loadHintLevels()[$hintLevel] ?? '';

        $systemTemplate = $this->getSystemTemplate();
        $userTemplate = $this->getUserTemplate();

        $system = strtr($systemTemplate, [
            '{{hint_level_instruction}}' => $hintInstruction,
            '{{assessment_type}}' => (string) ($context['assessment_type'] ?? 'tugas'),
            '{{title}}' => (string) ($context['title'] ?? ''),
            '{{description}}' => (string) ($context['description'] ?? ''),
            '{{coding_steps}}' => (string) ($context['coding_steps'] ?? ''),
            '{{linked_materials}}' => (string) ($context['linked_materials'] ?? ''),
            '{{nonce}}' => $nonce,
        ]);

        $user = strtr($userTemplate, [
            '{{nonce}}' => $nonce,
            '{{history}}' => $this->formatHistory($history),
            '{{code}}' => (string) ($context['code'] ?? ''),
            '{{console_output}}' => (string) ($context['console_output'] ?? ''),
            '{{selected_line}}' => (string) ($context['selected_line'] ?? '-'),
            '{{question}}' => (string) ($context['question'] ?? ''),
        ]);

        return [
            'system' => $system,
            'user' => $user,
            'nonce' => $nonce,
            'hint_level' => $hintLevel,
        ];
    }

    /**
     * Render reviewer prompt using resources/ai/reviewer.txt.
     */
    public function renderReviewer(string $title, string $codingStepsShort, string $candidateReply, ?string $nonce = null): string
    {
        $nonce = $nonce ?: $this->generateNonce();
        $template = $this->getReviewerTemplate();

        return strtr($template, [
            '{{title}}' => $title,
            '{{coding_steps_short}}' => $codingStepsShort,
            '{{candidate_reply}}' => $candidateReply,
            '{{nonce}}' => $nonce,
        ]);
    }

    protected function getSystemTemplate(): string
    {
        if ($this->systemTemplate !== null) {
            return $this->systemTemplate;
        }

        $path = $this->templateDir.'/tutor_system.txt';

        return $this->systemTemplate = file_exists($path) ? file_get_contents($path) : '';
    }

    protected function getUserTemplate(): string
    {
        if ($this->userTemplate !== null) {
            return $this->userTemplate;
        }

        $path = $this->templateDir.'/tutor_user.txt';

        return $this->userTemplate = file_exists($path) ? file_get_contents($path) : '';
    }

    protected function getReviewerTemplate(): string
    {
        if ($this->reviewerTemplate !== null) {
            return $this->reviewerTemplate;
        }

        $path = $this->templateDir.'/reviewer.txt';

        return $this->reviewerTemplate = file_exists($path) ? file_get_contents($path) : '';
    }
}
