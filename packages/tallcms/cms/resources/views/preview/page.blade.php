<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {{ $page->title }}</title>

    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    {{-- Load Vite assets (Tailwind CSS, DaisyUI, block styles) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Fallback for plugin mode without Vite --}}
    @if(file_exists(public_path('vendor/tallcms/tallcms.css')))
        <link rel="stylesheet" href="{{ asset('vendor/tallcms/tallcms.css') }}">
    @endif

    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            margin: 0;
            padding: 0;
        }
        .preview-container {
            background: #f8fafc;
            min-height: 100vh;
        }
        .preview-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.8);
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .preview-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .device-selector {
            display: flex;
            background: #f9fafb;
            border-radius: 8px;
            padding: 4px;
        }
        .device-btn {
            padding: 8px 16px;
            border: none;
            background: transparent;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            color: #6b7280;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .device-btn.active {
            background: white;
            color: #1f2937;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .device-btn:hover {
            color: #1f2937;
        }
        .preview-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #6b7280;
            font-size: 14px;
        }
        .preview-viewport {
            margin: 1rem auto;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            background: white;
        }
        .viewport-desktop { width: 100%; max-width: 1200px; }
        .viewport-tablet { width: 768px; max-width: 90vw; }
        .viewport-mobile { width: 375px; max-width: 90vw; }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="preview-header">
            <div class="preview-controls">
                <div class="preview-info">
                    <span><strong>Preview:</strong> {{ $page->title }}</span>
                </div>

                <div class="device-selector">
                    <button class="device-btn {{ $device === 'desktop' ? 'active' : '' }}"
                            onclick="switchDevice('desktop')">Desktop</button>
                    <button class="device-btn {{ $device === 'tablet' ? 'active' : '' }}"
                            onclick="switchDevice('tablet')">Tablet</button>
                    <button class="device-btn {{ $device === 'mobile' ? 'active' : '' }}"
                            onclick="switchDevice('mobile')">Mobile</button>
                </div>
            </div>
        </div>

        <div id="preview-viewport" class="preview-viewport viewport-{{ $device }}">
            <div class="page-content">
                <div class="max-w-7xl mx-auto px-4 py-8">
                    {!! $renderedContent !!}
                </div>
            </div>
        </div>
    </div>

    @livewireScripts

    <script>
        function switchDevice(device) {
            const url = new URL(window.location);
            url.searchParams.set('device', device);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
