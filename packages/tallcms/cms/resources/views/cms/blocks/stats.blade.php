@php
    $columnsClass = match($columns ?? '4') {
        '2' => 'sm:grid-cols-2 max-w-3xl mx-auto',
        '3' => 'sm:grid-cols-3 max-w-5xl mx-auto',
        '4' => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-4',
    };

    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');
    $shouldAnimate = $animate ?? false;

    $animationType = $animation_type ?? '';
    $animationDuration = $animation_duration ?? 'anim-duration-500';
    $animationStagger = $animation_stagger ?? false;
    $staggerDelay = (int) ($animation_stagger_delay ?? 100);

    // StatsBlock requires merging animation state with count-up animation x-data
    $hasEntranceAnimation = !empty($animationType);
@endphp

<section
    @if($anchor_id ?? null) id="{{ $anchor_id }}" @endif
    class="stats-block {{ $sectionPadding }} {{ $background ?? 'bg-base-100' }} {{ $css_classes ?? '' }}"
    x-data="{
        tallcmsShown: false,
        tallcmsReducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        animateValue(el, target, duration = 2000) {
            const isPureInteger = /^\d+$/.test(target);
            if (this.tallcmsReducedMotion || !isPureInteger) {
                el.textContent = target;
                return;
            }
            const numericTarget = parseInt(target, 10);
            let startTime = null;
            const step = (timestamp) => {
                if (!startTime) startTime = timestamp;
                const progress = Math.min((timestamp - startTime) / duration, 1);
                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                const current = Math.floor(easeOutQuart * numericTarget);
                el.textContent = current.toLocaleString();
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                } else {
                    el.textContent = numericTarget.toLocaleString();
                }
            };
            window.requestAnimationFrame(step);
        }
    }"
    x-intersect:enter.once="tallcmsShown = true; @if($shouldAnimate) $el.querySelectorAll('[data-stat-value]').forEach(el => animateValue(el, el.dataset.statValue)) @endif"
    @if($hasEntranceAnimation)
        :class="tallcmsShown || tallcmsReducedMotion ? 'tallcms-animate animate-{{ $animationType }} {{ $animationDuration }}' : 'opacity-0'"
    @endif
>
    <div class="{{ $contentWidthClass ?? 'max-w-7xl mx-auto' }} {{ $contentPadding ?? 'px-4 sm:px-6 lg:px-8' }}">
        {{-- Section Header --}}
        @if(!empty($heading))
            <x-tallcms::animation-wrapper
                :animation="$animationType"
                :duration="$animationDuration"
                :use-parent="true"
                class="{{ $text_alignment ?? 'text-center' }} mb-12"
            >
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-base-content">
                    {{ $heading }}
                </h2>
            </x-tallcms::animation-wrapper>
        @endif

        {{-- Stats Grid using daisyUI stats component --}}
        @if(!empty($stats))
            <div class="stats stats-vertical lg:stats-horizontal shadow w-full {{ $columnsClass }} grid">
                @foreach($stats as $index => $stat)
                    @php
                        $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1)) : 0;
                    @endphp
                    <x-tallcms::animation-wrapper
                        :animation="$animationType"
                        :duration="$animationDuration"
                        :use-parent="true"
                        :delay="$itemDelay"
                        class="{{ $stat_style ?? 'stat' }} {{ $text_alignment ?? 'text-center' }} place-items-center"
                    >
                        {{-- Icon --}}
                        @if(!empty($stat['icon']))
                            <div class="stat-figure text-primary">
                                @php
                                    $iconName = str_replace(['heroicon-o-', 'heroicon-s-', 'heroicon-m-'], '', $stat['icon']);
                                    $iconStyle = str_starts_with($stat['icon'], 'heroicon-s-') ? 's' : 'o';
                                @endphp
                                <x-dynamic-component
                                    :component="'heroicon-' . $iconStyle . '-' . $iconName"
                                    class="w-8 h-8"
                                />
                            </div>
                        @endif

                        {{-- Value --}}
                        <div class="stat-value text-primary">
                            @if(!empty($stat['prefix']))
                                <span>{{ $stat['prefix'] }}</span>
                            @endif
                            @if($shouldAnimate)
                                <span data-stat-value="{{ $stat['value'] }}">0</span>
                            @else
                                <span>{{ $stat['value'] }}</span>
                            @endif
                            @if(!empty($stat['suffix']))
                                <span>{{ $stat['suffix'] }}</span>
                            @endif
                        </div>

                        {{-- Label --}}
                        <div class="stat-title text-base-content/70">
                            {{ $stat['label'] }}
                        </div>
                    </x-tallcms::animation-wrapper>
                @endforeach
            </div>
        @endif
    </div>
</section>
