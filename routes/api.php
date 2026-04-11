<?php

use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\PdfController;
use App\Http\Controllers\Api\TextController;
use Illuminate\Support\Facades\Route;

Route::prefix('images')->group(function () {
    Route::post('/jpg-to-png', [ImageController::class, 'jpgToPng']);
    Route::post('/png-to-webp', [ImageController::class, 'pngToWebp']);
    Route::post('/compress', [ImageController::class, 'compress']);
    Route::post('/resize', [ImageController::class, 'resize']);
    Route::post('/convert', [ImageController::class, 'convert']);
});

Route::prefix('text')->group(function () {
    Route::post('/analyze', [TextController::class, 'analyze']);
    Route::post('/case-convert', [TextController::class, 'convertCase']);
});

Route::prefix('convert')->group(function () {
    Route::post('/pdf-to-word', [DocumentController::class, 'pdfToWord']);
    Route::post('/word-to-pdf', [DocumentController::class, 'wordToPdf']);
});

Route::prefix('pdf')->group(function () {
    Route::post('/merge', [PdfController::class, 'merge']);
    Route::post('/split', [PdfController::class, 'split']);
});
