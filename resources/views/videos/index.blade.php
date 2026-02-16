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
