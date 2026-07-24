<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .feature-section { border-top: 1px solid rgba(255,255,255,0.15); padding-top: 8px; margin-top: 8px; }
        .section-label { font-size: 11px; font-weight: 600; color: #ffc107; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
        .thumb-gallery img { width: 80px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid transparent; }
        .thumb-gallery img:hover { border-color: #0d6efd; }
    </style>
</head>
<body class="bg-dark text-light">
<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2>🎬 Video Library</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('videos.merge') }}" class="btn btn-outline-primary btn-sm">🔗 Merge Videos</a>
            <a href="{{ route('videos.upload.form') }}" class="btn btn-primary btn-sm">+ Upload Video</a>
        </div>
    </div>

    <form method="GET" action="{{ route('videos.index') }}" class="mb-4">
        <input type="text" name="search" value="{{ request('search') }}"
               class="form-control w-50" placeholder="Search videos...">
    </form>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($videos->isEmpty())
        <div class="text-center p-5 bg-secondary rounded">
            <p>No videos found</p>
            <a href="{{ route('videos.upload.form') }}" class="btn btn-primary">Upload First Video</a>
        </div>
    @else
    <div class="row g-4">
        @foreach($videos as $video)
        <div class="col-md-6 col-lg-4">
            <div class="card bg-secondary text-light h-100">

                {{-- Thumbnail --}}
                <img src="{{ asset('storage/uploads/'.$video->thumbnail) }}"
                     class="card-img-top" style="height:180px; object-fit:cover;"
                     onerror="this.src='https://placehold.co/400x180/333/fff?text=No+Thumb'">

                <div class="card-body p-3">
                    <h6 class="card-title mb-1">{{ $video->title }}</h6>
                    <small class="text-muted">⏱ {{ $video->duration ?? 'N/A' }} &nbsp; 📦 {{ $video->size ?? 'N/A' }}</small>

                    {{-- Player --}}
                    <video controls class="w-100 mt-2 rounded" style="max-height:160px;">
                        <source src="{{ asset('storage/uploads/'.$video->filename) }}">
                    </video>

                    {{-- Download / Delete --}}
                    <div class="d-flex justify-content-between mt-2">
                        <a href="{{ route('videos.download', $video->filename) }}" class="btn btn-success btn-sm">⬇ Original</a>
                        <form action="{{ route('videos.delete', $video->id) }}" method="POST"
                              onsubmit="return confirm('Delete this video and all its files?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">🗑 Delete</button>
                        </form>
                    </div>

                    {{-- ── 1. TRANSCODING ─────────────────────────────── --}}
                    <div class="feature-section">
                        <div class="section-label">📐 Transcode / Convert</div>
                        <div class="d-flex flex-wrap gap-1">
                            <a href="{{ route('videos.480', $video->id) }}" class="btn btn-sm btn-outline-light">480p</a>
                            <a href="{{ route('videos.720', $video->id) }}" class="btn btn-sm btn-outline-light">720p</a>
                            <a href="{{ route('videos.1080', $video->id) }}" class="btn btn-sm btn-outline-light">1080p</a>
                            <a href="{{ route('videos.h265', $video->id) }}" class="btn btn-sm btn-outline-warning">H.265</a>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            @if($video->converted_480)
                                <a href="{{ route('videos.download', $video->converted_480) }}" class="btn btn-sm btn-success">⬇ 480p</a>
                            @endif
                            @if($video->converted_720)
                                <a href="{{ route('videos.download', $video->converted_720) }}" class="btn btn-sm btn-success">⬇ 720p</a>
                            @endif
                            @if($video->converted_1080)
                                <a href="{{ route('videos.download', $video->converted_1080) }}" class="btn btn-sm btn-success">⬇ 1080p</a>
                            @endif
                            @if($video->converted_h265)
                                <a href="{{ route('videos.download', $video->converted_h265) }}" class="btn btn-sm btn-warning">⬇ H.265</a>
                            @endif
                        </div>
                    </div>

                    {{-- ── 2. COMPRESSION ──────────────────────────────── --}}
                    <div class="feature-section">
                        <div class="section-label">🗜 Compress (CRF)</div>
                        <form action="{{ route('videos.compress', $video->id) }}" method="POST" class="d-flex gap-1 align-items-center">
                            @csrf
                            <input type="number" name="crf" value="28" min="18" max="51"
                                   class="form-control form-control-sm" style="width:70px;" title="18=best quality, 51=smallest size">
                            <button class="btn btn-sm btn-outline-warning">Compress</button>
                        </form>
                        <small class="text-muted" style="font-size:10px;">18=best quality · 51=smallest file</small>
                        @if($video->compressed_file)
                            <a href="{{ route('videos.download', $video->compressed_file) }}" class="btn btn-sm btn-warning mt-1">⬇ Compressed</a>
                        @endif
                    </div>

                    {{-- ── 3. HLS STREAMING ────────────────────────────── --}}
                    <div class="feature-section">
                        <div class="section-label">📡 HLS Streaming</div>
                        <a href="{{ route('videos.hls', $video->id) }}" class="btn btn-sm btn-outline-info">Create HLS (.m3u8)</a>
                        @if($video->hls_path)
                            <a href="{{ asset('storage/' . $video->hls_path) }}" class="btn btn-sm btn-info" target="_blank">▶ HLS Playlist</a>
                        @endif
                    </div>

                    {{-- ── 4. TRIM ──────────────────────────────────────── --}}
                    <div class="feature-section">
                        <div class="section-label">✂ Trim / Clip</div>
                        <form action="{{ route('videos.trim', $video->id) }}" method="POST" class="d-flex gap-1">
                            @csrf
                            <input type="text" name="start" placeholder="00:00:05" class="form-control form-control-sm" required>
                            <input type="text" name="end"   placeholder="00:00:30" class="form-control form-control-sm" required>
                            <button class="btn btn-sm btn-outline-warning">Cut</button>
                        </form>
                        @if($video->trimmed_file)
                            <a href="{{ route('videos.download', $video->trimmed_file) }}" class="btn btn-sm btn-success mt-1">⬇ Trimmed</a>
                        @endif
                    </div>

                    {{-- ── 5. AUDIO ─────────────────────────────────────── --}}
                    <div class="feature-section">
                        <div class="section-label">🎵 Audio</div>
                        <div class="d-flex flex-wrap gap-1">
                            <a href="{{ route('videos.audio', $video->id) }}" class="btn btn-sm btn-outline-success">Extract MP3</a>
                            @if($video->audio_file)
                                <a href="{{ route('videos.download', $video->audio_file) }}" class="btn btn-sm btn-success">⬇ MP3</a>
                            @endif
                        </div>
                        {{-- Audio Format Conversion --}}
                        <form action="{{ route('videos.audio.convert', $video->id) }}" method="POST" class="d-flex gap-1 mt-1">
                            @csrf
                            <select name="format" class="form-select form-select-sm" style="width:90px;">
                                <option value="wav">WAV</option>
                                <option value="ogg">OGG</option>
                                <option value="flac">FLAC</option>
                            </select>
                            <button class="btn btn-sm btn-outline-success">Convert</button>
                        </form>
                        @if($video->converted_audio_file)
                            <a href="{{ route('videos.download', $video->converted_audio_file) }}" class="btn btn-sm btn-success mt-1">⬇ {{ strtoupper(pathinfo($video->converted_audio_file, PATHINFO_EXTENSION)) }}</a>
                        @endif
                        {{-- Mute --}}
                        <div class="mt-1">
                            <a href="{{ route('videos.mute', $video->id) }}" class="btn btn-sm btn-outline-danger">🔇 Mute Video</a>
                            @if($video->muted_file)
                                <a href="{{ route('videos.download', $video->muted_file) }}" class="btn btn-sm btn-danger">⬇ Muted</a>
                            @endif
                        </div>
                    </div>

                    {{-- ── 6. THUMBNAIL GALLERY ─────────────────────────── --}}
                    <div class="feature-section">
                        <div class="section-label">🖼 Thumbnail Gallery</div>
                        <a href="{{ route('videos.thumbnails', $video->id) }}" class="btn btn-sm btn-outline-light">Generate Thumbnails</a>
                        <a href="{{ route('videos.gif', $video->id) }}" class="btn btn-sm btn-outline-light ms-1">Create GIF</a>
                        @if($video->thumbnail_gallery && count($video->thumbnail_gallery))
                            <div class="thumb-gallery d-flex flex-wrap gap-1 mt-2">
                                @foreach($video->thumbnail_gallery as $thumb)
                                    <img src="{{ asset('storage/uploads/'.$thumb) }}"
                                         title="{{ $thumb }}"
                                         onerror="this.style.display='none'">
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- ── 7. WATERMARK ─────────────────────────────────── --}}
                    <div class="feature-section">
                        <div class="section-label">💧 Watermark / Logo</div>
                        <a href="{{ route('videos.watermark', $video->id) }}" class="btn btn-sm btn-outline-warning">Burn Watermark</a>
                        @if($video->watermarked_file)
                            <a href="{{ route('videos.download', $video->watermarked_file) }}" class="btn btn-sm btn-warning">⬇ Watermarked</a>
                        @endif
                    </div>

                    {{-- ── 8. SUBTITLES ─────────────────────────────────── --}}
                    <div class="feature-section">
                        <div class="section-label">CC Subtitles (SRT Burn-in)</div>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#subModal{{ $video->id }}">
                            Upload & Burn SRT
                        </button>
                        @if($video->subtitled_file)
                            <a href="{{ route('videos.download', $video->subtitled_file) }}" class="btn btn-sm btn-secondary">⬇ Subtitled</a>
                        @endif
                    </div>

                </div>{{-- card-body --}}
            </div>{{-- card --}}

            {{-- Subtitle Modal --}}
            <div class="modal fade" id="subModal{{ $video->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <form action="{{ route('videos.subtitle', $video->id) }}" method="POST"
                          enctype="multipart/form-data" class="modal-content bg-dark text-light">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Burn Subtitle – {{ $video->title }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Upload .srt file</label>
                            <input type="file" name="subtitle" class="form-control" accept=".srt" required>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Burn Subtitle</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>{{-- col --}}
        @endforeach
    </div>
    @endif

    <p class="text-center text-muted mt-5 small">Laravel 12 • FFMpeg Video Processing</p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
