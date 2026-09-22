<?php

namespace App\Support;

class AcademicPreview
{
    public static function config(int $course): array
    {
        LearningPreview::course($course);

        $config = session("academic.config.$course", [
            'cpl'=>[['code'=>'CPL-01','description'=>'Mampu menganalisis masalah dan menyusun solusi secara sistematis.'],['code'=>'CPL-02','description'=>'Mampu menerapkan pengetahuan komputasi dalam praktik.']],
            'cpmk'=>[['code'=>'CPMK-01','cpl'=>'CPL-01','description'=>'Menganalisis konsep dasar dan memilih pendekatan penyelesaian.','threshold'=>50],['code'=>'CPMK-02','cpl'=>'CPL-02','description'=>'Menerapkan konsep melalui tugas dan praktikum.','threshold'=>80]],
            'components'=>[['code'=>'tugas','name'=>'Tugas','weight'=>25],['code'=>'kuis','name'=>'Kuis','weight'=>10],['code'=>'uts','name'=>'UTS','weight'=>20],['code'=>'uas','name'=>'UAS','weight'=>25],['code'=>'proyek','name'=>'Proyek','weight'=>15],['code'=>'partisipasi','name'=>'Kehadiran & partisipasi','weight'=>5]],
        ]);
        foreach ($config['cpmk'] as &$cpmk) $cpmk['threshold'] = $cpmk['threshold'] ?? 65;

        return $config;
    }

    public static function breakdown(int $course, int $student = 1): array
    {
        $cpmk = [];
        foreach (self::config($course)['cpmk'] as $outcome) {
            $cpmk[$outcome['code']] = [
                'code'=>$outcome['code'], 'description'=>$outcome['description'],
                'threshold'=>$outcome['threshold'], 'earned'=>0, 'max'=>0,
                'score'=>null, 'complete'=>true, 'passed'=>null,
            ];
        }

        $items = [];
        foreach (LearningPreview::items() as $id=>$item) {
            if ($item['course'] !== $course || !in_array($item['type'], ['tugas', 'coding', 'kuis'], true)) continue;
            if (! empty($item['questions'])) {
                $questions = $item['questions'];
            } elseif (($item['scoring_mode'] ?? null) === 'manual_cpmk' && ! empty($item['manual_cpmk_weights'])) {
                $questions = [];
                foreach ($item['manual_cpmk_weights'] as $code => $weight) {
                    $questions[] = ['prompt'=>'Kriteria '.$code, 'cpmk'=>$code, 'points'=>100, 'manual_weight'=>$weight];
                }
            } else {
                $questions = [[
                    'prompt'=>$item['body'] ?? $item['title'],
                    'cpmk'=>$item['cpmk'] ?? null, 'points'=>$item['points'] ?? 100,
                ]];
            }
            $max = array_sum(array_column($questions, 'points'));
            $grades = session("academic.item_grades.$id.$student.points", []);
            $scoringMode = $item['scoring_mode'] ?? 'legacy_points';
            $groupCounts = array_count_values(array_filter(array_column($questions, 'cpmk')));
            $assessment = [
                'id'=>$id, 'title'=>$item['title'],
                'component'=>$item['component'] ?? ($item['type'] === 'kuis' ? 'kuis' : 'tugas'),
                'questions'=>[], 'score'=>null, 'complete'=>$max > 0,
                'scoring_mode'=>$scoringMode,
            ];
            $earned = 0;
            $normalizedScore = 0;
            foreach ($questions as $index=>$question) {
                $code = $question['cpmk'] ?? null;
                // Seeded quizzes predate the canonical CPMK-01 code format.
                if (!isset($cpmk[$code]) && preg_match('/^CPMK (\d+)$/', $code ?? '', $match)) {
                    $code = sprintf('CPMK-%02d', (int) $match[1]);
                }
                $points = $grades[$index] ?? null;
                $points = is_numeric($points) ? (float) $points : null;
                $questionMax = $question['points'] ?? 100;
                $weight = $max > 0 ? $questionMax / $max * 100 : 0;
                $withinCpmkWeight = $weight;
                $cpmkWeight = $weight;
                if ($scoringMode === 'automatic_cpmk') {
                    $groupCount = max(1, (int) ($groupCounts[$question['cpmk'] ?? ''] ?? 1));
                    $weight = 100 / max(1, count($questions));
                    $withinCpmkWeight = 100 / $groupCount;
                    $cpmkWeight = $groupCount / max(1, count($questions)) * 100;
                } elseif ($scoringMode === 'manual_cpmk') {
                    $weight = (float) ($question['manual_weight'] ?? 0);
                    $withinCpmkWeight = 100;
                    $cpmkWeight = $weight;
                }
                $persenSoal = ($points !== null && $questionMax > 0) ? ($points / $questionMax) : null;
                $nilaiSoal = $persenSoal !== null ? ($persenSoal * $withinCpmkWeight) : null;

                $assessment['questions'][] = [
                    'prompt'=>$question['prompt'], 'type'=>$question['type'] ?? 'pilihan',
                    'cpmk'=>$code, 'points'=>$questionMax,
                    'earned'=>$points, 'weight'=>round($weight, 4),
                    'within_cpmk_weight'=>round($withinCpmkWeight, 4),
                    'cpmk_weight'=>round($cpmkWeight, 4),
                    'porsi'=>round($withinCpmkWeight, 2),
                    'persen_soal'=>$persenSoal !== null ? round($persenSoal * 100, 2) : null,
                    'nilai_soal'=>$nilaiSoal !== null ? round($nilaiSoal, 2) : null,
                ];
                $earned += $points ?? 0;
                if ($points !== null && in_array($scoringMode, ['automatic_cpmk', 'manual_cpmk'], true)) {
                    $normalizedScore += ($questionMax > 0 ? $points / $questionMax : 0) * $weight;
                }
                if ($points === null) $assessment['complete'] = false;
                if (isset($cpmk[$code])) {
                    if (in_array($scoringMode, ['automatic_cpmk', 'manual_cpmk'], true)) {
                        $cpmk[$code]['max'] += $weight;
                        $cpmk[$code]['earned'] += $points === null || $questionMax <= 0 ? 0 : $points / $questionMax * $weight;
                    } else {
                        $cpmk[$code]['max'] += $questionMax;
                        $cpmk[$code]['earned'] += $points ?? 0;
                    }
                    if ($points === null) $cpmk[$code]['complete'] = false;
                }
            }
            if ($assessment['complete']) {
                $assessment['score'] = in_array($scoringMode, ['automatic_cpmk', 'manual_cpmk'], true)
                    ? round($normalizedScore, 2)
                    : round($earned / $max * 100, 2);
            }
            // Group scores by CPMK for this assessment
            $cpmkGroups = [];
            foreach ($assessment['questions'] as $q) {
                $cCode = $q['cpmk'];
                if (!$cCode) continue;
                if (!isset($cpmkGroups[$cCode])) {
                    $cpmkGroups[$cCode] = ['total_nilai' => 0, 'count' => 0, 'weight' => $q['cpmk_weight']];
                }
                $cpmkGroups[$cCode]['count']++;
                if ($q['nilai_soal'] !== null) {
                    $cpmkGroups[$cCode]['total_nilai'] += $q['nilai_soal'];
                }
            }
            $assessment['cpmk_scores'] = array_map(fn($g) => round($g['total_nilai'], 2), $cpmkGroups);
            $assessment['cpmk_weights'] = array_map(fn($g) => round($g['weight'], 2), $cpmkGroups);
            $items[] = $assessment;
        }

        $complete = count($items) > 0 && count($cpmk) > 0;
        foreach ($items as $item) $complete = $complete && $item['complete'];
        $failed = false;
        foreach ($cpmk as &$outcome) {
            $outcome['complete'] = $outcome['complete'] && $outcome['max'] > 0;
            if ($outcome['complete']) {
                $score = $outcome['earned'] / $outcome['max'] * 100;
                $outcome['score'] = round($score, 2);
                $outcome['passed'] = $score >= $outcome['threshold'];
            }
            $complete = $complete && $outcome['complete'];
            $failed = $failed || $outcome['passed'] === false;
        }

        return ['items'=>$items, 'cpmk'=>array_values($cpmk), 'complete'=>$complete, 'passed'=>$failed ? false : ($complete ? true : null)];
    }

    public static function scores(int $course, int $student = 1): array
    {
        $scores = session("academic.scores.$course.$student", $student === 1 ? ['tugas'=>86,'kuis'=>92,'uts'=>88,'uas'=>null,'proyek'=>null,'partisipasi'=>90] : []);
        // Item grades replace the manual component score with their normalized average.
        $graded = [];
        foreach (self::breakdown($course, $student)['items'] as $item) {
            if (!$item['complete']) continue;
            $graded[$item['component']][] = $item['score'];
        }
        foreach ($graded as $component=>$values) $scores[$component] = round(array_sum($values)/count($values),2);

        return $scores;
    }

    public static function result(int $course, int $student = 1): array
    {
        $scores = self::scores($course,$student);
        $total = 0;
        $coverage = 0;
        foreach (self::config($course)['components'] as $component) {
            $score = $scores[$component['code']] ?? null;
            if ($score !== null && $score !== '') {
                $total += $score * $component['weight'] / 100;
                $coverage += $component['weight'];
            }
        }
        $itemsComplete = !in_array(false, array_column(self::breakdown($course, $student)['items'], 'complete'), true);

        return ['scores'=>$scores,'total'=>$coverage > 0 ? round($total,2) : null,'average'=>$coverage > 0 ? round($total/$coverage*100,2) : null,'coverage'=>$coverage,'complete'=>abs($coverage-100)<0.001 && $itemsComplete];
    }

    public static function assessmentEvaluation(int $course, int $item): array
    {
        $itemData = LearningPreview::items()[$item] ?? null;
        abort_unless($itemData, 404);

        $courseData = LearningPreview::course($course);
        $academic = self::config($course);

        $questions = $itemData['questions'] ?? [];
        if (empty($questions)) {
            $questions = [[
                'id' => 1,
                'prompt' => $itemData['body'] ?? $itemData['title'],
                'type' => $itemData['question_type'] ?? 'uraian',
                'cpmk' => (!empty($itemData['cpmk']) && strlen($itemData['cpmk']) <= 15) ? $itemData['cpmk'] : ($academic['cpmk'][0]['code'] ?? 'CPMK-01'),
                'points' => $itemData['points'] ?? 100,
            ]];
        }

        $groupCounts = array_count_values(array_filter(array_column($questions, 'cpmk')));
        $totalQuestions = max(1, count($questions));

        $formattedQuestions = [];
        foreach ($questions as $qIdx => $q) {
            $cCode = (!empty($q['cpmk']) && strlen($q['cpmk']) <= 15) ? $q['cpmk'] : ($academic['cpmk'][0]['code'] ?? 'CPMK-01');
            $groupCount = max(1, (int) ($groupCounts[$cCode] ?? 1));
            $porsiSoal = 100 / $groupCount;
            $bobotCpmk = ($groupCount / $totalQuestions) * 100;
            $isEssay = in_array($q['type'] ?? 'pilihan', ['uraian', 'coding'], true);

            $formattedQuestions[$qIdx] = array_merge($q, [
                'index' => $qIdx,
                'cpmk' => $cCode,
                'points' => (float) ($q['points'] ?? 100),
                'porsi_soal' => round($porsiSoal, 2),
                'porsi_soal_raw' => $porsiSoal,
                'bobot_cpmk' => round($bobotCpmk, 2),
                'bobot_cpmk_raw' => $bobotCpmk,
                'is_essay' => $isEssay,
            ]);
        }

        self::ensureSampleAssessmentData($course, $item, $formattedQuestions);

        $baseStudents = array_values(array_filter(AdminPreview::users(), fn ($u) => ($u['role'] ?? '') === 'mahasiswa'));
        $extraStudents = [
            ['id' => 4, 'name' => 'Dewi Anggraini', 'email' => 'dewi@example.test', 'number' => '231011401235', 'role' => 'mahasiswa', 'status' => 'aktif', 'roles' => ['mahasiswa']],
            ['id' => 5, 'name' => 'Fajar Ramadhan', 'email' => 'fajar@example.test', 'number' => '231011401238', 'role' => 'mahasiswa', 'status' => 'aktif', 'roles' => ['mahasiswa']],
            ['id' => 6, 'name' => 'Rizky Pratama', 'email' => 'rizky@example.test', 'number' => '231011401239', 'role' => 'mahasiswa', 'status' => 'aktif', 'roles' => ['mahasiswa']],
            ['id' => 7, 'name' => 'Siti Nurhaliza', 'email' => 'siti@example.test', 'number' => '231011401240', 'role' => 'mahasiswa', 'status' => 'aktif', 'roles' => ['mahasiswa']],
        ];
        $students = $baseStudents;
        foreach ($extraStudents as $extra) {
            if (!collect($students)->contains('id', $extra['id'])) {
                $students[] = $extra;
            }
        }

        $pendingQueue = [];
        $completedResults = [];

        foreach ($students as $stu) {
            $stuId = $stu['id'];
            $submission = session("learning.submissions.{$item}.{$stuId}") ?? session("learning.submissions.{$item}");
            if ($submission && isset($submission['student_number']) && $submission['student_number'] !== $stu['number'] && !session()->has("learning.submissions.{$item}.{$stuId}")) {
                $submission = null;
            }

            $grades = session("academic.item_grades.{$item}.{$stuId}.points", []);

            $stuQuestions = [];
            $pendingEssaysCount = 0;
            $hasSubmitted = !empty($submission);

            foreach ($formattedQuestions as $qIdx => $q) {
                $ans = $submission['question_answers'][$qIdx] ?? [];
                $qPoints = (float) $q['points'];
                $porsi = $q['porsi_soal_raw'];

                if ($q['is_essay']) {
                    $rawScore = $grades[$qIdx] ?? null;
                    $scoreVal = is_numeric($rawScore) ? (float) $rawScore : null;
                    if ($hasSubmitted && $scoreVal === null) {
                        $pendingEssaysCount++;
                    }
                    $persen = ($scoreVal !== null && $qPoints > 0) ? ($scoreVal / $qPoints) : null;
                    $nilaiSoal = $persen !== null ? ($persen * $porsi) : null;

                    $stuQuestions[$qIdx] = [
                        'score' => $scoreVal,
                        'persen' => $persen !== null ? round($persen * 100, 2) : null,
                        'nilai_soal' => $nilaiSoal !== null ? round($nilaiSoal, 2) : null,
                        'nilai_soal_raw' => $nilaiSoal,
                        'status' => $scoreVal !== null ? 'DINILAI' : ($hasSubmitted ? 'PERLU_DINILAI' : 'BELUM'),
                        'student_answer' => $ans['text'] ?? ($submission['answer'] ?? ''),
                    ];
                } else {
                    $autoScore = isset($grades[$qIdx]) ? (float) $grades[$qIdx] : self::evaluateAutoQuestion($q, $ans);
                    $persen = $autoScore !== null ? ($autoScore / $qPoints) : ($hasSubmitted ? 0.0 : null);
                    $scoreVal = $persen !== null ? round($persen * $qPoints, 2) : null;
                    $nilaiSoalRaw = $persen !== null ? ($persen * $porsi) : null;
                    $nilaiSoal = $nilaiSoalRaw !== null ? round($nilaiSoalRaw, 2) : null;

                    $stuQuestions[$qIdx] = [
                        'score' => $scoreVal,
                        'persen' => $persen !== null ? round($persen * 100, 2) : null,
                        'nilai_soal' => $nilaiSoal,
                        'nilai_soal_raw' => $nilaiSoalRaw,
                        'status' => 'OTOMATIS',
                        'student_answer' => $ans,
                    ];
                }
            }

            $cpmkScores = [];
            foreach ($formattedQuestions as $qIdx => $q) {
                $cCode = $q['cpmk'];
                if (!isset($cpmkScores[$cCode])) {
                    $cpmkScores[$cCode] = [
                        'total_nilai' => 0.0,
                        'bobot_cpmk' => $q['bobot_cpmk_raw'],
                    ];
                }
                if (isset($stuQuestions[$qIdx]['nilai_soal_raw']) && $stuQuestions[$qIdx]['nilai_soal_raw'] !== null) {
                    $cpmkScores[$cCode]['total_nilai'] += $stuQuestions[$qIdx]['nilai_soal_raw'];
                }
            }

            $totalAsesmen = 0.0;
            foreach ($cpmkScores as $cCode => $cInfo) {
                $totalAsesmen += $cInfo['total_nilai'] * ($cInfo['bobot_cpmk'] / 100);
            }
            $totalAsesmen = round($totalAsesmen, 2);

            $statusKey = 'belum_dikerjakan';
            $statusLabel = 'Belum Dikerjakan';
            if ($hasSubmitted) {
                if ($pendingEssaysCount > 0) {
                    $statusKey = 'perlu_dinilai';
                    $statusLabel = 'Perlu Dinilai';
                } else {
                    $statusKey = 'selesai';
                    $statusLabel = 'Selesai';
                }
            }

            $studentEntry = [
                'student' => $stu,
                'has_submitted' => $hasSubmitted,
                'pending_essays_count' => $pendingEssaysCount,
                'status_key' => $statusKey,
                'status_label' => $statusLabel,
                'nilai_asesmen' => ($statusKey === 'selesai') ? $totalAsesmen : ($hasSubmitted ? $totalAsesmen : null),
                'nilai_display' => ($statusKey === 'selesai') ? number_format($totalAsesmen, 2, ',', '.') : '—',
                'cpmk_breakdown' => array_map(fn ($c) => [
                    'score' => round($c['total_nilai'], 2),
                    'weight' => round($c['bobot_cpmk'], 2),
                ], $cpmkScores),
                'questions_breakdown' => $stuQuestions,
            ];

            if ($statusKey === 'perlu_dinilai') {
                $pendingQueue[] = $studentEntry;
            }
            $completedResults[] = $studentEntry;
        }

        return [
            'course' => $courseData,
            'item' => $itemData,
            'questions' => $formattedQuestions,
            'students' => $students,
            'pending_queue' => $pendingQueue,
            'results' => $completedResults,
            'total_pending' => count($pendingQueue),
            'all_completed' => count($pendingQueue) === 0,
        ];
    }

    public static function evaluateAutoQuestion(array $question, array $answer): ?float
    {
        $type = $question['type'] ?? 'pilihan';
        $points = (float) ($question['points'] ?? 100);

        if ($type === 'pilihan') {
            $options = array_values(array_filter(array_map('trim', explode("\n", $question['options'] ?? '')), fn ($v) => $v !== ''));
            $correctOption = $options[0] ?? '';
            $chosen = $answer['choices'][0] ?? ($answer['choice'] ?? null);
            if ($chosen === null) return null;
            return ($chosen === $correctOption) ? $points : 0.0;
        }

        if ($type === 'kompleks') {
            $options = array_values(array_filter(array_map('trim', explode("\n", $question['options'] ?? '')), fn ($v) => $v !== ''));
            $correctKeys = array_slice($options, 0, max(1, (int) round(count($options) / 2)));
            $chosen = $answer['choices'] ?? [];
            if (empty($chosen)) return null;

            $benar = count(array_intersect($chosen, $correctKeys));
            $salah = count(array_diff($chosen, $correctKeys));
            $jumlahKunci = max(1, count($correctKeys));

            $persen = max(0.0, ($benar - $salah) / $jumlahKunci);
            return round($persen * $points, 2);
        }

        if ($type === 'benar_salah') {
            $chosen = $answer['boolean_choice'] ?? null;
            if ($chosen === null) return null;
            $correct = 'Benar';
            return ($chosen === $correct) ? $points : 0.0;
        }

        if ($type === 'mencocokkan') {
            $matching = $answer['matching'] ?? [];
            if (empty($matching)) return null;
            $correctCount = 0;
            $totalPairs = count($matching);
            foreach ($matching as $pIdx => $target) {
                if ($target !== null && $target !== '') {
                    $correctCount++;
                }
            }
            $persen = $totalPairs > 0 ? ($correctCount / $totalPairs) : 0.0;
            return round($persen * $points, 2);
        }

        return null;
    }

    public static function getEssayToGrade(int $course, int $item, int $studentId, ?int $questionIndex = null): array
    {
        $evaluation = self::assessmentEvaluation($course, $item);
        $questions = $evaluation['questions'];
        $essayQuestions = array_filter($questions, fn ($q) => $q['is_essay']);
        $essayIndexes = array_keys($essayQuestions);

        $student = collect($evaluation['students'])->firstWhere('id', $studentId);
        abort_unless($student, 404);

        $grades = session("academic.item_grades.{$item}.{$studentId}.points", []);

        if ($questionIndex === null || !isset($questions[$questionIndex]) || !$questions[$questionIndex]['is_essay']) {
            $targetIndex = null;
            foreach ($essayIndexes as $idx) {
                if (!isset($grades[$idx]) || $grades[$idx] === null) {
                    $targetIndex = $idx;
                    break;
                }
            }
            $questionIndex = $targetIndex ?? ($essayIndexes[0] ?? 0);
        }

        $currentQuestion = $questions[$questionIndex] ?? null;
        abort_unless($currentQuestion && $currentQuestion['is_essay'], 404);

        $currentPosition = array_search($questionIndex, $essayIndexes, true);
        $currentEssayNumber = $currentPosition !== false ? ($currentPosition + 1) : 1;
        $totalEssaysForStudent = count($essayIndexes);

        $nextQuestionIndex = null;
        $hasNextEssay = false;
        if ($currentPosition !== false && isset($essayIndexes[$currentPosition + 1])) {
            $nextQuestionIndex = $essayIndexes[$currentPosition + 1];
            $hasNextEssay = true;
        }

        $nextStudentId = null;
        foreach ($evaluation['pending_queue'] as $pending) {
            if ($pending['student']['id'] !== $studentId && $pending['pending_essays_count'] > 0) {
                $nextStudentId = $pending['student']['id'];
                break;
            }
        }

        $submission = session("learning.submissions.{$item}.{$studentId}") ?? session("learning.submissions.{$item}");
        $answerText = $submission['question_answers'][$questionIndex]['text']
            ?? ($submission['answer'] ?? '');

        $currentScore = $grades[$questionIndex] ?? null;

        return [
            'course' => $evaluation['course'],
            'item' => $evaluation['item'],
            'student' => $student,
            'question' => $currentQuestion,
            'question_index' => $questionIndex,
            'current_essay_number' => $currentEssayNumber,
            'total_essay_count' => $totalEssaysForStudent,
            'answer_text' => $answerText,
            'current_score' => $currentScore,
            'porsi_soal' => $currentQuestion['porsi_soal_raw'],
            'has_next_essay' => $hasNextEssay,
            'next_question_index' => $nextQuestionIndex,
            'is_last_essay' => ! $hasNextEssay,
            'next_student_id' => $nextStudentId,
        ];
    }

    private static function ensureSampleAssessmentData(int $course, int $item, array $questions): void
    {
        $seededKey = "academic.item_seeded.{$item}";
        if (session()->has($seededKey)) {
            return;
        }

        $essayIndices = array_keys(array_filter($questions, fn ($q) => $q['is_essay']));

        // Fajar Ramadhan (student 5, 231011401238) - exactly matching desain_penilaian_v4_1.md
        if (!session()->has("learning.submissions.{$item}.5")) {
            $answers = [];
            foreach ($questions as $idx => $q) {
                if ($q['is_essay']) {
                    $answers[$idx] = [
                        'text' => "Stack adalah struktur data yang menggunakan konsep LIFO (Last In First Out), di mana elemen yang terakhir masuk akan menjadi yang pertama keluar. Sedangkan Queue menggunakan konsep FIFO (First In First Out), di mana elemen yang pertama masuk akan menjadi yang pertama keluar.\n\nContoh penggunaan Stack adalah fitur Undo pada text editor atau penelusuran riwayat halaman web pada browser. Contoh penggunaan Queue adalah sistem antrean cetak printer atau pemrosesan permintaan task pada server antrian.",
                    ];
                } else {
                    $answers[$idx] = ['choices' => ['Benar']];
                }
            }
            session(["learning.submissions.{$item}.5" => [
                'student_number' => '231011401238',
                'question_answers' => $answers,
                'time' => now()->subHours(2)->format('d M Y, H:i'),
            ]]);
        }

        // Siti Nurhaliza (student 7, 231011401240) - 2 answers need grading
        if (!session()->has("learning.submissions.{$item}.7")) {
            $answers = [];
            foreach ($questions as $idx => $q) {
                if ($q['is_essay']) {
                    $answers[$idx] = [
                        'text' => "Perbedaan utamanya terletak pada cara penyimpanan dan pengaksesan data. Stack bekerja berdasarkan urutan LIFO, contohnya tumpukan pemanggilan fungsi (call stack) saat rekursi. Queue bekerja berdasarkan urutan FIFO, contohnya simulasi antrean kasir.",
                    ];
                } else {
                    $answers[$idx] = ['choices' => ['Benar']];
                }
            }
            session(["learning.submissions.{$item}.7" => [
                'student_number' => '231011401240',
                'question_answers' => $answers,
                'time' => now()->subHours(3)->format('d M Y, H:i'),
            ]]);
        }

        // Ahmad Maulana (student 1) - Finished (85,00)
        if (!session()->has("learning.submissions.{$item}.1")) {
            $answers = [];
            foreach ($questions as $idx => $q) {
                $answers[$idx] = ['text' => 'Analisis komparatif struktur data Stack dan Queue...', 'choices' => ['Benar']];
            }
            session(["learning.submissions.{$item}.1" => [
                'student_number' => '231011401234',
                'question_answers' => $answers,
                'time' => now()->subHours(5)->format('d M Y, H:i'),
            ]]);
            $seedGrades = [];
            foreach ($questions as $idx => $q) {
                $seedGrades[$idx] = round((float) $q['points'] * 0.85, 1);
            }
            session(["academic.item_grades.{$item}.1.points" => $seedGrades]);
        }

        // Dewi Anggraini (student 4) - Finished (78,00)
        if (!session()->has("learning.submissions.{$item}.4")) {
            $answers = [];
            foreach ($questions as $idx => $q) {
                $answers[$idx] = ['text' => 'Stack menggunakan prinsip LIFO, Queue menggunakan prinsip FIFO...', 'choices' => ['Benar']];
            }
            session(["learning.submissions.{$item}.4" => [
                'student_number' => '231011401235',
                'question_answers' => $answers,
                'time' => now()->subHours(6)->format('d M Y, H:i'),
            ]]);
            $seedGrades = [];
            foreach ($questions as $idx => $q) {
                $seedGrades[$idx] = round((float) $q['points'] * 0.78, 1);
            }
            session(["academic.item_grades.{$item}.4.points" => $seedGrades]);
        }

        // Rizky Pratama (student 6) - Finished (90,00)
        if (!session()->has("learning.submissions.{$item}.6")) {
            $answers = [];
            foreach ($questions as $idx => $q) {
                $answers[$idx] = ['text' => 'Stack adalah LIFO, Queue adalah FIFO dengan implementasi pointer head dan tail...', 'choices' => ['Benar']];
            }
            session(["learning.submissions.{$item}.6" => [
                'student_number' => '231011401239',
                'question_answers' => $answers,
                'time' => now()->subHours(7)->format('d M Y, H:i'),
            ]]);
            $seedGrades = [];
            foreach ($questions as $idx => $q) {
                $seedGrades[$idx] = round((float) $q['points'] * 0.90, 1);
            }
            session(["academic.item_grades.{$item}.6.points" => $seedGrades]);
        }

        session([$seededKey => true]);
    }
}
