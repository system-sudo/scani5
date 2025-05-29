<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Storage;


Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// REVERB ROUTES

Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
// Route::post('/posts', [PostController::class, 'store'])->name('posts.store');

Route::get('/files/{path}', function ($path) {
    // Check if the file exists
    if (!Storage::disk('public')->exists($path)) {
        abort(404, 'File not found.');
    }

    // Get the file content and MIME type
    $fileContent = Storage::disk('public')->get($path);
    $mimeType = Storage::disk('public')->mimeType($path);

    return response($fileContent, 200)
        ->header('Content-Type', $mimeType)
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Headers', 'cache-control');
})->where('path', '.*');
