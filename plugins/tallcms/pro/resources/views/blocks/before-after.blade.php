@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];

    $customProperties = collect([
        '--block-heading-color: ' . ($textPreset['heading'] ?? '#111827'),
        '--block-text-color: ' . ($textPreset['description'] ?? '#4b5563'),
        '--block-primary-color: ' . ($textPreset['link'] ?? '#2563eb'),
    ])->join('; ') . ';';

    // Max width classes
    $widthClass = match($width ?? 'xl') {
        'full' => 'max-w-full',
        'lg' => 'max-w-4xl',
        'md' => 'max-w-3xl',
        default => 'max-w-5xl',
    };

    $roundedClass = ($rounded ?? true) ? 'rounded-xl overflow-hidden' : '';
    $isVertical = ($orientation ?? 'horizontal') === 'vertical';
    $initialPos = max(0, min(100, (int)($initial_position ?? 50)));
@endphp

@once
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('proBeforeAfter', (config) => ({
        position: config.initialPosition || 50,
        isDragging: false,
        isVertical: config.isVertical || false,

        init() {
            this.$watch('position', () => this.updateClip());
            this.$nextTick(() => this.updateClip());
        },

        updateClip() {
            const overlay = this.$refs.overlay;
            if (!overlay) return;

            if (this.isVertical) {
                overlay.style.clipPath = `inset(${this.position}% 0 0 0)`;
            } else {
                overlay.style.clipPath = `inset(0 0 0 ${this.position}%)`;
            }
        },

        handleMove(e) {
            if (!this.isDragging) return;

            const rect = this.$refs.container.getBoundingClientRect();
            let pos;

            if (this.isVertical) {
                const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;
                pos = (y / rect.height) * 100;
            } else {
                const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
                pos = (x / rect.width) * 100;
            }

            this.position = Math.max(0, Math.min(100, pos));
        },

        startDrag(e) {
            e.preventDefault();
            this.isDragging = true;
            this.handleMove(e);
        },

        endDrag() {
            this.isDragging = false;
        }
    }));
});
</script>
@endonce

<section
    class="pro-before-after-block py-12 sm:py-16"
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

        {{-- Comparison Container --}}
        <div class="{{ $widthClass }} mx-auto">
            @if(!empty($before_image) && !empty($after_image))
                <div
                    x-data="proBeforeAfter({ initialPosition: {{ $initialPos }}, isVertical: {{ $isVertical ? 'true' : 'false' }} })"
                    x-ref="container"
                    class="relative select-none {{ $roundedClass }} shadow-lg {{ $isVertical ? 'cursor-ns-resize' : 'cursor-ew-resize' }}"
                    @mousedown="startDrag"
                    @mousemove.window="handleMove"
                    @mouseup.window="endDrag"
                    @mouseleave.window="endDrag"
                    @touchstart.prevent="startDrag"
                    @touchmove.window="handleMove"
                    @touchend.window="endDrag"
                >
                    {{-- Before Image (Bottom Layer) --}}
                    <div class="relative w-full">
                        <img
                            src="{{ $before_image }}"
                            alt="{{ $before_label }}"
                            class="w-full h-auto block"
                            draggable="false"
                        >

                        {{-- Before Label --}}
                        @if($show_labels ?? true)
                            <div class="absolute {{ $isVertical ? 'top-4' : 'bottom-4' }} left-4 bg-black/60 text-white text-sm font-medium px-3 py-1 rounded-full">
                                {{ $before_label }}
                            </div>
                        @endif
                    </div>

                    {{-- After Image (Overlay) --}}
                    <div
                        x-ref="overlay"
                        class="absolute inset-0"
                        style="clip-path: inset(0 0 0 {{ $initialPos }}%);"
                    >
                        <img
                            src="{{ $after_image }}"
                            alt="{{ $after_label }}"
                            class="w-full h-full object-cover"
                            draggable="false"
                        >

                        {{-- After Label --}}
                        @if($show_labels ?? true)
                            <div class="absolute {{ $isVertical ? 'bottom-4' : 'bottom-4' }} right-4 bg-black/60 text-white text-sm font-medium px-3 py-1 rounded-full">
                                {{ $after_label }}
                            </div>
                        @endif
                    </div>

                    {{-- Slider Handle --}}
                    @if($isVertical)
                        {{-- Vertical Slider --}}
                        <div
                            class="absolute left-0 right-0 h-1 bg-white shadow-lg transform -translate-y-1/2 pointer-events-none"
                            :style="'top: ' + position + '%'"
                        >
                            {{-- Handle Circle --}}
                            <div class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center pointer-events-auto cursor-ns-resize" style="border: 3px solid var(--block-primary-color);">
                                <svg class="w-5 h-5 rotate-90" fill="currentColor" viewBox="0 0 24 24" style="color: var(--block-primary-color);">
                                    <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                                    <path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z" transform="scale(-1,1) translate(-24,0)"/>
                                </svg>
                            </div>
                        </div>
                    @else
                        {{-- Horizontal Slider --}}
                        <div
                            class="absolute top-0 bottom-0 w-1 bg-white shadow-lg transform -translate-x-1/2 pointer-events-none"
                            :style="'left: ' + position + '%'"
                        >
                            {{-- Handle Circle --}}
                            <div class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center pointer-events-auto cursor-ew-resize" style="border: 3px solid var(--block-primary-color);">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" style="color: var(--block-primary-color);">
                                    <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                                    <path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z" transform="scale(-1,1) translate(-24,0)"/>
                                </svg>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                {{-- No Images Configured --}}
                <div class="relative w-full bg-gray-100 dark:bg-gray-800 {{ $roundedClass }}" style="padding-bottom: 56.25%;">
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                        <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-sm">Add before and after images to create a comparison. Click to edit.</p>
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
