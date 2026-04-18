<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImageToolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ImageController extends Controller
{
    public function __construct(
        private readonly ImageToolService $images,
    ) {
    }

    public function jpgToPng(Request $request): JsonResponse
    {
        $data = $request->validate([
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:jpg,jpeg', 'max:51200'],
            'scale'   => ['nullable', 'integer', 'between:10,200'],
        ]);

        return response()->json(
            $this->images->jpgToPng($data['files'], (int) ($data['scale'] ?? 100))
        );
    }

    public function pngToWebp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:png', 'max:51200'],
            'quality' => ['nullable', 'integer', 'between:50,100'],
            'scale'   => ['nullable', 'integer', 'between:10,200'],
        ]);

        return response()->json(
            $this->images->pngToWebp(
                $data['files'],
                (int) ($data['quality'] ?? 90),
                (int) ($data['scale'] ?? 100),
            )
        );
    }

    public function compress(Request $request): JsonResponse
    {
        $data = $request->validate([
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:51200'],
            'quality' => ['nullable', 'integer', 'between:20,95'],
            'format'  => ['nullable', Rule::in(['auto', 'image/jpeg', 'image/png', 'image/webp'])],
            'scale'   => ['nullable', 'integer', 'between:10,200'],
        ]);

        return response()->json(
            $this->images->compress(
                $data['files'],
                (int) ($data['quality'] ?? 80),
                (string) ($data['format'] ?? 'auto'),
                (int) ($data['scale'] ?? 100),
            )
        );
    }

    public function resize(Request $request): JsonResponse
    {
        $data = $request->validate([
            'files'      => ['required', 'array', 'min:1'],
            'files.*'    => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:51200'],
            'width'      => ['required', 'integer', 'min:1', 'max:10000'],
            'height'     => ['required', 'integer', 'min:1', 'max:10000'],
            'lock_aspect'=> ['nullable', 'boolean'],
            'scale'      => ['nullable', 'integer', 'between:10,200'],
        ]);

        return response()->json(
            $this->images->resize(
                $data['files'],
                (int) $data['width'],
                (int) $data['height'],
                (bool) ($data['lock_aspect'] ?? false),
                (int) ($data['scale'] ?? 100),
            )
        );
    }

    public function convert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/bmp,image/tiff', 'max:51200'],
            'quality' => ['nullable', 'integer', 'between:20,100'],
            'format'  => ['required', Rule::in(['image/jpeg', 'image/png', 'image/webp'])],
            'scale'   => ['nullable', 'integer', 'between:10,200'],
        ]);

        return response()->json(
            $this->images->convertAny(
                $data['files'],
                (string) $data['format'],
                (int) ($data['quality'] ?? 85),
                (int) ($data['scale'] ?? 100),
            )
        );
    }
}
