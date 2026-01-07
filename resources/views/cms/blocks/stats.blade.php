@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];

    $customProperties = collect([
        '--block-heading-color: ' . ($textPreset['heading'] ?? '#111827'),
        '--block-text-color: ' . ($textPreset['description'] ?? '#4b5563'),
    ])->join('; ') . ';';

    $textAlignClass = match($text_alignment ?? 'center') {
        'left' => 'text-left',
        'center' => 'text-center',
        default => 'text-center',
    };

    $columnsClass = match($columns ?? '4') {
        '2' => 'sm:grid-cols-2 max-w-3xl mx-auto',
        '3' => 'sm:grid-cols-3 max-w-5xl mx-auto',
        '4' => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-4',
    };

    $styleClasses = match($style ?? 'minimal') {
        'cards' => 'bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6',
        'bordered' => 'bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6',
        'minimal' => 'p-4',
        default => 'p-4',
    };

    $sectionSpacing = ($first_section ?? false) ? 'pt-0' : 'pt-16 sm:pt-24';
    $shouldAnimate = $animate ?? false;
@endphp

<section
    class="stats-block {{ $sectionSpacing }} pb-16 sm:pb-24"
    style="{{ $customProperties }}"
    @if($shouldAnimate)
        x-data="{
            prefersReducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            animateValue(el, target, duration = 2000) {
                if (this.prefersReducedMotion) {
                    el.textContent = target;
                    return;
                }

                const numericTarget = parseInt(target.replace(/[^0-9]/g, ''), 10);
                if (isNaN(numericTarget)) {
                    el.textContent = target;
                    return;
                }

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
        x-intersect:enter.once="$el.querySelectorAll('[data-stat-value]').forEach(el => animateValue(el, el.dataset.statValue))"
    @endif
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        @if(!empty($heading))
            <div class="{{ $textAlignClass }} mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight" style="color: var(--block-heading-color);">
                    {{ $heading }}
                </h2>
            </div>
        @endif

        {{-- Stats Grid --}}
        @if(!empty($stats))
            <div class="grid gap-6 sm:gap-8 {{ $columnsClass }}">
                @foreach($stats as $stat)
                    <div class="stat-item {{ $styleClasses }} {{ $textAlignClass }}">
                        {{-- Icon --}}
                        @if(!empty($stat['icon']))
                            <div class="stat-icon mb-3 {{ $textAlignClass === 'text-center' ? 'flex justify-center' : '' }}">
                                @php
                                    $iconName = str_replace(['heroicon-o-', 'heroicon-s-', 'heroicon-m-'], '', $stat['icon']);
                                    $iconStyle = str_starts_with($stat['icon'], 'heroicon-s-') ? 's' : 'o';
                                @endphp
                                <x-dynamic-component
                                    :component="'heroicon-' . $iconStyle . '-' . $iconName"
                                    class="w-8 h-8 text-primary-600 dark:text-primary-400"
                                />
                            </div>
                        @endif

                        {{-- Value --}}
                        <div class="stat-value text-3xl sm:text-4xl lg:text-5xl font-bold" style="color: var(--block-heading-color);">
                            @if(!empty($stat['prefix']))
                                <span class="stat-prefix">{{ $stat['prefix'] }}</span>
                            @endif
                            @if($shouldAnimate)
                                <span data-stat-value="{{ $stat['value'] }}">0</span>
                            @else
                                <span>{{ $stat['value'] }}</span>
                            @endif
                            @if(!empty($stat['suffix']))
                                <span class="stat-suffix">{{ $stat['suffix'] }}</span>
                            @endif
                        </div>

                        {{-- Label --}}
                        <p class="stat-label text-sm sm:text-base mt-2" style="color: var(--block-text-color);">
                            {{ $stat['label'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
