<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <h2 class="text-center mb-4">🎬 Upload New Video</h2>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card bg-secondary text-light">
                <div class="card-body">
                    <form method="POST" action="{{ route('videos.upload') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Video Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Enter video title..." required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Select Video File</label>
                            <input type="file" name="video" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Upload Video</button>
                    </form>
                </div>
            </div>

            <p class="text-center text-gray-400 text-xs mt-4">
                Laravel 12 • FFMpeg Video Processing
            </p>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
