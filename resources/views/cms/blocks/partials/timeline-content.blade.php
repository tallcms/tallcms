{{-- Timeline Content Card --}}
@php
    $textAlign = ($alignRight ?? false) ? 'text-right' : 'text-left';
@endphp
<div class="timeline-content bg-white dark:bg-gray-800 rounded-xl shadow-md p-5 sm:p-6 {{ $textAlign }} {{ ($alignRight ?? false) ? '' : 'w-full' }}">
    {{-- Date shown in content regardless of numbered mode (number only affects node display) --}}
    @if(!empty($item['date']))
        <span class="inline-block text-xs font-semibold text-primary-600 dark:text-primary-400 mb-2">
            {{ $item['date'] }}
        </span>
    @endif

    @if(!empty($item['title']))
        <h3 class="timeline-title font-semibold text-lg" style="color: var(--block-heading-color);">
            {{ $item['title'] }}
        </h3>
    @endif

    @if(!empty($item['description']))
        <p class="timeline-description mt-2 text-sm leading-relaxed" style="color: var(--block-text-color);">
            {{ $item['description'] }}
        </p>
    @endif

    @if(!empty($item['image']))
        <img
            src="{{ Storage::disk(cms_media_disk())->url($item['image']) }}"
            alt="{{ $item['title'] ?? '' }}"
            class="mt-4 rounded-lg w-full h-40 object-cover"
        >
    @endif
</div>
