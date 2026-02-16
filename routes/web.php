<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoController;

Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
Route::get('/videos/upload', function() {
    return view('videos.upload');
})->name('videos.upload.form');
Route::post('/videos/upload', [VideoController::class, 'upload'])->name('videos.upload');

Route::delete('/videos/{id}', [VideoController::class, 'delete'])->name('videos.delete');
Route::get('/', function () {
    return view('welcome');
});
