<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TextToolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TextController extends Controller
{
    public function __construct(
        private readonly TextToolService $textTools,
    ) {
    }

    public function analyze(Request $request): JsonResponse
    {
        $data = $request->validate([
            'text' => ['nullable', 'string'],
        ]);

        return response()->json([
            'success' => true,
            'stats' => $this->textTools->analyze((string) ($data['text'] ?? '')),
        ]);
    }

    public function convertCase(Request $request): JsonResponse
    {
        $data = $request->validate([
            'text' => ['nullable', 'string'],
            'mode' => ['required', Rule::in(['upper', 'lower', 'sentence', 'title', 'capitalize', 'alternating', 'inverse'])],
        ]);

        return response()->json([
            'success' => true,
            'text' => $this->textTools->convertCase((string) ($data['text'] ?? ''), $data['mode']),
        ]);
    }
}
