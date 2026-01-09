@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];
    $isPreview = $is_preview ?? false;

    $headingColor = $textPreset['heading'] ?? '#111827';
    $textColor = $textPreset['description'] ?? '#4b5563';
    $primaryColor = $textPreset['link'] ?? '#2563eb';

    $containerStyle = match($style ?? 'default') {
        'cards' => 'background: #fff; border-radius: 0.75rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); overflow: hidden;',
        'minimal' => '',
        default => 'border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden;',
    };
@endphp

<section style="padding: 3rem 0;">
    <div style="max-width: 56rem; margin: 0 auto; padding: 0 1rem;">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <div style="text-align: center; margin-bottom: 2.5rem;">
                @if(!empty($heading))
                    <h2 style="font-size: 1.5rem; font-weight: 700; color: {{ $headingColor }};">
                        {{ $heading }}
                    </h2>
                @endif
                @if(!empty($subheading))
                    <p style="margin-top: 0.75rem; font-size: 1.125rem; color: {{ $textColor }};">
                        {{ $subheading }}
                    </p>
                @endif
            </div>
        @endif

        {{-- Comparison Table --}}
        @if(!empty($features))
            <div style="{{ $containerStyle }}">
                {{-- Header Row --}}
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); background: #f9fafb; {{ $style === 'minimal' ? 'border-bottom: 1px solid #e5e7eb;' : '' }}">
                    <div style="padding: 1rem; font-weight: 600; font-size: 0.875rem; color: {{ $headingColor }};">
                        Feature
                    </div>
                    <div style="padding: 1rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: {{ $headingColor }};">
                        {{ $column_a_title }}
                    </div>
                    <div style="padding: 1rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: {{ $primaryColor }};">
                        {{ $column_b_title }}
                    </div>
                </div>

                {{-- Feature Rows --}}
                <div>
                    @foreach($features as $index => $feature)
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); {{ $index < count($features) - 1 ? 'border-bottom: 1px solid #e5e7eb;' : '' }}">
                            <div style="padding: 0.75rem 1rem; font-size: 0.875rem; color: {{ $textColor }};">
                                {{ $feature['feature'] ?? '' }}
                            </div>
                            <div style="padding: 0.75rem 1rem; text-align: center; display: flex; align-items: center; justify-content: center;">
                                @php $valueA = $feature['column_a'] ?? 'check'; @endphp
                                @if($valueA === 'check')
                                    <svg style="width: 1.25rem; height: 1.25rem; color: #22c55e;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                @elseif($valueA === 'x')
                                    <svg style="width: 1.25rem; height: 1.25rem; color: #d1d5db;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                @elseif($valueA === 'partial')
                                    <svg style="width: 1.25rem; height: 1.25rem; color: #eab308;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" />
                                    </svg>
                                @else
                                    <span style="font-size: 0.875rem; color: {{ $textColor }};">
                                        {{ $feature['column_a_text'] ?? '' }}
                                    </span>
                                @endif
                            </div>
                            <div style="padding: 0.75rem 1rem; text-align: center; background: rgba(59, 130, 246, 0.05); display: flex; align-items: center; justify-content: center;">
                                @php $valueB = $feature['column_b'] ?? 'check'; @endphp
                                @if($valueB === 'check')
                                    <svg style="width: 1.25rem; height: 1.25rem; color: #22c55e;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                @elseif($valueB === 'x')
                                    <svg style="width: 1.25rem; height: 1.25rem; color: #d1d5db;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                @elseif($valueB === 'partial')
                                    <svg style="width: 1.25rem; height: 1.25rem; color: #eab308;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" />
                                    </svg>
                                @else
                                    <span style="font-size: 0.875rem; font-weight: 500; color: {{ $primaryColor }};">
                                        {{ $feature['column_b_text'] ?? '' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div style="text-align: center; padding: 2rem; color: #6b7280;">
                <p>No comparison features configured. Click to edit this block.</p>
            </div>
        @endif
    </div>
</section>
