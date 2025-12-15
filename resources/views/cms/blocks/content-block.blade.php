@php
    use App\Services\HtmlSanitizerService;
    
    // Get current theme presets that work in both admin and frontend
    $textPresets = theme_text_presets();
    
    // Resolve text colors from theme system with fallbacks
    $textPreset = $textPresets['primary'] ?? [
        'heading' => '#111827',
        'description' => '#374151'
    ];
    
    // Content width classes
    $contentWidthClasses = match($content_width) {
        'narrow' => 'max-w-2xl mx-auto',
        'normal' => 'max-w-4xl mx-auto', 
        'wide' => 'max-w-6xl mx-auto',
        default => 'max-w-4xl mx-auto'
    };
    
    // Simple spacing system using Tailwind classes
    $sectionClasses = collect([
        'w-full',
        'px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16',
        $first_section ? 'pt-32 sm:pt-36 pb-12 sm:pb-16' : 'py-12 sm:py-16'
    ])->filter()->join(' ');
    
    // Generate unique ID for scoped styling
    $blockId = 'content-block-' . uniqid();
@endphp

<article class="{{ $sectionClasses }}" id="{{ $blockId }}">
    
    <div class="{{ $contentWidthClasses }}">
        
        @if($title || $subtitle)
            <header class="mb-8 sm:mb-10">
                @if($title)
                    <{{ $heading_level }} class="text-3xl sm:text-4xl font-bold leading-tight mb-4" 
                        style="color: {{ $textPreset['heading'] }} !important; font-size: clamp(1.875rem, 4vw, 2.25rem) !important; font-weight: bold !important; line-height: 1.2 !important;">
                        {{ $title }}
                    </{{ $heading_level }}>
                @endif
                
                @if($subtitle)
                    @php
                        $subtitleLevel = match($heading_level) {
                            'h2' => 'h3',
                            'h3' => 'h4', 
                            'h4' => 'h5',
                            default => 'h3'
                        };
                    @endphp
                    <{{ $subtitleLevel }} class="text-lg sm:text-xl mt-2" 
                        style="color: {{ $textPreset['description'] }} !important; font-size: 1.25rem !important; font-weight: normal !important; line-height: 1.4 !important; margin-top: 0.75rem !important;">
                        {{ $subtitle }}
                    </{{ $subtitleLevel }}>
                @endif
            </header>
        @endif
        
        @if($body)
            <div class="content-block-body">
                {!! HtmlSanitizerService::sanitizeTipTapContent($body) !!}
            </div>
            
            <style>
                /* Standalone styling that works without Tailwind prose - with !important for admin override */
                .content-block-body {
                    font-size: 1rem !important;
                    line-height: 1.7 !important;
                    color: {{ $textPreset['description'] }} !important;
                }
                
                .content-block-body p {
                    margin: 0 0 1rem 0 !important;
                    line-height: 1.7 !important;
                    color: {{ $textPreset['description'] }} !important;
                }
                
                .content-block-body h1, .content-block-body h2, .content-block-body h3, 
                .content-block-body h4, .content-block-body h5, .content-block-body h6 {
                    color: {{ $textPreset['heading'] }} !important;
                    font-weight: 600 !important;
                    margin: 1.5rem 0 0.75rem 0 !important;
                    line-height: 1.3 !important;
                }
                
                .content-block-body h1 { font-size: 2rem !important; }
                .content-block-body h2 { font-size: 1.75rem !important; }
                .content-block-body h3 { font-size: 1.5rem !important; }
                .content-block-body h4 { font-size: 1.25rem !important; }
                .content-block-body h5 { font-size: 1.125rem !important; }
                .content-block-body h6 { font-size: 1rem !important; }
                
                .content-block-body ul, .content-block-body ol {
                    margin: 1rem 0;
                    padding-left: 1.5rem;
                    color: {{ $textPreset['description'] }};
                }
                
                .content-block-body li {
                    margin: 0.25rem 0;
                    line-height: 1.6;
                }
                
                .content-block-body blockquote {
                    margin: 1.5rem 0;
                    padding: 0.75rem 1rem;
                    border-left: 4px solid {{ $textPreset['link'] ?? '#2563eb' }};
                    background-color: #f9fafb;
                    border-radius: 0 0.375rem 0.375rem 0;
                    font-style: normal;
                    color: {{ $textPreset['description'] }};
                }
                
                .content-block-body strong {
                    font-weight: 600;
                    color: {{ $textPreset['heading'] }};
                }
                
                .content-block-body em {
                    font-style: italic;
                }
                
                .content-block-body a {
                    color: {{ $textPreset['link'] ?? '#2563eb' }};
                    text-decoration: none;
                }
                
                .content-block-body a:hover {
                    color: {{ $textPreset['link_hover'] ?? '#1d4ed8' }};
                    text-decoration: underline;
                }
                
                .content-block-body table {
                    width: 100%;
                    margin: 1rem 0;
                    border-collapse: collapse;
                }
                
                .content-block-body th, .content-block-body td {
                    padding: 0.5rem;
                    border: 1px solid #e5e7eb;
                    text-align: left;
                }
                
                .content-block-body th {
                    background-color: #f9fafb;
                    font-weight: 600;
                    color: {{ $textPreset['heading'] }};
                }
                
                .content-block-body code {
                    background-color: #f1f5f9;
                    padding: 0.125rem 0.25rem;
                    border-radius: 0.25rem;
                    font-size: 0.875rem;
                    color: #334155;
                }
                
                .content-block-body pre {
                    background-color: #1e293b;
                    color: #f1f5f9;
                    padding: 1rem;
                    border-radius: 0.5rem;
                    margin: 1rem 0;
                    overflow-x: auto;
                }
                
                .content-block-body pre code {
                    background-color: transparent;
                    padding: 0;
                    color: inherit;
                }
            </style>
        @endif
        
    </div>
</article>