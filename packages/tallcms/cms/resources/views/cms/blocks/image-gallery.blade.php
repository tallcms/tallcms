@php
    $layoutClasses = [
        'grid-2' => 'grid-cols-1 md:grid-cols-2',
        'grid-3' => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
        'grid-4' => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
        'masonry' => 'columns-1 md:columns-2 lg:columns-3',
        'carousel' => 'flex gap-4 overflow-x-auto snap-x snap-mandatory',
    ];

    $sizeClasses = [
        'small' => 'h-48',
        'medium' => 'h-64',
        'large' => 'h-80',
        'full' => 'h-auto',
    ];

    $gridClass = $layoutClasses[$layout ?? 'grid-3'] ?? $layoutClasses['grid-3'];
    $sizeClass = $sizeClasses[$image_size ?? 'medium'] ?? $sizeClasses['medium'];
    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');
    $galleryId = 'gallery-' . uniqid();
@endphp

<section
    class="image-gallery-block {{ $sectionPadding }} {{ $background ?? 'bg-base-100' }}"
    x-data="{
        images: @js(collect($images)->filter(fn($img) => Storage::disk(cms_media_disk())->exists($img))->map(fn($img) => Storage::disk(cms_media_disk())->url($img))->values()->all()),
        currentIndex: 0,
        isOpen: false,
        open(index) {
            this.currentIndex = index;
            this.isOpen = true;
        },
        close() {
            this.isOpen = false;
        },
        next() {
            this.currentIndex = (this.currentIndex + 1) % this.images.length;
        },
        prev() {
            this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
        }
    }"
    @keydown.escape.window="close()"
    @keydown.left.window="isOpen && prev()"
    @keydown.right.window="isOpen && next()"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($title ?? false)
            <h3 class="text-2xl font-bold text-base-content text-center mb-8">
                {{ $title }}
            </h3>
        @endif

        @if(($layout ?? 'grid-3') === 'masonry')
            <div class="{{ $gridClass }} gap-4">
                @php $imageIndex = 0; @endphp
                @foreach($images as $image)
                    @if(Storage::disk(cms_media_disk())->exists($image))
                        <div class="break-inside-avoid mb-4">
                            <img
                                src="{{ Storage::disk(cms_media_disk())->url($image) }}"
                                alt="Gallery image {{ $imageIndex + 1 }}"
                                class="w-full rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
                                @click="open({{ $imageIndex }})"
                            >
                        </div>
                        @php $imageIndex++; @endphp
                    @endif
                @endforeach
            </div>
        @elseif(($layout ?? 'grid-3') === 'carousel')
            <div class="{{ $gridClass }} pb-4">
                @php $imageIndex = 0; @endphp
                @foreach($images as $image)
                    @if(Storage::disk(cms_media_disk())->exists($image))
                        <div class="flex-none w-80 snap-start">
                            <img
                                src="{{ Storage::disk(cms_media_disk())->url($image) }}"
                                alt="Gallery image {{ $imageIndex + 1 }}"
                                class="w-full {{ $sizeClass }} object-cover rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
                                @click="open({{ $imageIndex }})"
                            >
                        </div>
                        @php $imageIndex++; @endphp
                    @endif
                @endforeach
            </div>
        @else
            <div class="grid {{ $gridClass }} gap-6">
                @php $imageIndex = 0; @endphp
                @foreach($images as $image)
                    @if(Storage::disk(cms_media_disk())->exists($image))
                        <div>
                            <img
                                src="{{ Storage::disk(cms_media_disk())->url($image) }}"
                                alt="Gallery image {{ $imageIndex + 1 }}"
                                class="w-full {{ $sizeClass }} object-cover rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
                                @click="open({{ $imageIndex }})"
                            >
                        </div>
                        @php $imageIndex++; @endphp
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Lightbox Modal --}}
    <div
        x-show="isOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/90"
        @click.self="close()"
    >
        <div class="relative max-w-4xl max-h-full p-4">
            <img
                :src="images[currentIndex]"
                :alt="'Gallery image ' + (currentIndex + 1)"
                class="max-w-full max-h-[90vh] rounded-lg"
            >

            {{-- Close Button --}}
            <button
                @click="close()"
                class="btn btn-circle btn-ghost text-white absolute top-4 right-4"
            >
                <x-heroicon-o-x-mark class="w-8 h-8" />
            </button>

            {{-- Previous Button --}}
            <button
                @click="prev()"
                class="btn btn-circle btn-ghost text-white absolute left-4 top-1/2 -translate-y-1/2"
            >
                <x-heroicon-o-chevron-left class="w-8 h-8" />
            </button>

            {{-- Next Button --}}
            <button
                @click="next()"
                class="btn btn-circle btn-ghost text-white absolute right-4 top-1/2 -translate-y-1/2"
            >
                <x-heroicon-o-chevron-right class="w-8 h-8" />
            </button>
        </div>
    </div>
</section>
