<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('app:about', function () {
    $this->comment('ConvertsAnything local tools app');
})->purpose('Display a short app-specific description.');
