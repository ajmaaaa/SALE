<?php

namespace App\Services\Ai;

use App\Models\AiMessage;

class OutputGuard
{
    /**
     * Common Indonesian words for language check.
     */
    protected const INDONESIAN_MARKERS = [
        'dan', 'yang', 'di', 'ke', 'dari', 'ini', 'itu', 'kamu', 'anda', 'pada',
        'untuk', 'bisa', 'adalah', 'coba', 'apakah', 'dengan', 'akan', 'kode',
        'fungsi', 'nilai', 'tahap', 'logika', 'kesalahan', 'variabel', 'rekursi',
        'periksa', 'mengapa', 'kenapa', 'bagaimana', 'baris', 'struktur', 'proses',
    ];

    /**
     * Perform deterministic safety and leakage checks on the candidate reply.
     *
     * @param  string  $reply  The candidate reply text from the AI model
     * @param  string|null  $referenceSolution  Optional authoritative reference solution code
     */
    public function check(string $reply, ?string $referenceSolution = null): OutputGuardResult
    {
        $trimmed = trim($reply);

        // 1. Empty check
        if ($trimmed === '') {
            return new OutputGuardResult(
                OutputGuardResult::STATUS_REJECT,
                AiMessage::VERDICT_BLOCKED_OUTPUT,
                'empty_reply'
            );
        }

        // 2. Word count limit (target < 200 words, hard threshold max_reply_words)
        $words = preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $maxWords = (int) config('ai.max_reply_words', 250);
        if (count($words) > $maxWords) {
            return new OutputGuardResult(
                OutputGuardResult::STATUS_REJECT,
                AiMessage::VERDICT_BLOCKED_OUTPUT,
                'word_count_exceeded'
            );
        }

        // 3. Fenced code block check (``` or ~~~)
        if (preg_match('/(```|~~~)/', $trimmed)) {
            return new OutputGuardResult(
                OutputGuardResult::STATUS_REJECT,
                AiMessage::VERDICT_BLOCKED_OUTPUT,
                'fenced_code_block'
            );
        }

        // 4. Inline code check (<= 40 characters, single line only)
        if (preg_match_all('/`([^`]+)`/', $trimmed, $matches)) {
            foreach ($matches[1] as $inlineCode) {
                if (str_contains($inlineCode, "\n") || str_contains($inlineCode, "\r")) {
                    return new OutputGuardResult(
                        OutputGuardResult::STATUS_REJECT,
                        AiMessage::VERDICT_BLOCKED_OUTPUT,
                        'inline_code_multiline'
                    );
                }
                if (mb_strlen($inlineCode) > 40) {
                    return new OutputGuardResult(
                        OutputGuardResult::STATUS_REJECT,
                        AiMessage::VERDICT_BLOCKED_OUTPUT,
                        'inline_code_too_long'
                    );
                }
            }
        }

        // 5. N-gram overlap with reference solution (n = 5 tokens)
        if (! empty($referenceSolution) && is_string($referenceSolution)) {
            $refTokens = $this->tokenize($referenceSolution);
            $replyTokens = $this->tokenize($trimmed);

            if (count($refTokens) >= 5 && count($replyTokens) >= 5) {
                $refNgrams = $this->extractNgrams($refTokens, 5);
                $replyNgrams = $this->extractNgrams($replyTokens, 5);

                $matchCount = 0;
                foreach ($replyNgrams as $ngram => $_) {
                    if (isset($refNgrams[$ngram])) {
                        $matchCount++;
                    }
                }

                $overlapRatio = $matchCount / count($replyNgrams);
                $ngramThreshold = (float) config('ai.ngram_threshold', 0.4);
                if ($overlapRatio >= $ngramThreshold) {
                    return new OutputGuardResult(
                        OutputGuardResult::STATUS_REJECT,
                        AiMessage::VERDICT_BLOCKED_OUTPUT,
                        'ngram_overlap'
                    );
                }
            }
        }

        // 6. Code-like line ratio check (exceeding threshold -> doubtful, triggers Reviewer)
        $lines = preg_split('/\r\n|\r|\n/', $trimmed) ?: [];
        $nonEmptyLines = array_values(array_filter($lines, fn ($l) => trim($l) !== ''));

        if (! empty($nonEmptyLines)) {
            $codeLineCount = 0;
            foreach ($nonEmptyLines as $rawLine) {
                if ($this->isCodeLikeLine($rawLine)) {
                    $codeLineCount++;
                }
            }

            $ratio = $codeLineCount / count($nonEmptyLines);
            $codeRatioThreshold = (float) config('ai.code_ratio_threshold', 0.25);
            if ($ratio >= $codeRatioThreshold) {
                return new OutputGuardResult(
                    OutputGuardResult::STATUS_DOUBTFUL,
                    AiMessage::VERDICT_OK,
                    'code_ratio_exceeded'
                );
            }
        }

        // 7. Indonesian language sanity check
        if (count($words) >= 30) {
            $lowered = mb_strtolower($trimmed);
            $markerCount = 0;
            foreach (self::INDONESIAN_MARKERS as $marker) {
                if (preg_match('/\b'.preg_quote($marker, '/').'\b/u', $lowered)) {
                    $markerCount++;
                    if ($markerCount >= 2) {
                        break;
                    }
                }
            }
            if ($markerCount < 2) {
                return new OutputGuardResult(
                    OutputGuardResult::STATUS_DOUBTFUL,
                    AiMessage::VERDICT_OK,
                    'not_indonesian'
                );
            }
        }

        return new OutputGuardResult(
            OutputGuardResult::STATUS_SAFE,
            AiMessage::VERDICT_OK,
            'passed'
        );
    }

    /**
     * Check if a line resembles code.
     */
    protected function isCodeLikeLine(string $rawLine): bool
    {
        // 4+ spaces or tab indentation
        if (preg_match('/^(\s{4,}|\t)/', $rawLine)) {
            return true;
        }

        $trimmed = trim($rawLine);

        // Ends with semicolon, opening brace, or closing brace
        if (preg_match('/[;{}]\s*$/', $trimmed)) {
            return true;
        }

        // Common programming keywords/patterns at the beginning of a line
        if (preg_match('/^(def\s+|class\s+|import\s+|from\s+\w+\s+import|return(\s+|$)|if\s+.*:\s*$|elif\s+.*:\s*$|else\s*:\s*$|while\s+.*:\s*$|for\s+\w+\s+in\s+.*:\s*$|try\s*:\s*$|except(\s+.*)?:\s*$)/', $trimmed)) {
            return true;
        }

        // Simple assignment like x = ... or self.root = ...
        if (preg_match('/^[a-zA-Z_][\w\.]*\s*=\s*[^=]/', $trimmed)) {
            return true;
        }

        return false;
    }

    /**
     * Tokenize text into words/tokens for n-gram comparison.
     *
     * @return array<int, string>
     */
    protected function tokenize(string $text): array
    {
        $lowered = mb_strtolower($text);
        // Replace non-alphanumeric characters with space
        $clean = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $lowered) ?? '';

        return preg_split('/\s+/u', trim($clean), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Extract n-grams as flipped associative array for O(1) lookup.
     *
     * @param  array<int, string>  $tokens
     * @return array<string, int>
     */
    protected function extractNgrams(array $tokens, int $n): array
    {
        $ngrams = [];
        $count = count($tokens);
        for ($i = 0; $i <= $count - $n; $i++) {
            $slice = array_slice($tokens, $i, $n);
            $ngrams[implode(' ', $slice)] = 1;
        }

        return $ngrams;
    }
}
