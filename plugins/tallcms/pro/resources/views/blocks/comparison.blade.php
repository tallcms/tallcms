@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];

    $customProperties = collect([
        '--block-heading-color: ' . ($textPreset['heading'] ?? '#111827'),
        '--block-text-color: ' . ($textPreset['description'] ?? '#4b5563'),
        '--block-primary-color: ' . ($textPreset['link'] ?? '#2563eb'),
    ])->join('; ') . ';';

    $containerClasses = match($style ?? 'default') {
        'cards' => 'bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden',
        'minimal' => '',
        default => 'border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden',
    };
@endphp

<section
    class="pro-comparison-block py-12 sm:py-16"
    style="{{ $customProperties }}"
>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <div class="text-center mb-10">
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

        {{-- Comparison Table --}}
        @if(!empty($features))
            <div class="{{ $containerClasses }}">
                {{-- Header Row --}}
                <div class="grid grid-cols-3 bg-gray-50 dark:bg-gray-800 {{ $style === 'minimal' ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
                    <div class="px-4 py-4 font-semibold text-sm" style="color: var(--block-heading-color);">
                        Feature
                    </div>
                    <div class="px-4 py-4 text-center font-semibold text-sm" style="color: var(--block-heading-color);">
                        {{ $column_a_title }}
                    </div>
                    <div class="px-4 py-4 text-center font-semibold text-sm" style="color: var(--block-primary-color);">
                        {{ $column_b_title }}
                    </div>
                </div>

                {{-- Feature Rows --}}
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($features as $feature)
                        <div class="grid grid-cols-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <div class="px-4 py-3 text-sm" style="color: var(--block-text-color);">
                                {{ $feature['feature'] ?? '' }}
                            </div>
                            <div class="px-4 py-3 text-center">
                                @php $valueA = $feature['column_a'] ?? 'check'; @endphp
                                @if($valueA === 'check')
                                    <svg class="w-5 h-5 mx-auto text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                @elseif($valueA === 'x')
                                    <svg class="w-5 h-5 mx-auto text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                @elseif($valueA === 'partial')
                                    <svg class="w-5 h-5 mx-auto text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" />
                                    </svg>
                                @else
                                    <span class="text-sm" style="color: var(--block-text-color);">
                                        {{ $feature['column_a_text'] ?? '' }}
                                    </span>
                                @endif
                            </div>
                            <div class="px-4 py-3 text-center bg-primary-50/50 dark:bg-primary-900/10">
                                @php $valueB = $feature['column_b'] ?? 'check'; @endphp
                                @if($valueB === 'check')
                                    <svg class="w-5 h-5 mx-auto text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                @elseif($valueB === 'x')
                                    <svg class="w-5 h-5 mx-auto text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                @elseif($valueB === 'partial')
                                    <svg class="w-5 h-5 mx-auto text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" />
                                    </svg>
                                @else
                                    <span class="text-sm font-medium" style="color: var(--block-primary-color);">
                                        {{ $feature['column_b_text'] ?? '' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <p>No comparison features configured. Click to edit this block.</p>
            </div>
        @endif
    </div>
</section>
