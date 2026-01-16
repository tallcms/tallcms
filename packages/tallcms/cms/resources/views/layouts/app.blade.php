<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? config('app.name') }}</title>

    @if(isset($description))
        <meta name="description" content="{{ $description }}">
    @endif

    @if(isset($featuredImage))
        <meta property="og:image" content="{{ Storage::url($featuredImage) }}">
        <meta property="twitter:image" content="{{ Storage::url($featuredImage) }}">
    @endif

    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    <meta property="og:description" content="{{ $description ?? '' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ request()->url() }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? config('app.name') }}">
    <meta name="twitter:description" content="{{ $description ?? '' }}">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- TallCMS Package Styles -->
    @if(file_exists(public_path('vendor/tallcms/tallcms.css')))
        <link rel="stylesheet" href="{{ asset('vendor/tallcms/tallcms.css') }}">
    @endif

    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
    </style>
</head>
<body class="bg-white">
    <div class="min-h-screen">
        <!-- Navigation -->
        @php
            // Build CMS root URL respecting plugin mode prefix
            $cmsPrefix = config('tallcms.plugin_mode.routes_prefix');
            $cmsRootUrl = $cmsPrefix ? url($cmsPrefix) : url('/');
        @endphp
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ $cmsRootUrl }}" class="text-xl font-bold text-gray-900">
                            {{ config('app.name') }}
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main>
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="bg-gray-50 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="text-center text-gray-500 text-sm">
                    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>

    @livewireScripts

    @if(file_exists(public_path('vendor/tallcms/tallcms.js')))
        <script src="{{ asset('vendor/tallcms/tallcms.js') }}"></script>
    @endif
</body>
</html>
