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
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ url('/') }}" class="text-xl font-bold text-gray-900">
                            {{ config('app.name') }}
                        </a>
                    </div>
                    
                    <div class="flex items-center space-x-6">
                        <a href="{{ route('cms.pages.index') }}" class="text-gray-700 hover:text-gray-900 transition-colors">
                            Pages
                        </a>
                        {{-- Blog/Posts will be displayed via custom blocks within pages --}}
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="py-8">
            {{ $slot }}
        </main>
        
        <!-- Footer -->
        <footer class="bg-white border-t mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="text-center text-gray-600">
                    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                    <p class="text-sm mt-2">Powered by TallCMS</p>
                </div>
            </div>
        </footer>
    </div>
    
    @livewireScripts
</body>
</html>