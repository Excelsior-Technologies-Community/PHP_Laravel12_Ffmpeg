<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoController;

Route::get('/', fn() => redirect()->route('videos.index'));

Route::get('/videos',                    [VideoController::class, 'index'])->name('videos.index');
Route::get('/videos/upload',             fn() => view('videos.upload'))->name('videos.upload.form');
Route::post('/videos/upload',            [VideoController::class, 'upload'])->name('videos.upload');
Route::delete('/videos/{id}',            [VideoController::class, 'delete'])->name('videos.delete');
Route::get('/videos/download/{file}',    [VideoController::class, 'download'])->name('videos.download');

// Transcoding
Route::get('/videos/{id}/720',           [VideoController::class, 'convert720'])->name('videos.720');
Route::get('/videos/{id}/1080',          [VideoController::class, 'convert1080'])->name('videos.1080');
Route::get('/videos/{id}/480',           [VideoController::class, 'convert480'])->name('videos.480');
Route::get('/videos/{id}/h265',          [VideoController::class, 'convertH265'])->name('videos.h265');
Route::post('/videos/{id}/compress',     [VideoController::class, 'compress'])->name('videos.compress');

// Streaming
Route::get('/videos/{id}/hls',           [VideoController::class, 'createHls'])->name('videos.hls');

// Editing
Route::post('/videos/{id}/trim',         [VideoController::class, 'trim'])->name('videos.trim');
Route::get('/videos/merge',              [VideoController::class, 'showMerge'])->name('videos.merge');
Route::post('/videos/merge',             [VideoController::class, 'merge'])->name('videos.merge.process');
Route::get('/videos/{id}/mute',          [VideoController::class, 'muteVideo'])->name('videos.mute');

// Audio
Route::get('/videos/{id}/audio',         [VideoController::class, 'extractAudio'])->name('videos.audio');
Route::post('/videos/{id}/audio-convert',[VideoController::class, 'convertAudio'])->name('videos.audio.convert');

// Thumbnails & GIF
Route::get('/videos/{id}/thumbnails',    [VideoController::class, 'extractThumbnails'])->name('videos.thumbnails');
Route::get('/videos/{id}/gif',           [VideoController::class, 'createGif'])->name('videos.gif');

// Watermark & Subtitle
Route::get('/videos/{id}/watermark',     [VideoController::class, 'addWatermark'])->name('videos.watermark');
Route::post('/videos/{id}/subtitle',     [VideoController::class, 'addSubtitle'])->name('videos.subtitle');
