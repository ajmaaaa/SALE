<?php

namespace App\Http\Controllers;

use App\Services\Ai\CodeRunner;
use Illuminate\Http\Request;

class CodeRunnerController extends Controller
{
    public function run(Request $request, int $assignment, CodeRunner $runner)
    {
        $input = $request->validate([
            'code' => 'required|string|max:8000',
        ]);

        $result = $runner->run($assignment, $input['code']);

        if ($result['error']) {
            return response()->json(['error' => $result['error']], 503);
        }

        return response()->json($result);
    }
}
