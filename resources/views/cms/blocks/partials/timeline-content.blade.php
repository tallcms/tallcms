{{-- Timeline Content Card --}}
@php
    $textAlign = ($alignRight ?? false) ? 'text-right' : 'text-left';
@endphp
<div class="card bg-base-200 shadow-md {{ $textAlign }} {{ ($alignRight ?? false) ? '' : 'w-full' }}">
    <div class="card-body p-5 sm:p-6">
        {{-- Date shown in content regardless of numbered mode (number only affects node display) --}}
        @if(!empty($item['date']))
            <span class="inline-block text-xs font-semibold text-primary mb-2">
                {{ $item['date'] }}
            </span>
        @endif

        @if(!empty($item['title']))
            <h3 class="font-semibold text-lg text-base-content">
                {{ $item['title'] }}
            </h3>
        @endif

        @if(!empty($item['description']))
            <p class="mt-2 text-sm leading-relaxed text-base-content/70">
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
</div>
