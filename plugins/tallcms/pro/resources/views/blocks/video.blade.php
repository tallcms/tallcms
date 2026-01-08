@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];

    $customProperties = collect([
        '--block-heading-color: ' . ($textPreset['heading'] ?? '#111827'),
        '--block-text-color: ' . ($textPreset['description'] ?? '#4b5563'),
    ])->join('; ') . ';';

    // Parse YouTube URL to get video ID
    $youtubeId = null;
    if ($source === 'youtube' && !empty($youtube_url)) {
        // Match various YouTube URL formats
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $youtube_url, $matches)) {
            $youtubeId = $matches[1];
        }
    }

    // Parse Vimeo URL to get video ID
    $vimeoId = null;
    if ($source === 'vimeo' && !empty($vimeo_url)) {
        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $vimeo_url, $matches)) {
            $vimeoId = $matches[1];
        }
    }

    // Aspect ratio padding calculation
    $aspectPadding = match($aspect_ratio ?? '16:9') {
        '4:3' => '75%',
        '21:9' => '42.86%',
        '1:1' => '100%',
        default => '56.25%', // 16:9
    };

    // Max width classes
    $widthClass = match($width ?? 'xl') {
        'full' => 'max-w-full',
        'lg' => 'max-w-4xl',
        'md' => 'max-w-3xl',
        default => 'max-w-5xl', // xl
    };

    // Build embed params
    $embedParams = [];
    if ($autoplay ?? false) $embedParams[] = 'autoplay=1';
    if ($muted ?? false) $embedParams[] = 'mute=1';
    if ($loop ?? false) $embedParams[] = 'loop=1';
    if (!($controls ?? true)) $embedParams[] = 'controls=0';

    // YouTube-specific params (loop requires playlist param with video ID)
    $youtubeSpecific = ['rel=0', 'modestbranding=1'];
    if (($loop ?? false) && $youtubeId) {
        $youtubeSpecific[] = 'playlist=' . $youtubeId;
    }
    $youtubeParams = implode('&', array_merge($embedParams, $youtubeSpecific));
    $vimeoParams = implode('&', $embedParams);
@endphp

<section
    class="pro-video-block py-12 sm:py-16"
    style="{{ $customProperties }}"
>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <div class="text-center mb-8">
                @if(!empty($heading))
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight" style="color: var(--block-heading-color);">
                        {{ $heading }}
                    </h2>
                @endif
                @if(!empty($subheading))
                    <p class="mt-3 text-lg max-w-2xl mx-auto" style="color: var(--block-text-color);">
                        {{ $subheading }}
                    </p>
                @endif
            </div>
        @endif

        {{-- Video Container --}}
        <div class="{{ $widthClass }} mx-auto">
            @if($source === 'youtube' && $youtubeId)
                {{-- YouTube Embed --}}
                <div class="relative w-full overflow-hidden rounded-lg shadow-lg bg-black" style="padding-bottom: {{ $aspectPadding }};">
                    <iframe
                        class="absolute inset-0 w-full h-full"
                        src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}?{{ $youtubeParams }}"
                        title="{{ $heading ?: 'YouTube video' }}"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                        loading="lazy"
                    ></iframe>
                </div>

            @elseif($source === 'vimeo' && $vimeoId)
                {{-- Vimeo Embed --}}
                <div class="relative w-full overflow-hidden rounded-lg shadow-lg bg-black" style="padding-bottom: {{ $aspectPadding }};">
                    <iframe
                        class="absolute inset-0 w-full h-full"
                        src="https://player.vimeo.com/video/{{ $vimeoId }}?{{ $vimeoParams }}"
                        title="{{ $heading ?: 'Vimeo video' }}"
                        frameborder="0"
                        allow="autoplay; fullscreen; picture-in-picture"
                        allowfullscreen
                        loading="lazy"
                    ></iframe>
                </div>

            @elseif($source === 'self_hosted' && !empty($video_url))
                {{-- Self-Hosted Video --}}
                <div class="relative w-full overflow-hidden rounded-lg shadow-lg bg-black" style="padding-bottom: {{ $aspectPadding }};">
                    <video
                        class="absolute inset-0 w-full h-full object-contain"
                        @if(!empty($poster_url)) poster="{{ $poster_url }}" @endif
                        @if($autoplay ?? false) autoplay @endif
                        @if($muted ?? false) muted @endif
                        @if($loop ?? false) loop @endif
                        @if($controls ?? true) controls @endif
                        playsinline
                    >
                        <source src="{{ $video_url }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>

            @else
                {{-- No Video Configured --}}
                <div class="relative w-full overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800" style="padding-bottom: {{ $aspectPadding }};">
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                        <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <p class="text-sm">No video configured. Click to edit this block.</p>
                    </div>
                </div>
            @endif

            {{-- Caption --}}
            @if(!empty($caption))
                <p class="mt-4 text-center text-sm" style="color: var(--block-text-color);">
                    {{ $caption }}
                </p>
            @endif
        </div>
    </div>
</section>
