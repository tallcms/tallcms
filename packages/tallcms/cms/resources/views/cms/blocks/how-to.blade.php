@php
    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');

    $animationType = $animation_type ?? '';
    $animationDuration = $animation_duration ?? 'anim-duration-700';
    $animationStagger = $animation_stagger ?? false;
    $staggerDelay = (int) ($animation_stagger_delay ?? 100);

    // Build anchor ID attribute (avoid @if inside tag to prevent Blade comment injection)
    $anchorIdAttr = !empty($anchor_id) ? 'id="' . e($anchor_id) . '"' : '';
@endphp

<section
    {!! $anchorIdAttr !!}
    class="how-to-block {{ $sectionPadding }} {{ $background ?? 'bg-base-100' }} {{ $css_classes ?? '' }}"
    x-data="{
        tallcmsShown: false,
        tallcmsReducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches
    }"
    x-intersect:enter.once="tallcmsShown = true"
>
    <div class="{{ $contentWidthClass ?? 'max-w-6xl mx-auto' }} {{ $contentPadding ?? 'px-4 sm:px-6 lg:px-8' }}">
        {{-- Section Header --}}
        @if(!empty($title) || !empty($description))
            <x-tallcms::animation-wrapper
                :animation="$animationType"
                :duration="$animationDuration"
                :use-parent="true"
                class="{{ $text_alignment ?? 'text-center' }} mb-12"
            >
                @if(!empty($title))
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-base-content">
                        {{ $title }}
                    </h2>
                @endif
                @if(!empty($description))
                    <p class="mt-4 text-lg text-base-content/70 max-w-2xl {{ ($text_alignment ?? 'text-center') === 'text-center' ? 'mx-auto' : '' }}">
                        {{ $description }}
                    </p>
                @endif
            </x-tallcms::animation-wrapper>
        @endif

        {{-- Steps --}}
        @if(!empty($steps))
            <ol class="space-y-6">
                @foreach($steps as $index => $step)
                    @php
                        $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1)) : 0;
                        $stepNumber = $index + 1;
                    @endphp

                    <x-tallcms::animation-wrapper
                        :animation="$animationType"
                        :duration="$animationDuration"
                        :use-parent="true"
                        :delay="$itemDelay"
                        tag="li"
                        class="card bg-base-200"
                    >
                        <div class="card-body">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary text-primary-content flex items-center justify-center font-bold text-lg">
                                    {{ $stepNumber }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="card-title text-base-content">
                                        {{ $step['step_name'] ?? '' }}
                                    </h3>
                                    <p class="text-base-content/80 leading-relaxed mt-2">
                                        {!! nl2br(e($step['step_text'] ?? '')) !!}
                                    </p>

                                    @if(!empty($step['step_image']))
                                        <div class="mt-4">
                                            <img
                                                src="{{ Storage::disk(cms_media_disk())->url($step['step_image']) }}"
                                                alt="{{ $step['step_name'] ?? 'Step ' . $stepNumber }}"
                                                class="rounded-lg max-w-full h-auto"
                                                loading="lazy"
                                            />
                                        </div>
                                    @endif

                                    @if(!empty($step['step_url']))
                                        <a href="{{ $step['step_url'] }}" target="_blank" rel="noopener noreferrer" class="link link-primary mt-2 inline-block">
                                            Learn more &rarr;
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </x-tallcms::animation-wrapper>
                @endforeach
            </ol>
        @endif
    </div>

    {{-- Schema.org HowTo Structured Data --}}
    @if(($show_schema ?? true) && !empty($steps))
        @php
            $schemaSteps = array_map(fn($step, $index) => array_filter([
                '@type' => 'HowToStep',
                'position' => $index + 1,
                'name' => $step['step_name'] ?? '',
                'text' => $step['step_text'] ?? '',
                'url' => !empty($step['step_url']) ? $step['step_url'] : null,
                'image' => !empty($step['step_image']) ? Storage::disk(cms_media_disk())->url($step['step_image']) : null,
            ], fn($v) => $v !== null), $steps, array_keys($steps));

            $schema = array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'HowTo',
                'name' => $title ?? '',
                'description' => !empty($description) ? $description : null,
                'totalTime' => !empty($total_time) ? $total_time : null,
                'estimatedCost' => !empty($estimated_cost) ? [
                    '@type' => 'MonetaryAmount',
                    'currency' => $currency ?? 'USD',
                    'value' => $estimated_cost,
                ] : null,
                'step' => $schemaSteps,
            ], fn($v) => $v !== null);
        @endphp
        <script type="application/ld+json">{!! json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
</section>
