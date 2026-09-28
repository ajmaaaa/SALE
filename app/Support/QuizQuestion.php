<?php

namespace App\Support;

class QuizQuestion
{
    public static function canonicalizeQuestions(array $questions): array
    {
        return array_values(array_map(
            fn (array $question, int $index) => self::canonicalizeQuestion($question, $index),
            $questions,
            array_keys($questions)
        ));
    }

    public static function canonicalizeQuestion(array $question, int $index = 0): array
    {
        $questionId = (string) ($question['id'] ?? self::stableId('q', (string) $index.'|'.($question['prompt'] ?? '')));
        $question['id'] = $questionId;
        $type = $question['type'] ?? 'pilihan';

        if ($type === 'benar_salah') {
            $question['option_items'] = [
                ['id' => $questionId.'_true', 'text' => 'Benar'],
                ['id' => $questionId.'_false', 'text' => 'Salah'],
            ];
            $legacyKey = $question['boolean_answer'] ?? $question['correct_answer'] ?? null;
            $question['answer_key'] = [
                'option_ids' => self::resolveOptionIds($question, $legacyKey),
                'matches' => [],
            ];
        } elseif (in_array($type, ['pilihan', 'kompleks'], true)) {
            $question['option_items'] = self::optionItems($question, $questionId);
            $legacyKey = $question['correct_answers'] ?? $question['correct_answer'] ?? null;
            $question['answer_key'] = [
                'option_ids' => self::resolveOptionIds($question, $legacyKey),
                'matches' => [],
            ];
        } elseif ($type === 'mencocokkan') {
            $matchingItems = self::matchingItems($question, $questionId);
            $question['matching_items'] = $matchingItems;
            $question['option_items'] = array_values(array_map(
                fn (array $item) => ['id' => $item['option_id'], 'text' => $item['answer']],
                $matchingItems
            ));
            $question['answer_key'] = [
                'option_ids' => [],
                'matches' => array_column($matchingItems, 'option_id', 'id'),
            ];
        } else {
            $question['answer_key'] = ['option_ids' => [], 'matches' => []];
        }

        unset($question['correct_answer'], $question['correct_answers'], $question['boolean_answer']);

        return $question;
    }

    public static function normalizeAnswers(array $questions, array $answers, ?array $displayOrder = null): array
    {
        $questionsById = [];
        foreach ($questions as $index => $question) {
            $questionsById[(string) $question['id']] = $question;
        }

        $normalized = [];
        foreach ($answers as $key => $answer) {
            $hasExplicitId = isset($answer['question_id']);
            $questionId = $hasExplicitId ? (string) $answer['question_id'] : (string) $key;

            // When the answer does NOT carry an explicit question_id and the key is a
            // non-negative integer, always resolve by position to prevent collisions where
            // the integer key matches an existing question ID (e.g. key=1 maps to question
            // ID "1" even though positional intent was question at index 1 → possibly ID "2").
            if (! $hasExplicitId && is_numeric($key) && (int) $key >= 0) {
                $originalIndex = $displayOrder[(int) $key] ?? (int) $key;
                $questionId = (string) ($questions[$originalIndex]['id'] ?? $key);
            }

            if (! isset($questionsById[$questionId])) {
                continue;
            }

            $question = $questionsById[$questionId];
            $optionItems = collect($question['option_items'] ?? []);
            $optionIds = array_values(array_filter((array) ($answer['option_ids'] ?? []), fn ($id) => $optionItems->contains('id', (string) $id)));
            if (empty($optionIds)) {
                $legacyChoices = (array) ($answer['choices'] ?? []);
                if (isset($answer['boolean_choice'])) {
                    $legacyChoices = [$answer['boolean_choice']];
                }
                $optionIds = self::idsForValues($optionItems->all(), $legacyChoices);
            }

            $matches = [];
            foreach ((array) ($answer['matches'] ?? []) as $pairId => $optionId) {
                if (isset(($question['answer_key']['matches'] ?? [])[(string) $pairId]) && $optionItems->contains('id', (string) $optionId)) {
                    $matches[(string) $pairId] = (string) $optionId;
                }
            }
            if (empty($matches) && ! empty($answer['matching'])) {
                foreach (array_values($question['matching_items'] ?? []) as $pairIndex => $pair) {
                    $legacyValue = $answer['matching'][$pairIndex] ?? null;
                    $optionId = $optionItems->firstWhere('text', $legacyValue)['id'] ?? null;
                    if ($optionId) {
                        $matches[(string) $pair['id']] = (string) $optionId;
                    }
                }
            }

            $normalized[$questionId] = [
                'question_id' => $questionId,
                'option_ids' => $optionIds,
                'matches' => $matches,
                'text' => $answer['text'] ?? null,
            ];
        }

        return $normalized;
    }

    public static function evaluate(array $question, array $answer): ?float
    {
        $question = self::canonicalizeQuestion($question);
        $type = $question['type'] ?? 'pilihan';
        $points = (float) ($question['points'] ?? 100);

        if ($type === 'pilihan' || $type === 'benar_salah') {
            $chosen = array_values(array_map('strval', $answer['option_ids'] ?? []));
            if (empty($chosen)) {
                return null;
            }
            $correct = array_values(array_map('strval', $question['answer_key']['option_ids'] ?? []));
            sort($chosen);
            sort($correct);

            return $chosen === $correct ? $points : 0.0;
        }

        if ($type === 'kompleks') {
            $chosen = array_values(array_map('strval', $answer['option_ids'] ?? []));
            if (empty($chosen)) {
                return null;
            }
            $correct = array_values(array_map('strval', $question['answer_key']['option_ids'] ?? []));
            sort($chosen);
            sort($correct);

            if ($chosen === $correct) {
                return $points;
            }

            $scoreMode = $question['score_mode'] ?? 'parsial';
            if ($scoreMode === 'semua_atau_nol') {
                return 0.0;
            }

            $numCorrect = count($correct);
            if ($numCorrect === 0) {
                return 0.0;
            }

            $correctChosen = count(array_intersect($chosen, $correct));
            $wrongChosen = count(array_diff($chosen, $correct));

            $netCorrect = max(0, $correctChosen - $wrongChosen);
            $fraction = $netCorrect / $numCorrect;

            return round($fraction * $points, 2);
        }

        if ($type === 'mencocokkan') {
            $chosen = $answer['matches'] ?? [];
            if (empty($chosen)) {
                return null;
            }
            $correct = $question['answer_key']['matches'] ?? [];
            $correctCount = 0;
            foreach ($correct as $pairId => $optionId) {
                if (($chosen[$pairId] ?? null) === $optionId) {
                    $correctCount++;
                }
            }

            return empty($correct) ? 0.0 : round(($correctCount / count($correct)) * $points, 2);
        }

        return null;
    }

    private static function optionItems(array $question, string $questionId): array
    {
        if (! empty($question['option_items'])) {
            return array_values(array_map(fn (array $option, int $index) => [
                'id' => (string) ($option['id'] ?? self::stableId('o', $questionId.'|'.$index)),
                'text' => trim((string) ($option['text'] ?? '')),
            ], $question['option_items'], array_keys($question['option_items'])));
        }

        $values = array_values(array_filter(
            array_map('trim', explode("\n", (string) ($question['options'] ?? ''))),
            fn ($value) => $value !== ''
        ));

        return array_map(fn (string $text, int $index) => [
            'id' => self::stableId('o', $questionId.'|'.$index),
            'text' => $text,
        ], $values, array_keys($values));
    }

    private static function matchingItems(array $question, string $questionId): array
    {
        if (! empty($question['matching_items'])) {
            return array_values($question['matching_items']);
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", (string) ($question['options'] ?? '')))));
        $items = [];
        foreach ($lines as $index => $line) {
            $delimiterPosition = strrpos($line, ' = ');
            if ($delimiterPosition !== false) {
                $prompt = trim(substr($line, 0, $delimiterPosition));
                $answer = trim(substr($line, $delimiterPosition + 3));
            } else {
                [$prompt, $answer] = array_pad(array_map('trim', explode('=', $line, 2)), 2, '');
            }
            $items[] = [
                'id' => self::stableId('m', $questionId.'|'.$index),
                'prompt' => $prompt,
                'option_id' => self::stableId('o', $questionId.'|match|'.$index),
                'answer' => $answer,
            ];
        }

        return $items;
    }

    private static function resolveOptionIds(array $question, mixed $legacyKey): array
    {
        $canonical = $question['answer_key']['option_ids'] ?? null;
        if (is_array($canonical)) {
            return array_values(array_map('strval', $canonical));
        }

        $keys = is_array($legacyKey) ? $legacyKey : preg_split('/\s*,\s*/', trim((string) $legacyKey), -1, PREG_SPLIT_NO_EMPTY);
        $options = $question['option_items'] ?? [];
        $resolved = [];
        foreach ($keys as $key) {
            $key = trim((string) $key);
            if (preg_match('/^[A-Z]$/i', $key)) {
                $index = ord(strtoupper($key)) - ord('A');
                if (isset($options[$index])) {
                    $resolved[] = (string) $options[$index]['id'];
                }

                continue;
            }
            foreach ($options as $option) {
                if ((string) $option['id'] === $key || (string) $option['text'] === $key) {
                    $resolved[] = (string) $option['id'];
                    break;
                }
            }
        }

        return array_values(array_unique($resolved));
    }

    private static function idsForValues(array $options, array $values): array
    {
        $ids = [];
        foreach ($values as $value) {
            if (preg_match('/^[A-Z]$/i', (string) $value)) {
                $index = ord(strtoupper((string) $value)) - ord('A');
                if (isset($options[$index])) {
                    $ids[] = (string) $options[$index]['id'];

                    continue;
                }
            }
            foreach ($options as $option) {
                if ((string) $option['id'] === (string) $value || (string) $option['text'] === (string) $value) {
                    $ids[] = (string) $option['id'];
                    break;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    private static function stableId(string $prefix, string $seed): string
    {
        return $prefix.'_'.substr(hash('sha256', $seed), 0, 16);
    }
}
