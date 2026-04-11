<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentToolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PdfController extends Controller
{
    public function __construct(
        private readonly DocumentToolService $documents,
    ) {
    }

    public function merge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'files' => ['required', 'array', 'min:2'],
            'files.*' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ]);

        try {
            return response()->json(
                $this->documents->mergePdfs($data['files'])
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function split(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
            'ranges' => ['required', 'string'],
        ]);

        try {
            return response()->json(
                $this->documents->splitPdf($data['file'], $data['ranges'])
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
