<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Video;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\FFMpeg as FFMpegInstance;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    private function bins(): array
    {
        $ffmpeg  = env('FFMPEG_BINARIES', 'ffmpeg');
        $ffprobe = env('FFPROBE_BINARIES', 'ffprobe');
        return ['ffmpeg' => $ffmpeg, 'ffprobe' => $ffprobe];
    }

    private function ffmpeg(): FFMpegInstance
    {
        $b = $this->bins();
        return FFMpegInstance::create([
            'ffmpeg.binaries'  => $b['ffmpeg'],
            'ffprobe.binaries' => $b['ffprobe'],
        ]);
    }

    private function ffprobe(): FFProbe
    {
        $b = $this->bins();
        return FFProbe::create([
            'ffprobe.binaries' => $b['ffprobe'],
            'ffmpeg.binaries'  => $b['ffmpeg'],
        ]);
    }

    private function exec(string $cmd): bool
    {
        exec($cmd . ' 2>&1', $out, $code);
        return $code === 0;
    }

    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Video::query();
        if ($request->search) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        return view('videos.index', ['videos' => $query->latest()->get()]);
    }

    // ─── Upload ───────────────────────────────────────────────────────────────

    public function upload(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'video' => 'required|mimes:mp4,mov,avi,mkv,webm|max:102400',
        ]);

        $file     = $request->file('video');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path     = $file->storeAs('uploads', $filename, 'public');
        $fullPath = storage_path('app/public/' . $path);

        $duration  = (int) $this->ffprobe()->format($fullPath)->get('duration');
        $size      = round(filesize($fullPath) / 1024 / 1024, 2) . ' MB';
        $thumbnail = null;

        try {
            $thumbnail = 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.png';
            FFMpeg::fromDisk('public')->open($path)
                ->getFrameFromSeconds(1)->export()
                ->toDisk('public')->save('uploads/' . $thumbnail);
        } catch (\Exception $e) {
            $thumbnail = null;
        }

        Video::create([
            'title'                    => $request->title,
            'filename'                 => $filename,
            'thumbnail'                => $thumbnail,
            'duration'                 => gmdate('H:i:s', $duration),
            'size'                     => $size,
            'original_duration_seconds'=> $duration,
        ]);

        return redirect()->route('videos.index')->with('success', 'Video uploaded successfully!');
    }

    // ─── Download ─────────────────────────────────────────────────────────────

    public function download($file)
    {
        $path = storage_path('app/public/uploads/' . $file);
        abort_unless(file_exists($path), 404);
        return response()->download($path);
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function delete($id)
    {
        $video = Video::findOrFail($id);

        $files = array_filter([
            $video->filename,
            $video->thumbnail,
            $video->converted_720,
            $video->converted_1080,
            $video->converted_480,
            $video->converted_h265,
            $video->compressed_file,
            $video->trimmed_file,
            $video->merged_file,
            $video->audio_file,
            $video->converted_audio_file,
            $video->watermarked_file,
            $video->subtitled_file,
            $video->muted_file,
        ]);

        foreach ($files as $f) {
            if (Storage::disk('public')->exists('uploads/' . $f)) {
                Storage::disk('public')->delete('uploads/' . $f);
            }
        }

        if ($video->thumbnail_gallery) {
            foreach ($video->thumbnail_gallery as $t) {
                if (Storage::disk('public')->exists('uploads/' . $t)) {
                    Storage::disk('public')->delete('uploads/' . $t);
                }
            }
        }

        if ($video->hls_path) {
            $hlsDir = storage_path('app/public/uploads/hls_' . $video->id);
            if (is_dir($hlsDir)) {
                array_map('unlink', glob($hlsDir . '/*'));
                rmdir($hlsDir);
            }
        }

        $video->delete();
        return redirect()->route('videos.index')->with('success', 'Video deleted!');
    }

    // ─── Transcoding: 720p / 1080p / 480p ────────────────────────────────────

    public function convert720($id)  { return $this->transcode($id, 720); }
    public function convert1080($id) { return $this->transcode($id, 1080); }
    public function convert480($id)  { return $this->transcode($id, 480); }

    private function transcode($id, int $res)
    {
        $video  = Video::findOrFail($id);
        $input  = storage_path('app/public/uploads/' . $video->filename);
        $out    = "converted_{$res}_" . $video->filename;
        $output = storage_path('app/public/uploads/' . $out);

        $bitrate = $res >= 1080 ? 5000 : ($res >= 720 ? 2500 : 1500);
        $format  = new X264('aac', 'libx264');
        $format->setKiloBitrate($bitrate);
        $format->setAdditionalParameters(['-vf', "scale=-2:{$res}", '-preset', 'fast']);

        try {
            $this->ffmpeg()->open($input)->save($format, $output);
            $video->{"converted_{$res}"} = $out;
            $video->save();
            return back()->with('success', "{$res}p conversion done!");
        } catch (\Exception $e) {
            return back()->with('error', "{$res}p failed: " . $e->getMessage());
        }
    }

    // ─── H.265 (HEVC) Conversion ──────────────────────────────────────────────

    public function convertH265($id)
    {
        $video  = Video::findOrFail($id);
        $input  = storage_path('app/public/uploads/' . $video->filename);
        $out    = 'h265_' . pathinfo($video->filename, PATHINFO_FILENAME) . '.mp4';
        $output = storage_path('app/public/uploads/' . $out);

        $cmd = sprintf(
            '"%s" -i "%s" -c:v libx265 -crf 28 -preset fast -c:a aac "%s"',
            $this->bins()['ffmpeg'], $input, $output
        );

        if ($this->exec($cmd)) {
            $video->converted_h265 = $out;
            $video->save();
            return back()->with('success', 'H.265 conversion done!');
        }
        return back()->with('error', 'H.265 conversion failed.');
    }

    // ─── Video Compression (CRF-based) ────────────────────────────────────────

    public function compress(Request $request, $id)
    {
        $request->validate(['crf' => 'required|integer|min:18|max:51']);

        $video  = Video::findOrFail($id);
        $input  = storage_path('app/public/uploads/' . $video->filename);
        $out    = 'compressed_' . $video->filename;
        $output = storage_path('app/public/uploads/' . $out);

        $cmd = sprintf(
            '"%s" -i "%s" -c:v libx264 -crf %d -preset fast -c:a aac "%s"',
            $this->bins()['ffmpeg'], $input, $request->crf, $output
        );

        if ($this->exec($cmd)) {
            $video->compressed_file = $out;
            $video->save();
            return back()->with('success', 'Video compressed! (CRF ' . $request->crf . ')');
        }
        return back()->with('error', 'Compression failed.');
    }

    // ─── HLS Streaming ────────────────────────────────────────────────────────

    public function createHls($id)
    {
        $video    = Video::findOrFail($id);
        $input    = storage_path('app/public/uploads/' . $video->filename);
        $hlsDir   = storage_path('app/public/uploads/hls_' . $video->id);
        $playlist = $hlsDir . '/playlist.m3u8';

        if (!is_dir($hlsDir)) mkdir($hlsDir, 0777, true);

        $cmd = sprintf(
            '"%s" -i "%s" -c:v libx264 -c:a aac -f hls -hls_time 10 -hls_playlist_type vod -hls_segment_filename "%s/segment_%%03d.ts" "%s"',
            $this->bins()['ffmpeg'], $input, $hlsDir, $playlist
        );

        if ($this->exec($cmd)) {
            $video->hls_path = 'uploads/hls_' . $video->id . '/playlist.m3u8';
            $video->save();
            return back()->with('success', 'HLS stream created!');
        }
        return back()->with('error', 'HLS creation failed.');
    }

    // ─── Trim ─────────────────────────────────────────────────────────────────

    public function trim($id, Request $request)
    {
        $request->validate(['start' => 'required|string', 'end' => 'required|string']);

        $video  = Video::findOrFail($id);
        $input  = storage_path('app/public/uploads/' . $video->filename);
        $out    = 'trimmed_' . $video->filename;
        $output = storage_path('app/public/uploads/' . $out);

        $cmd = sprintf(
            '"%s" -ss %s -to %s -i "%s" -c copy "%s"',
            $this->bins()['ffmpeg'],
            $request->start, $request->end, $input, $output
        );

        if ($this->exec($cmd)) {
            $video->trimmed_file = $out;
            $video->save();
            return back()->with('success', 'Video trimmed!');
        }
        return back()->with('error', 'Trimming failed.');
    }

    // ─── Merge ────────────────────────────────────────────────────────────────

    public function showMerge()
    {
        return view('videos.merge', ['videos' => Video::all()]);
    }

    public function merge(Request $request)
    {
        $request->validate(['video_ids' => 'required|array|min:2']);

        $videos = Video::whereIn('id', $request->video_ids)->get();
        if ($videos->count() < 2) return back()->with('error', 'Select at least 2 videos.');

        $listFile = storage_path('app/public/uploads/merge_list_' . time() . '.txt');
        file_put_contents($listFile, $videos->map(fn($v) =>
            "file '" . storage_path('app/public/uploads/' . $v->filename) . "'"
        )->implode("\n"));

        $out    = 'merged_' . time() . '_' . $videos->first()->filename;
        $output = storage_path('app/public/uploads/' . $out);

        $cmd = sprintf(
            '"%s" -f concat -safe 0 -i "%s" -c copy "%s"',
            $this->bins()['ffmpeg'], $listFile, $output
        );

        if ($this->exec($cmd)) {
            $first = $videos->first();
            $first->merged_file = $out;
            $first->save();
            @unlink($listFile);
            return back()->with('success', 'Videos merged!');
        }
        return back()->with('error', 'Merge failed.');
    }

    // ─── Audio Extraction (MP3) ───────────────────────────────────────────────

    public function extractAudio($id)
    {
        $video  = Video::findOrFail($id);
        $input  = storage_path('app/public/uploads/' . $video->filename);
        $out    = 'audio_' . pathinfo($video->filename, PATHINFO_FILENAME) . '.mp3';
        $output = storage_path('app/public/uploads/' . $out);

        $cmd = sprintf(
            '"%s" -i "%s" -vn -acodec libmp3lame -q:a 2 "%s"',
            $this->bins()['ffmpeg'], $input, $output
        );

        if ($this->exec($cmd)) {
            $video->audio_file = $out;
            $video->save();
            return back()->with('success', 'Audio extracted (MP3)!');
        }
        return back()->with('error', 'Audio extraction failed.');
    }

    // ─── Audio Format Conversion (WAV / OGG / FLAC → MP3) ────────────────────

    public function convertAudio(Request $request, $id)
    {
        $request->validate(['format' => 'required|in:wav,ogg,flac']);

        $video  = Video::findOrFail($id);
        $input  = storage_path('app/public/uploads/' . $video->filename);
        $fmt    = $request->format;
        $out    = 'audio_' . pathinfo($video->filename, PATHINFO_FILENAME) . '.' . $fmt;
        $output = storage_path('app/public/uploads/' . $out);

        $codecMap = ['wav' => 'pcm_s16le', 'ogg' => 'libvorbis', 'flac' => 'flac'];
        $cmd = sprintf(
            '"%s" -i "%s" -vn -acodec %s "%s"',
            $this->bins()['ffmpeg'], $input, $codecMap[$fmt], $output
        );

        if ($this->exec($cmd)) {
            $video->converted_audio_file = $out;
            $video->save();
            return back()->with('success', strtoupper($fmt) . ' audio created!');
        }
        return back()->with('error', 'Audio conversion failed.');
    }

    // ─── Thumbnail Gallery ────────────────────────────────────────────────────

    public function extractThumbnails($id)
    {
        $video    = Video::findOrFail($id);
        $input    = storage_path('app/public/uploads/' . $video->filename);
        $duration = max(1, (int) $video->original_duration_seconds);
        $interval = max(1, (int) ($duration / 6));
        $thumbs   = [];

        for ($i = 1; $i <= 6; $i++) {
            $time = $i * $interval;
            if ($time >= $duration) break;
            $name   = 'gallery_' . pathinfo($video->filename, PATHINFO_FILENAME) . '_' . $i . '.png';
            $output = storage_path('app/public/uploads/' . $name);
            $cmd    = sprintf('"%s" -ss %d -i "%s" -vframes 1 "%s"', $this->bins()['ffmpeg'], $time, $input, $output);
            if ($this->exec($cmd)) $thumbs[] = $name;
        }

        $video->thumbnail_gallery = $thumbs;
        $video->save();
        return back()->with('success', count($thumbs) . ' thumbnails extracted!');
    }

    // ─── GIF Creation ─────────────────────────────────────────────────────────

    public function createGif($id)
    {
        $video  = Video::findOrFail($id);
        $input  = storage_path('app/public/uploads/' . $video->filename);
        $output = storage_path('app/public/uploads/gif_' . pathinfo($video->filename, PATHINFO_FILENAME) . '.gif');

        $cmd = sprintf(
            '"%s" -ss 00:00:02 -t 3 -i "%s" -vf "fps=10,scale=480:-1:flags=lanczos,split[s0][s1];[s0]palettegen=max_colors=128[p];[s1][p]paletteuse" "%s"',
            $this->bins()['ffmpeg'], $input, $output
        );

        if ($this->exec($cmd)) return back()->with('success', 'GIF created!');
        return back()->with('error', 'GIF creation failed.');
    }

    // ─── Watermark ────────────────────────────────────────────────────────────

    public function addWatermark($id)
    {
        $video     = Video::findOrFail($id);
        $input     = storage_path('app/public/uploads/' . $video->filename);
        $watermark = public_path('watermark.png');
        $out       = 'watermarked_' . $video->filename;
        $output    = storage_path('app/public/uploads/' . $out);

        $cmd = sprintf(
            '"%s" -i "%s" -i "%s" -filter_complex "overlay=10:10" -c:a copy "%s"',
            $this->bins()['ffmpeg'], $input, $watermark, $output
        );

        if ($this->exec($cmd)) {
            $video->watermarked_file = $out;
            $video->save();
            return back()->with('success', 'Watermark added!');
        }
        return back()->with('error', 'Watermark failed.');
    }

    // ─── Subtitle Burn-in ─────────────────────────────────────────────────────

    public function addSubtitle(Request $request, $id)
    {
        $request->validate(['subtitle' => 'required|file|mimes:srt']);

        $video    = Video::findOrFail($id);
        $input    = storage_path('app/public/uploads/' . $video->filename);
        $subPath  = storage_path('app/public/' . $request->file('subtitle')->store('uploads', 'public'));
        $out      = 'subtitled_' . $video->filename;
        $output   = storage_path('app/public/uploads/' . $out);

        $escapedSub = str_replace(['\\', ':'], ['\\\\', '\\:'], $subPath);
        $cmd = sprintf(
            '"%s" -i "%s" -vf "subtitles=%s:force_style=\'Fontsize=24\'" -c:a copy "%s"',
            $this->bins()['ffmpeg'], $input, $escapedSub, $output
        );

        if ($this->exec($cmd)) {
            $video->subtitled_file = $out;
            $video->save();
            return back()->with('success', 'Subtitle burned in!');
        }
        return back()->with('error', 'Subtitle failed.');
    }

    // ─── Mute Video ───────────────────────────────────────────────────────────

    public function muteVideo($id)
    {
        $video  = Video::findOrFail($id);
        $input  = storage_path('app/public/uploads/' . $video->filename);
        $out    = 'muted_' . $video->filename;
        $output = storage_path('app/public/uploads/' . $out);

        $cmd = sprintf(
            '"%s" -i "%s" -an -c:v copy "%s"',
            $this->bins()['ffmpeg'], $input, $output
        );

        if ($this->exec($cmd)) {
            $video->is_muted   = true;
            $video->muted_file = $out;
            $video->save();
            return back()->with('success', 'Audio muted!');
        }
        return back()->with('error', 'Mute failed.');
    }
}
