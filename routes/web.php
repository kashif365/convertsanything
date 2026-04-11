<?php

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/downloads/{file}', [DownloadController::class, 'show'])
    ->where('file', '[^/]+')
    ->name('downloads.show');
Route::get('/tools/{category}', [PageController::class, 'category'])
    ->where('category', 'images|documents|text')
    ->name('tools.category');
Route::get('/tools/{category}/{tool}', [PageController::class, 'tool'])->name('tools.show');
