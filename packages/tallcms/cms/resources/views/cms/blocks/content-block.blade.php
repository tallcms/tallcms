@php
    use TallCms\Cms\Services\HtmlSanitizerService;

    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');

    // Build base classes (padding now handled by $contentPadding variable from block)
    $baseClasses = "w-full {$sectionPadding} " . ($background ?? 'bg-base-100');

    // Add custom classes if provided
    $allClasses = trim($baseClasses . ' ' . ($css_classes ?? ''));
@endphp

<article @if($anchor_id ?? null) id="{{ $anchor_id }}" @endif class="{{ $allClasses }}">
    <div class="{{ $contentWidthClass ?? 'max-w-6xl mx-auto' }} {{ $contentPadding ?? 'px-4 sm:px-6 lg:px-8' }}">

        @if(($title ?? null) || ($subtitle ?? null))
            <header class="mb-8 sm:mb-10">
                @if($title ?? null)
                    <{{ $heading_level ?? 'h2' }} class="text-3xl sm:text-4xl font-bold leading-tight mb-4 text-base-content">
                        {{ $title }}
                    </{{ $heading_level ?? 'h2' }}>
                @endif

                @if($subtitle ?? null)
                    @php
                        $subtitleLevel = match($heading_level ?? 'h2') {
                            'h2' => 'h3',
                            'h3' => 'h4',
                            'h4' => 'h5',
                            default => 'h3'
                        };
                    @endphp
                    <{{ $subtitleLevel }} class="text-lg sm:text-xl mt-2 text-base-content/70 font-normal">
                        {{ $subtitle }}
                    </{{ $subtitleLevel }}>
                @endif
            </header>
        @endif

        @if($body ?? null)
            <div class="prose prose-lg max-w-none text-base-content">
                {!! HtmlSanitizerService::sanitizeTipTapContent($body) !!}
            </div>
        @endif

    </div>
</article>
