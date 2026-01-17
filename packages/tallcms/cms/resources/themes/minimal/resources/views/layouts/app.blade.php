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

    <!-- Theme Assets -->
    @themeVite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-screen bg-base-100 text-base-content">
    <!-- Navbar -->
    <div class="navbar bg-base-100 shadow-sm sticky top-0 z-50">
        <div class="navbar-start">
            <!-- Mobile Menu -->
            <div class="dropdown lg:hidden">
                <div tabindex="0" role="button" class="btn btn-ghost btn-circle">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </div>
                <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow-lg bg-base-100 rounded-box w-52">
                    <x-menu location="header" style="vertical" />
                </ul>
            </div>
            <!-- Logo -->
            <a href="{{ tallcms_home_url() }}" class="btn btn-ghost text-xl font-bold">
                {{ config('app.name') }}
            </a>
        </div>

        <!-- Desktop Menu -->
        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1">
                <x-menu location="header" style="horizontal" />
            </ul>
        </div>

        <div class="navbar-end gap-2">
            <!-- Theme Switcher -->
            {{-- Theme switcher: enable "all presets" mode to add theme-controller --}}
        </div>
    </div>

    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="footer footer-center bg-base-200 text-base-content p-10">
        <aside>
            <p class="font-bold text-lg">{{ config('app.name') }}</p>
            <p>Minimal theme for TallCMS</p>
        </aside>
        <nav>
            <x-menu location="footer" style="footer" />
        </nav>
        <aside>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </aside>
    </footer>

    @livewireScripts
</body>
</html>