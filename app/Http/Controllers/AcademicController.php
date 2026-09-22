<?php

namespace App\Http\Controllers;

use App\Support\AcademicPreview as Academic;
use App\Support\AdminPreview;
use App\Support\LearningPreview as Learning;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicController extends Controller
{
    private function authorizeOwnership(int $course, int $item = 0)
    {
        $courseData = Learning::course($course);
        abort_unless($courseData, 404);

        // Security Audit BOLA/IDOR Fix: In a real database scenario, we would check:
        // abort_unless($courseData->dosen_id === auth()->id(), 403, 'Akses ditolak.');

        if ($item !== 0) {
            $itemData = Learning::items()[$item] ?? null;
            abort_unless($itemData && $itemData['course'] === $course, 404, 'Item tidak ditemukan atau bukan milik course ini.');
        }
    }

    public function settings(int $course)
    {
        return view('dosen.academic-settings',['course'=>Learning::course($course),'config'=>Academic::config($course)]);
    }

    public function saveSettings(Request $request,int $course)
    {
        $this->authorizeOwnership($course);
        Learning::course($course);
        $data=$request->validate([
            'cpl'=>'required|array|min:1|max:30','cpl.*.code'=>'required|alpha_dash|max:30|distinct','cpl.*.description'=>'required|string|max:1000',
            'cpmk'=>'required|array|min:1|max:50','cpmk.*.code'=>'required|alpha_dash|max:30|distinct','cpmk.*.cpl'=>['required',Rule::in(array_column($request->input('cpl',[]),'code'))],'cpmk.*.description'=>'required|string|max:1000',
            'components'=>'required|array|min:1|max:20','components.*.code'=>'required|alpha_dash|max:30|distinct','components.*.name'=>'required|string|max:80','components.*.weight'=>'required|numeric|min:0|max:100',
            'cpmk.*.threshold'=>'sometimes|required|numeric|min:0|max:100',
        ]);
        if(abs(array_sum(array_column($data['components'],'weight'))-100)>0.001) return back()->withErrors(['components'=>'Total bobot harus tepat 100%.'])->withInput();
        foreach(Learning::items() as $item){
            if($item['course']!==$course) continue;
            foreach($item['questions'] ?? [] as $question){
                if(!in_array($question['cpmk'],array_column($data['cpmk'],'code'))) return back()->withErrors(['cpmk'=>'CPMK yang sudah digunakan pada soal tidak dapat dihapus.'])->withInput();
            }
            if(isset($item['component']) && !in_array($item['component'],array_column($data['components'],'code'))) return back()->withErrors(['components'=>'Komponen yang dipakai tugas tidak dapat dihapus.'])->withInput();
        }
        session(["academic.config.$course"=>$data]);
        return back()->with('notice','CPL, CPMK, dan bobot penilaian disimpan.');
    }

    public function gradebook(Request $request)
    {
        $course=$request->integer('course',1);
        $this->authorizeOwnership($course);
        $config=Academic::config($course);
        $component=$request->query('component', '');
        abort_unless(is_string($component) && ($component === '' || in_array($component, array_column($config['components'], 'code'), true)), 422);
        $assessments=array_values(array_filter(Academic::breakdown($course)['items'], fn($item)=>$item['component']===$component));
        $assessment=$request->integer('assessment');
        abort_unless($assessment===0 || in_array($assessment, array_column($assessments, 'id')), 422);
        return view('dosen.gradebook',['course'=>Learning::course($course),'config'=>$config,'students'=>array_filter(AdminPreview::users(),fn($u)=>$u['role']==='mahasiswa'),'componentFilter'=>$component,'assessments'=>$assessments,'assessmentFilter'=>$assessment]);
    }

    public function saveScores(Request $request, int $course)
    {
        $this->authorizeOwnership($course);
        $config = Academic::config($course);
        $data=$request->validate(['scores'=>'required|array','scores.*'=>'array','scores.*.*'=>'nullable|numeric|min:0|max:100']);
        foreach($data['scores'] as $student=>$scores){
            abort_unless((AdminPreview::users()[$student]['role'] ?? null)==='mahasiswa',422);
            abort_if(array_diff(array_keys($scores),array_column($config['components'],'code')),422);
        }
        foreach($data['scores'] as $student=>$scores) session(["academic.scores.$course.$student"=>$scores]);
        return back()->with('notice','Nilai komponen disimpan. Rekap mahasiswa sudah diperbarui.');
    }

    public function bulkScores(Request $request, int $course)
    {
        $this->authorizeOwnership($course);
        $config = Academic::config($course);
        $data = $request->validate([
            'raw_scores' => 'required|string|max:50000',
        ]);

        $users = AdminPreview::users();
        $studentsByNumber = [];
        foreach ($users as $u) {
            if ($u['role'] === 'mahasiswa') {
                $studentsByNumber[$u['number']] = $u['id'];
            }
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($data['raw_scores']));
        $components = array_column($config['components'], 'code');
        $updated = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $delimiter = str_contains($line, "\t") ? "\t" : (str_contains($line, ';') ? ';' : ',');
            $cols = array_map('trim', str_getcsv($line, $delimiter, '"', '\\'));

            if (count($cols) < 2) {
                continue;
            }

            $identifier = $cols[0];
            $studentId = $studentsByNumber[$identifier] ?? (isset($users[(int)$identifier]) && $users[(int)$identifier]['role'] === 'mahasiswa' ? (int)$identifier : null);

            if (!$studentId) {
                continue;
            }

            $currentScores = session("academic.scores.$course.$studentId", []);
            foreach ($components as $idx => $compCode) {
                if (isset($cols[$idx + 1]) && is_numeric($cols[$idx + 1])) {
                    $scoreVal = floatval($cols[$idx + 1]);
                    if ($scoreVal >= 0 && $scoreVal <= 100) {
                        $currentScores[$compCode] = $scoreVal;
                    }
                }
            }

            session(["academic.scores.$course.$studentId" => $currentScores]);
            $updated++;
        }

        return back()->with('notice', "Berhasil memperbarui nilai untuk {$updated} mahasiswa secara massal.");
    }

    public function gradeItem(Request $request,int $item)
    {
        $resource=Learning::items()[$item] ?? null;
        abort_unless($resource, 404);
        $this->authorizeOwnership($resource['course'], $item);
        abort_unless(session("learning.submissions.$item"),404);
        if (!empty($resource['questions'])) {
            $questions=$resource['questions'];
        } elseif (($resource['scoring_mode'] ?? null) === 'manual_cpmk' && !empty($resource['manual_cpmk_weights'])) {
            $questions=array_map(fn($weight)=>['points'=>100], array_values($resource['manual_cpmk_weights']));
        } else {
            $questions=[['points'=>$resource['points'] ?? 100]];
        }
        $data=$request->validate(['points'=>'required|array|size:'.count($questions),'points.*'=>'required|numeric|min:0','feedback'=>'nullable|string|max:3000']);
        foreach($questions as $index=>$question){
            if(!isset($data['points'][$index]) || $data['points'][$index]>$question['points']) return back()->withErrors(['points'=>'Nilai soal tidak boleh melebihi poin maksimal.'])->withInput();
        }
        session(["academic.item_grades.$item.1"=>$data]);
        return back()->with('notice','Penilaian disimpan dan masuk ke komponen course terkait.');
    }

    public function student()
    {
        return view('learning.grades',['courses'=>Learning::courses()]);
    }

    public function assessmentGrading(int $course, int $item)
    {
        $this->authorizeOwnership($course, $item);
        $evaluation = Academic::assessmentEvaluation($course, $item);

        return view('dosen.penilaian.item-grading', [
            'course' => $evaluation['course'],
            'item' => $evaluation['item'],
            'questions' => $evaluation['questions'],
            'students' => $evaluation['students'],
            'pendingQueue' => $evaluation['pending_queue'],
            'results' => $evaluation['results'],
            'totalPending' => $evaluation['total_pending'],
            'allCompleted' => $evaluation['all_completed'],
        ]);
    }

    public function evaluateEssay(int $course, int $item, int $student, ?int $questionIndex = null)
    {
        $this->authorizeOwnership($course, $item);
        $evaluation = Academic::assessmentEvaluation($course, $item);
        $questions   = $evaluation['questions'];

        $studentObj = collect($evaluation['students'])->firstWhere('id', $student);
        abort_unless($studentObj, 404);

        // Kumpulkan semua soal esai + jawaban + skor yang sudah ada
        $grades = session("academic.item_grades.{$item}.{$student}.points", []);
        $submission = session("learning.submissions.{$item}.{$student}") ?? session("learning.submissions.{$item}");

        $essayItems = [];
        foreach ($questions as $qIdx => $q) {
            if (! $q['is_essay']) continue;

            $answerText = $submission['question_answers'][$qIdx]['text']
                ?? ($submission['answer'] ?? '');

            $currentScore = isset($grades[$qIdx]) && is_numeric($grades[$qIdx]) ? (float) $grades[$qIdx] : null;
            $maxPoints    = (float) $q['points'];
            $porsiSoal    = $q['porsi_soal_raw'];

            $persen    = ($currentScore !== null && $maxPoints > 0) ? ($currentScore / $maxPoints) : null;
            $nilaiSoal = $persen !== null ? round($persen * $porsiSoal, 2) : null;

            $essayItems[] = [
                'question_index' => $qIdx,
                'question'       => $q,
                'answer_text'    => $answerText,
                'current_score'  => $currentScore,
                'max_points'     => $maxPoints,
                'porsi_soal'     => $porsiSoal,
                'persen'         => $persen !== null ? round($persen * 100, 1) : null,
                'nilai_soal'     => $nilaiSoal,
            ];
        }

        abort_unless(count($essayItems) > 0, 404);

        return view('dosen.penilaian.essay-evaluation', [
            'course'      => $evaluation['course'],
            'item'        => $evaluation['item'],
            'student'     => $studentObj,
            'essay_items' => $essayItems,
            'questions'   => $questions,
        ]);
    }

    public function saveEssayScore(Request $request, int $course, int $item, int $student, int $questionIndex)
    {
        $this->authorizeOwnership($course, $item);
        $evaluation = Academic::assessmentEvaluation($course, $item);
        $questions  = $evaluation['questions'];
        $question   = $questions[$questionIndex] ?? null;
        abort_unless($question && $question['is_essay'], 404);

        $maxPoints = (float) $question['points'];
        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:' . $maxPoints],
        ], [
            'score.required' => 'Masukkan skor nilai untuk jawaban ini.',
            'score.numeric'  => 'Skor harus berupa angka.',
            'score.min'      => 'Skor minimal adalah 0.',
            'score.max'      => "Skor maksimal untuk soal ini adalah {$maxPoints}.",
        ]);

        $grades = session("academic.item_grades.{$item}.{$student}.points", []);
        $grades[$questionIndex] = (float) $validated['score'];
        session(["academic.item_grades.{$item}.{$student}.points" => $grades]);

        // Kembali ke halaman split grading mahasiswa yang sama
        return redirect()
            ->route('dosen.item.penilaian.esai', [$course, $item, $student])
            ->with('notice', 'Skor berhasil disimpan.');
    }
}
