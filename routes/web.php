<?php

use Illuminate\Support\Facades\Route;

// SPA fallback - serve React app untuk semua route non-API
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
