<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Services\Ai\AiTutor;
use App\Services\Ai\ContextBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AiEvalController extends Controller
{
    /**
     * Development-only endpoint for AI Tutor v2 pipeline evaluation.
     *
     * Non-destructive: Does NOT create threads, save messages, or deduct user quota.
     */
    public function evaluate(Request $request, AiTutor $tutor, ContextBuilder $contextBuilder): JsonResponse
    {
        // 1. Only active in non-production environments
        if (app()->environment('production')) {
            abort(404, 'Not Found');
        }

        // 2. Protected by X-Eval-Token
        $expectedToken = (string) (config('ai.eval_token') ?: env('AI_EVAL_TOKEN', ''));
        $providedToken = (string) $request->header('X-Eval-Token');

        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'message' => 'Unauthorized. Invalid or missing X-Eval-Token.',
            ], 401);
        }

        config(['ai.v2' => true, 'ai.single_call' => true]);

        // 3. Validate input
        $validated = $request->validate([
            'assessment_id' => 'nullable|integer',
            'question' => 'required|string',
            'code' => 'nullable|string',
            'console' => 'nullable|string',
            'console_output' => 'nullable|string',
            'selected_line' => 'nullable',
            'reference_solution' => 'nullable|string',
            'task_title' => 'nullable|string',
            'task_body' => 'nullable|string',
            'coding_steps' => 'nullable|string',
            'linked_materials' => 'nullable|string',
            'turns' => 'nullable|integer',
            'history' => 'nullable|array',
        ]);

        $consoleOutput = $validated['console_output'] ?? $validated['console'] ?? '';
        $selectedLine = $validated['selected_line'] ?? null;
        $turns = (int) ($validated['turns'] ?? 0);
        $history = $validated['history'] ?? [];

        // 4. Resolve assessment or standalone task context
        $assessmentId = $validated['assessment_id'] ?? null;
        $assessment = $assessmentId ? Assessment::with('classSection')->find($assessmentId) : null;

        $inputData = [
            'question' => $validated['question'],
            'code' => $validated['code'] ?? '',
            'console_output' => $consoleOutput,
            'selected_line' => $selectedLine,
        ];

        if ($assessment) {
            $builtContext = $contextBuilder->build($assessment, $inputData);
            $taskObject = (object) [
                'id' => $assessment->id,
                'title' => $builtContext['title'],
                'body' => $builtContext['description']
                    ."\n\nTahapan/instruksi:\n".$builtContext['coding_steps']
                    ."\n\nMateri kuliah terkait:\n".$builtContext['linked_materials'],
            ];
        } else {
            $defaultTitle = $validated['task_title'] ?? 'Tugas Praktikum Algoritma & Struktur Data (BST)';
            $defaultBody = $validated['task_body'] ?? 'Implementasikan struktur data Binary Search Tree (BST) di Python mencakup node, penyisipan (insert), dan penelusuran (inorder traversal).';
            $defaultSteps = $validated['coding_steps'] ?? "Tahap 1: Buat kelas Node BST.\nTahap 2: Buat fungsi insert.\nTahap 3: Buat fungsi inorder traversal.";
            $defaultMaterials = $validated['linked_materials'] ?? 'Materi BST: Node menyimpan key, left, right. Operasi rekursif.';

            $mockAssessment = (object) [
                'id' => $assessmentId ?? 81,
                'name' => $defaultTitle,
                'description' => $defaultBody,
                'learning_payload' => [
                    'title' => $defaultTitle,
                    'body' => $defaultBody,
                    'coding_steps' => $defaultSteps,
                    'type' => 'coding',
                ],
            ];

            $builtContext = $contextBuilder->build($mockAssessment, $inputData);
            $builtContext['linked_materials'] = $defaultMaterials;

            $taskObject = (object) [
                'id' => $assessmentId ?? 81,
                'title' => $builtContext['title'],
                'body' => $builtContext['description']
                    ."\n\nTahapan/instruksi:\n".$builtContext['coding_steps']
                    ."\n\nMateri kuliah terkait:\n".$builtContext['linked_materials'],
            ];
        }

        if (! empty($validated['reference_solution'])) {
            $builtContext['reference_solution'] = $validated['reference_solution'];
        }

        // 5. Execute Phase 5 single-call pipeline non-destructively (userId = 0)
        try {
            $userId = 0;
            $result = $tutor->answerSingleCall(
                $userId,
                $taskObject,
                $builtContext,
                $history,
                $turns,
                null,
                null
            );

            return response()->json([
                'verdict' => $result['verdict'],
                'reply' => $result['reply'],
                'hint_level' => $result['hint_level'] ?? 1,
                'reviewed' => $result['reviewed'] ?? false,
                'raw_reply' => $result['raw_reply'] ?? null,
                'guard_reason' => $result['guard_reason'] ?? null,
                'reviewer_reason' => $result['reviewer_reason'] ?? null,
            ]);
        } catch (HttpException $e) {
            return response()->json([
                'verdict' => 'error',
                'reply' => $e->getMessage(),
                'error_code' => $e->getHeaders()['X-AI-Error'] ?? 'internal_error',
                'status' => $e->getStatusCode(),
            ], $e->getStatusCode());
        }
    }
}
