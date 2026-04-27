<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Video;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    // Show videos + search
    public function index(Request $request)
    {
        $query = Video::query();

        if ($request->search) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $videos = $query->latest()->get();

        return view('videos.index', compact('videos'));
    }

    // Upload + process
    public function upload(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'video' => 'required|mimes:mp4,mov,avi|max:102400',
        ]);

        $videoFile = $request->file('video');
        $filename = time() . '_' . $videoFile->getClientOriginalName();

        $path = $videoFile->storeAs('uploads', $filename, 'public');
        $fullPath = storage_path('app/public/' . $path);

        // FFProbe
        $ffprobe = \FFMpeg\FFProbe::create([
            'ffprobe.binaries' => 'C:\\ffmpeg\\bin\\ffprobe.exe',
            'ffmpeg.binaries' => 'C:\\ffmpeg\\bin\\ffmpeg.exe',
        ]);

        $duration = $ffprobe->format($fullPath)->get('duration');
        $size = filesize($fullPath);

        $durationFormatted = gmdate("H:i:s", (int) $duration);
        $sizeFormatted = round($size / 1024 / 1024, 2) . ' MB';

        // ONLY Thumbnail (fast)
        $thumbnail = 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.png';

        try {
            \ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::fromDisk('public')
                ->open($path)
                ->getFrameFromSeconds(1)
                ->export()
                ->toDisk('public')
                ->save('uploads/' . $thumbnail);
        } catch (\Exception $e) {
            $thumbnail = null;
        }

        Video::create([
            'title' => $request->title,
            'filename' => $filename,
            'thumbnail' => $thumbnail,
            'duration' => $durationFormatted,
            'size' => $sizeFormatted,
        ]);

        return redirect()->route('videos.index')
            ->with('success', 'Video uploaded successfully!');
    }

    // Download
    public function download($file)
    {
        $path = storage_path('app/public/uploads/' . $file);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->download($path);
    }

    // Delete
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

        return redirect()->route('videos.index')->with('success', 'Deleted!');
    }
}