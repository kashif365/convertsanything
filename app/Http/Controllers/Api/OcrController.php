<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OcrController extends Controller
{
    public function extract(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file'     => ['required', 'file', 'mimes:jpg,jpeg,png,gif,bmp', 'max:5120'],
            'language' => ['nullable', 'string', Rule::in([
                'eng', 'spa', 'fra', 'deu', 'ita', 'por',
                'chi_sim', 'jpn', 'kor', 'ara', 'rus',
            ])],
        ]);

        $file    = $data['file'];
        $tmpDir  = storage_path('app/temp');

        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $safeName = uniqid('ocr_') . '.' . $file->getClientOriginalExtension();
        $filePath = $tmpDir . '/' . $safeName;
        $file->move($tmpDir, $safeName);

        try {
            $service = new OcrService();
            $result  = $service->extractText($filePath, $data['language'] ?? 'eng');

            return response()->json([
                'success' => true,
                'message' => 'Text extracted successfully.',
                'text'    => $result['text'],
                'pages'   => $result['pages'],
                'words'   => $result['words'],
                'chars'   => $result['chars'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } finally {
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }
}
