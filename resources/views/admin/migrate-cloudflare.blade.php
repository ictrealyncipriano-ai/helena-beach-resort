<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Migrate to Cloudflare — Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-2xl w-full">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Migrate Images to Cloudflare R2</h1>
        <p class="text-gray-600 mb-6">Copy all existing gallery and cottage images from Supabase Storage to Cloudflare R2.</p>

        @if (!isset($exitCode))
            <form method="POST" action="{{ route('admin.migrate-cloudflare.run') }}">
                @csrf
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <p class="text-sm text-yellow-700">
                        This may take a moment depending on how many files need to be copied.
                        Files already on Cloudflare will be skipped.
                    </p>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-colors">
                    Start Migration
                </button>
            </form>
        @else
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <pre class="text-sm text-gray-700 whitespace-pre-wrap font-mono">{{ $output }}</pre>
            </div>
            @if ($exitCode === 0)
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                    <p class="text-sm text-green-700 font-medium">Migration completed successfully.</p>
                </div>
            @else
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                    <p class="text-sm text-red-700 font-medium">Migration failed.</p>
                </div>
            @endif
            <div class="flex gap-3">
                <a href="{{ route('admin.migrate-cloudflare') }}" class="flex-1 text-center bg-gray-500 hover:bg-gray-600 text-white font-medium py-3 px-4 rounded-lg transition-colors">
                    Run Again
                </a>
                <a href="/admin/galleries" class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-colors">
                    Back to Galleries
                </a>
            </div>
        @endif
    </div>
</body>
</html>