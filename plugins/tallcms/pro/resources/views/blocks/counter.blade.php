@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];

    $customProperties = collect([
        '--block-heading-color: ' . ($textPreset['heading'] ?? '#111827'),
        '--block-text-color: ' . ($textPreset['description'] ?? '#4b5563'),
        '--block-primary-color: ' . ($textPreset['link'] ?? '#2563eb'),
    ])->join('; ') . ';';

    $uniqueId = 'counter-' . uniqid();

    // Note: Using full class strings so Tailwind JIT can detect them
    $colCount = $columns ?? '4';

    $cardClasses = match($style ?? 'default') {
        'cards' => 'bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6',
        'minimal' => 'py-4',
        default => 'text-center',
    };
@endphp

@once
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('proCounter', (config) => ({
        value: 0,
        target: config.target || 0,
        duration: config.duration || 2000,
        started: false,
        init() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.started) {
                        this.started = true;
                        this.animate();
                    }
                });
            }, { threshold: 0.1 });
            observer.observe(this.$el);
        },
        animate() {
            const start = performance.now();
            const step = (timestamp) => {
                const progress = Math.min((timestamp - start) / this.duration, 1);
                const easeOutQuad = 1 - (1 - progress) * (1 - progress);
                this.value = Math.floor(easeOutQuad * this.target);
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    this.value = this.target;
                }
            };
            requestAnimationFrame(step);
        },
        get formattedValue() {
            return this.value.toLocaleString();
        }
    }));
});
</script>
@endonce

<section
    class="pro-counter-block py-12 sm:py-16"
    style="{{ $customProperties }}"
>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <div class="text-center mb-12">
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

        {{-- Counters Grid --}}
        @if(!empty($counters))
            <style>
                #{{ $uniqueId }} {
                    display: grid;
                    gap: 1.5rem;
                    grid-template-columns: repeat(1, minmax(0, 1fr));
                }
                @media (min-width: 640px) {
                    #{{ $uniqueId }} {
                        grid-template-columns: repeat({{ min($colCount, 2) }}, minmax(0, 1fr));
                    }
                }
                @media (min-width: 1024px) {
                    #{{ $uniqueId }} {
                        gap: 2rem;
                        grid-template-columns: repeat({{ $colCount }}, minmax(0, 1fr));
                    }
                }
            </style>
            <div id="{{ $uniqueId }}">
                @foreach($counters as $counter)
                    <div
                        class="{{ $cardClasses }}"
                        x-data="proCounter({ target: {{ (int)($counter['value'] ?? 0) }}, duration: {{ (int)($duration ?? 2000) }} })"
                    >
                        <div class="counter-value text-4xl sm:text-5xl font-bold mb-2" style="color: var(--block-primary-color);">
                            <span>{{ $counter['prefix'] ?? '' }}</span>
                            <span x-text="formattedValue">0</span>
                            <span>{{ $counter['suffix'] ?? '' }}</span>
                        </div>
                        <div class="counter-label text-lg font-semibold mb-1" style="color: var(--block-heading-color);">
                            {{ $counter['label'] ?? '' }}
                        </div>
                        @if(!empty($counter['description']))
                            <div class="counter-description text-sm" style="color: var(--block-text-color);">
                                {{ $counter['description'] }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <p>No counters configured. Click to edit this block.</p>
            </div>
        @endif
    </div>
</section>
