<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merge Videos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">

<div class="container py-5">
    <h2>🔗 Merge Videos</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('videos.merge.process') }}" class="mt-4">
        @csrf
        <div class="row">
            @foreach($videos as $video)
            <div class="col-md-4 mb-3">
                <div class="card bg-secondary text-light">
                    <div class="card-body">
                        <h5>{{ $video->title }}</h5>
                        <img src="{{ asset('storage/uploads/'.$video->thumbnail) }}" class="img-fluid mb-2" style="height:150px; object-fit:cover;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="video_ids[]" value="{{ $video->id }}" id="v{{ $video->id }}">
                            <label class="form-check-label" for="v{{ $video->id }}">Select</label>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-primary mt-3">Merge Selected Videos</button>
        <a href="{{ route('videos.index') }}" class="btn btn-secondary mt-3">Cancel</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
