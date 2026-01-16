@php
    use TallCms\Cms\Services\HtmlSanitizerService;

    // Content width classes
    $contentWidthClasses = match($content_width ?? 'normal') {
        'narrow' => 'max-w-2xl mx-auto',
        'normal' => 'max-w-4xl mx-auto',
        'wide' => 'max-w-6xl mx-auto',
        default => 'max-w-4xl mx-auto'
    };

    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');
@endphp

<article class="w-full px-4 sm:px-6 lg:px-8 {{ $sectionPadding }} {{ $background ?? 'bg-base-100' }}">
    <div class="{{ $contentWidthClasses }}">

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
