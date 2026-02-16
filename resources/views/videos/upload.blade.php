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
