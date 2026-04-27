<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Library</title>

    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">

<div class="container py-5">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h2>🎬 Video Library</h2>

        <a href="{{ route('videos.upload.form') }}" class="btn btn-primary">
            + Upload Video
        </a>
    </div>

    <!-- Search -->
    <form method="GET" action="{{ route('videos.index') }}" class="mb-4">
        <input type="text" name="search" value="{{ request('search') }}"
               class="form-control w-50"
               placeholder="Search videos...">
    </form>

    <!-- Success -->
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Empty -->
    @if($videos->isEmpty())
        <div class="text-center p-5 bg-secondary rounded">
            <p>No videos found</p>
        </div>
    @else

    <!-- Grid -->
    <div class="row g-4">

        @foreach($videos as $video)
        <div class="col-md-4">

            <div class="card bg-secondary text-light">

                <!-- Thumbnail -->
                <img src="{{ asset('storage/uploads/'.$video->thumbnail) }}"
                     class="card-img-top"
                     style="height:200px; object-fit:cover;">

                <div class="card-body">

                    <!-- Title -->
                    <h5 class="card-title">{{ $video->title }}</h5>

                    <!-- Video -->
                    <video controls class="w-100 mb-2">
                        <source src="{{ asset('storage/uploads/'.$video->filename) }}">
                    </video>

                    <!-- Meta -->
                    <small>
                        ⏱ {{ $video->duration ?? 'N/A' }} <br>
                        📦 {{ $video->size ?? 'N/A' }}
                    </small>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between mt-3">

                        <a href="{{ route('videos.download', $video->filename) }}"
                           class="btn btn-success btn-sm">
                            Download
                        </a>

                        <form action="{{ route('videos.delete', $video->id) }}"
                              method="POST"
                              onsubmit="return confirm('Delete this video?');">
                            @csrf
                            @method('DELETE')

                            <button class="btn btn-danger btn-sm">
                                Delete
                            </button>
                        </form>

                    </div>

                </div>

            </div>

        </div>
        @endforeach

    </div>
    @endif

</div>

</body>
</html>