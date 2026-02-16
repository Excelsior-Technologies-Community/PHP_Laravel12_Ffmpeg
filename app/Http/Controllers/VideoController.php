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
