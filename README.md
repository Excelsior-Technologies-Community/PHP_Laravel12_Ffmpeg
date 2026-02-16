# PHP_Laravel12_Ffmpeg

## Introduction

PHP_Laravel12_Ffmpeg is a modern **Laravel 12** demonstration project
that shows how to integrate **FFMpeg video processing** inside a real
web application workflow.

This project focuses on **practical video handling features used in real
production systems**, including:

-   Secure video upload with validation\
-   Automatic thumbnail generation\
-   Video format conversion to MP4 (H.264)\
-   Video resizing for optimized playback\
-   Clean UI to preview uploaded videos\
-   Proper file deletion and storage management

------------------------------------------------------------------------

## Project Overview

This project is built using:

-   **Laravel 12**
-   **PHP 8.2+**
-   **FFMpeg & FFProbe binaries**
-   **protonemedia/laravel-ffmpeg package**
-   **Tailwind CSS modern UI**

The application allows users to:

1.  Upload videos securely\
2.  Automatically generate thumbnails\
3.  Convert videos to MP4 format\
4.  Resize videos for lightweight streaming\
5.  View all uploaded videos in a modern dashboard\
6.  Delete videos along with processed files

------------------------------------------------------------------------

## Prerequisites

Make sure the following are installed:

-   PHP **8.2+**
-   Composer
-   Laravel 12
-   MySQL / MariaDB
-   FFmpeg & FFprobe

------------------------------------------------------------------------

## Step 1: Install FFmpeg on Windows

To enable video processing in this Laravel project, download and configure FFmpeg & FFprobe as follows:

1) Open the official Windows builds page:

```bash
https://www.gyan.dev/ffmpeg/builds/
```

2) In the Release builds section, download:

```
ffmpeg-release-essentials.zip
```

This package already contains:

- ffmpeg.exe (video processing engine)

- ffprobe.exe (media metadata & audio detection)

- Required codec libraries

> The Essentials build is recommended. Avoid git or full builds.


3) Extract the ZIP file and move the folder to a simple location such as:

```
C:\ffmpeg
```

Final structure should be:

```
C:\ffmpeg\bin\ffmpeg.exe
C:\ffmpeg\bin\ffprobe.exe
```

4) Add FFmpeg to the Windows System PATH:

- Search Environment Variables in Windows

- Open Edit the system environment variables → Environment Variables

- Edit Path under System variables

- Add:

```
C:\ffmpeg\bin
```

5) Verify installation using Command Prompt:

```
ffmpeg -version
```

If FFmpeg version details appear, installation is successful.

------------------------------------------------------------------------

## Step 2: Create Laravel Project

``` bash
composer create-project laravel/laravel PHP_Laravel12_Ffmpeg "12.*"
cd PHP_Laravel12_Ffmpeg
```

------------------------------------------------------------------------

## Step 3: Install Laravel FFMpeg Package

Install the protonemedia/laravel-ffmpeg package via Composer:

```bash
composer require pbmedia/laravel-ffmpeg
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="ProtoneMedia\LaravelFFMpeg\Support\ServiceProvider"
```

This will create config/laravel-ffmpeg.php.

------------------------------------------------------------------------

## Step 4: Configure FFMpeg

Open config/laravel-ffmpeg.php

Ensure paths to FFMpeg and FFProbe binaries are correct:

```php
<?php

return [
    'ffmpeg' => [
        'binaries' => env('FFMPEG_BINARIES', 'ffmpeg'),

        'threads' => 12,   // set to false to disable the default 'threads' filter
    ],

    'ffprobe' => [
        'binaries' => env('FFPROBE_BINARIES', 'ffprobe'),
    ],

    'timeout' => 3600,

    'log_channel' => env('LOG_CHANNEL', 'stack'),   // set to false to completely disable logging

    'temporary_files_root' => env('FFMPEG_TEMPORARY_FILES_ROOT', sys_get_temp_dir()),

    'temporary_files_encrypted_hls' => env('FFMPEG_TEMPORARY_ENCRYPTED_HLS', env('FFMPEG_TEMPORARY_FILES_ROOT', sys_get_temp_dir())),
];
```


Add to .env file:

```.env
FFMPEG_BINARIES=C:\ffmpeg-8.0.1-essentials_build\bin\ffmpeg.exe
FFPROBE_BINARIES=C:\ffmpeg-8.0.1-essentials_build\bin\ffprobe.exe
```

------------------------------------------------------------------------

## Step 5: configure .env

```.env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel12_ffmpeg
DB_USERNAME=root
DB_PASSWORD=
```

create database  using below command

```bash
php artisan migrate
```

------------------------------------------------------------------------

## Step 6: Create Migration & Model

Create Video model and migration:

```bash
php artisan make:model Video -m
```

### Migration Table

Edit migration database/migrations/xxxx_create_videos_table.php:

```php
public function up(): void
{
    Schema::create('videos', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('filename');
        $table->string('thumbnail')->nullable();
        $table->timestamps();
    });
}
```

Run migration:

```bash
php artisan migrate
```

### Model

app/Models/Video.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'title',
        'filename',
        'thumbnail',
    ];
}
```


Create storage link:

``` bash
php artisan storage:link
```

------------------------------------------------------------------------

## Step 7: Create VideoController

```bash
php artisan make:controller VideoController
```

Add methods to app/Http/Controllers/VideoController.php:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use App\Models\Video;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    /**
     * Display all videos
     */
    public function index()
    {
        $videos = Video::latest()->get();
        return view('videos.index', compact('videos'));
    }

    /**
     * Upload and process video
     */
    public function upload(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'video' => 'required|mimes:mp4,mov,avi|max:102400', // 100MB
        ]);

        $videoFile = $request->file('video');
        $filename = time() . '_' . $videoFile->getClientOriginalName();

        // Store original video
        $path = $videoFile->storeAs('uploads', $filename, 'public');

        /*
        |--------------------------------------------------------------------------
        | 1. Generate Thumbnail
        |--------------------------------------------------------------------------
        */
        $thumbnail = 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.png';

        FFMpeg::fromDisk('public')
            ->open($path)
            ->getFrameFromSeconds(2)
            ->export()
            ->toDisk('public')
            ->save('uploads/' . $thumbnail);

        /*
        |--------------------------------------------------------------------------
        | 2. Convert to MP4 (X264)
        |--------------------------------------------------------------------------
        */
        FFMpeg::fromDisk('public')
            ->open($path)
            ->export()
            ->inFormat(new X264)
            ->toDisk('public')
            ->save('uploads/converted_' . $filename);

        /*
        |--------------------------------------------------------------------------
        | 3. Resize Video
        |--------------------------------------------------------------------------
        */
        FFMpeg::fromDisk('public')
            ->open($path)
            ->export()
            ->inFormat(new X264)
            ->resize(320, 240)
            ->toDisk('public')
            ->save('uploads/resized_' . $filename);

        /*
        |--------------------------------------------------------------------------
        | 4. Extract Audio (only if audio stream exists)
        |--------------------------------------------------------------------------
        */
        $ffprobe = FFProbe::create([
            'ffprobe.binaries' => env('FFPROBE_BINARIES'),
            'ffmpeg.binaries'  => env('FFMPEG_BINARIES'),
        ]);

        $fullPath = storage_path('app/public/' . $path);
        $audioStreams = $ffprobe->streams($fullPath)->audios();

        if ($audioStreams->count() > 0) {
            FFMpeg::fromDisk('public')
                ->open($path)
                ->export()
                ->inFormat(new \FFMpeg\Format\Audio\Mp3())   
                ->toDisk('public')
                ->save('uploads/audio_' . pathinfo($filename, PATHINFO_FILENAME) . '.mp3');
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Save in Database
        |--------------------------------------------------------------------------
        */
        Video::create([
            'title' => $request->title,
            'filename' => $filename,
            'thumbnail' => $thumbnail,
        ]);

        return redirect()->route('videos.index')
            ->with('success', 'Video uploaded and processed successfully!');
    }

    /**
     * Delete video and all generated files
     */
    public function delete($id)
    {
        $video = Video::findOrFail($id);

        $files = [
            $video->filename,
            $video->thumbnail,
            'converted_' . $video->filename,
            'resized_' . $video->filename,
            'audio_' . pathinfo($video->filename, PATHINFO_FILENAME) . '.mp3',
        ];

        foreach ($files as $file) {
            if ($file && Storage::disk('public')->exists('uploads/' . $file)) {
                Storage::disk('public')->delete('uploads/' . $file);
            }
        }

        $video->delete();

        return redirect()->route('videos.index')
            ->with('success', 'Video deleted successfully.');
    }
}
```

------------------------------------------------------------------------

## Step 8: Create blade files

### upload.blade.php

resources/views/videos/upload.blade.php


```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video</title>

    {{-- Tailwind CDN (for quick setup) --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-slate-900 via-gray-900 to-black min-h-screen flex items-center justify-center">

    <div class="w-full max-w-xl bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl shadow-2xl p-8">

        {{-- Heading --}}
        <h2 class="text-3xl font-bold text-white text-center mb-6">
            🎬 Upload New Video
        </h2>

        {{-- Success Message --}}
        @if(session('success'))
            <div class="mb-4 p-3 rounded-lg bg-green-500/20 border border-green-400 text-green-300 text-center">
                {{ session('success') }}
            </div>
        @endif

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-500/20 border border-red-400 text-red-300">
                <ul class="text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Upload Form --}}
        <form method="POST" action="{{ route('videos.upload') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Video Title --}}
            <div>
                <label class="block text-sm font-medium text-gray-200 mb-1">
                    Video Title
                </label>
                <input
                    type="text"
                    name="title"
                    placeholder="Enter video title..."
                    class="w-full px-4 py-2 rounded-lg bg-white/20 border border-white/30 text-white placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    required
                >
            </div>

            {{-- File Upload --}}
            <div>
                <label class="block text-sm font-medium text-gray-200 mb-1">
                    Select Video File
                </label>
                <input
                    type="file"
                    name="video"
                    class="w-full text-sm text-gray-200
                           file:mr-4 file:py-2 file:px-4
                           file:rounded-lg file:border-0
                           file:text-sm file:font-semibold
                           file:bg-indigo-600 file:text-white
                           hover:file:bg-indigo-700
                           cursor-pointer"
                    required
                >
            </div>

            {{-- Submit Button --}}
            <button
                type="submit"
                class="w-full py-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 transition duration-300 text-white font-semibold shadow-lg"
            >
                Upload Video
            </button>
        </form>

        {{-- Footer --}}
        <p class="text-center text-gray-400 text-xs mt-6">
            Laravel 12 • FFMpeg Video Processing • 2026 UI
        </p>
    </div>

</body>
</html>
```

### index.blade.php

resources/views/videos/index.blade.php

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Library</title>

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-slate-900 via-gray-900 to-black min-h-screen text-white">

    {{-- Page Container --}}
    <div class="max-w-7xl mx-auto px-6 py-10">

        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-10">
            <h1 class="text-4xl font-bold tracking-tight">
                🎬 Video Library
            </h1>

            <a href="{{ route('videos.upload.form') }}"
               class="mt-4 md:mt-0 inline-block px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 transition font-semibold shadow-lg">
                + Upload New Video
            </a>
        </div>

        {{-- Success Message --}}
        @if(session('success'))
            <div class="mb-6 p-4 rounded-lg bg-green-600/20 border border-green-500 text-green-300">
                {{ session('success') }}
            </div>
        @endif

        {{-- Empty State --}}
        @if($videos->isEmpty())
            <div class="text-center py-20 bg-white/5 border border-white/10 rounded-2xl">
                <p class="text-gray-400 text-lg">No videos uploaded yet.</p>

                <a href="{{ route('videos.upload.form') }}"
                   class="inline-block mt-4 px-5 py-2 bg-indigo-600 rounded-lg hover:bg-indigo-700">
                    Upload First Video
                </a>
            </div>
        @else

        {{-- Video Grid --}}
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">

            @foreach($videos as $video)
                <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl overflow-hidden shadow-xl hover:scale-[1.02] transition">

                    {{-- Thumbnail --}}
                    <div class="aspect-video bg-black">
                        <img
                            src="{{ asset('storage/uploads/'.$video->thumbnail) }}"
                            alt="{{ $video->title }}"
                            class="w-full h-full object-cover"
                        >
                    </div>

                    {{-- Card Content --}}
                    <div class="p-5 space-y-4">

                        {{-- Title --}}
                        <h2 class="text-lg font-semibold line-clamp-1">
                            {{ $video->title }}
                        </h2>

                        {{-- Video Player --}}
                        <video controls class="w-full rounded-lg border border-white/10">
                            <source src="{{ asset('storage/uploads/'.$video->filename) }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>

                        {{-- Actions --}}
                        <div class="flex items-center justify-between pt-2">

                            <span class="text-xs text-gray-400">
                                Uploaded {{ $video->created_at->diffForHumans() }}
                            </span>

                            {{-- Delete Button (future feature) --}}
                            <form action="{{ route('videos.delete', $video->id) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this video?');">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        class="text-red-400 hover:text-red-500 text-sm font-medium">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach

        </div>
        @endif

        {{-- Footer --}}
        <p class="text-center text-gray-500 text-xs mt-16">
            Laravel 12 • FFMpeg Processing • Modern UI 2026
        </p>

    </div>

</body>
</html>
```

------------------------------------------------------------------------

## Step 9: Define Routes

Edit routes/web.php:

```php
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
```

------------------------------------------------------------------------

## Step 10: Run Project

Start the Laravel server:

```bash
php artisan serve
```

Visit:

```bash
Upload page: http://127.0.0.1:8000/videos/upload

View videos: http://127.0.0.1:8000/videos
```

------------------------------------------------------------------------

## Output

### Upload Page

<img width="1915" height="1028" alt="Screenshot 2026-02-16 103406" src="https://github.com/user-attachments/assets/0f835d63-e565-4843-a22e-39c72684b868" />

<img width="1919" height="1030" alt="Screenshot 2026-02-16 105832" src="https://github.com/user-attachments/assets/f8ebb4b9-2f85-4866-a744-d068e614da22" />



### index Page

<img width="1915" height="1032" alt="Screenshot 2026-02-16 111020" src="https://github.com/user-attachments/assets/a79297cc-b5cc-4c8c-aef2-aaf4b6520a0a" />

------------------------------------------------------------------------

## Project Structure

Here’s the basic folder structure:

```
PHP_Laravel12_Ffmpeg/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── VideoController.php
│   │   └── Requests/
│   └── Models/
│       └── Video.php
│
├── config/
│   └── laravel-ffmpeg.php
│
├── database/
│   ├── migrations/
│   │   └── 2026_02_13_000000_create_videos_table.php
│
├── storage/
│   └── app/
│       └── public/
│           └── uploads/        # Uploaded videos, thumbnails, audio, resized files
│
├── public/
│   └── storage -> ../storage/app/public   # Symlink created by `php artisan storage:link`
│
├── resources/
│   └── views/
│       ├── videos/
│       │   ├── index.blade.php
│       │   └── upload.blade.php
│
├── routes/
│   └── web.php
│
├── .env
└── composer.json
```

------------------------------------------------------------------------

Your PHP_Laravel12_Ffmpeg Project is now ready!


