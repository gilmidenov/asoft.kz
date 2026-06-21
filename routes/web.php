<?php

use Illuminate\Support\Facades\Route;

// Все запросы (кроме /api/*) отдают app.blade.php
// Vue Router сам определяет что показать по URL
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
