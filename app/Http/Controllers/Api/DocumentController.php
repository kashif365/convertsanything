<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentToolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentToolService $documents,
    ) {
    }

    public function pdfToWord(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ]);

        try {
            return response()->json(
                $this->documents->pdfToWord($data['file'])
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function wordToPdf(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'extensions:doc,docx', 'max:51200'],
        ]);

        try {
            return response()->json(
                $this->documents->wordToPdf($data['file'])
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
