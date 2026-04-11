<?php

namespace App\Http\Controllers;

use App\Services\TemporaryDownloadService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class DownloadController extends Controller
{
    public function __construct(
        private readonly TemporaryDownloadService $downloads,
    ) {
    }

    public function show(Request $request, string $file): Response|BinaryFileResponse
    {
        $resolved = $this->downloads->resolve($file);

        abort_unless($resolved !== null, 404);

        if ($request->boolean('inline')) {
            return response()->file($resolved['path']);
        }

        return response()->download($resolved['path'], $resolved['download_name']);
    }
}
