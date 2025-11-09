<?php

use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

// API for polling list
Route::get('/api/uploads', [UploadController::class, 'listApi'])->name('api.uploads');
