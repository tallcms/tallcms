<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <script>
        // Apply saved theme immediately to prevent flash of wrong theme
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
            } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
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
    @if(supports_theme_controller())
    <!-- Theme Drawer Wrapper -->
    <div class="drawer drawer-end">
        <input id="theme-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content">
    @endif
            <!-- Navbar -->
            @if(function_exists('mega_menu_header_active') && mega_menu_header_active('header'))
                {{-- Mega Menu Full Header --}}
                <x-dynamic-component component="mega-menu::header" location="header" />
            @else
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
                    @if(supports_theme_controller())
                        @include('theme.talldaisy::components.theme-switcher')
                    @endif
                </div>
            </div>
            @endif

            <!-- Main Content -->
            <main>
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="footer footer-center bg-base-200 text-base-content p-10">
                <aside>
                    <p class="font-bold text-lg">{{ config('app.name') }}</p>
                    <p>A modern content management system built on the TALL stack.</p>
                </aside>
                <nav>
                    <x-menu location="footer" style="footer" />
                </nav>
                <aside>
                    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                </aside>
            </footer>
    @if(supports_theme_controller())
        </div>

        <!-- Theme Drawer Sidebar -->
        <div class="drawer-side z-[60]">
            <label for="theme-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
            <div class="bg-base-200 min-h-full w-80 p-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold">Choose Theme</h2>
                    <label for="theme-drawer" class="btn btn-sm btn-circle btn-ghost">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </label>
                </div>
                <ul class="menu w-full p-0 gap-1" id="theme-list">
                    @foreach(daisyui_presets() as $preset)
                        <li>
                            <button type="button"
                                    class="btn btn-sm btn-block btn-ghost justify-start theme-btn"
                                    data-theme-value="{{ $preset }}">
                                <span class="badge badge-sm" data-theme="{{ $preset }}">
                                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                                    <span class="w-2 h-2 rounded-full bg-secondary"></span>
                                    <span class="w-2 h-2 rounded-full bg-accent"></span>
                                </span>
                                {{ ucfirst($preset) }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <script>
        // Theme switcher - explicit control without relying on theme-controller class
        (function() {
            function setTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                // Update active button styling
                document.querySelectorAll('.theme-btn').forEach(btn => {
                    btn.classList.toggle('btn-active', btn.dataset.themeValue === theme);
                });
                // Close the drawer after selection
                const drawer = document.getElementById('theme-drawer');
                if (drawer) drawer.checked = false;
            }

            // Initialize: mark current theme as active
            const savedTheme = localStorage.getItem('theme') ||
                              document.documentElement.getAttribute('data-theme') ||
                              'light';
            document.querySelectorAll('.theme-btn').forEach(btn => {
                btn.classList.toggle('btn-active', btn.dataset.themeValue === savedTheme);
                btn.addEventListener('click', function() {
                    setTheme(this.dataset.themeValue);
                });
            });
        })();
    </script>

    @livewireScripts
</body>
</html>
