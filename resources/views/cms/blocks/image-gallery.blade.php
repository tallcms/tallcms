@php
    $layoutClasses = [
        'grid-2' => 'grid-cols-1 md:grid-cols-2',
        'grid-3' => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
        'grid-4' => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
        'masonry' => 'columns-1 md:columns-2 lg:columns-3',
        'carousel' => 'flex space-x-4 overflow-x-auto',
    ];
    
    $sizeClasses = [
        'small' => 'h-48',
        'medium' => 'h-64',
        'large' => 'h-80',
        'full' => 'h-auto',
    ];
    
    $gridClass = $layoutClasses[$layout] ?? $layoutClasses['grid-3'];
    $sizeClass = $sizeClasses[$image_size] ?? $sizeClasses['medium'];
@endphp

<div class="py-8" style="padding: 2rem 0;">
    @if($title)
        <h3 class="text-2xl font-bold text-gray-900 text-center mb-8" 
            style="font-size: 1.5rem; font-weight: bold; color: #111827; text-align: center; margin-bottom: 2rem;">
            {{ $title }}
        </h3>
    @endif
    
    @if($layout === 'masonry')
        <div class="{{ $gridClass }} gap-4 space-y-4" 
             style="columns: 3; column-gap: 1rem; row-gap: 1rem;">
            @foreach($images as $image)
                <div class="break-inside-avoid" style="break-inside: avoid; margin-bottom: 1rem;">
                    <img src="{{ Storage::url($image) }}" 
                         alt="Gallery image" 
                         class="w-full rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
                         style="width: 100%; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); cursor: pointer; transition: box-shadow 0.3s ease;"
                         onclick="openLightbox(this)">
                </div>
            @endforeach
        </div>
    @elseif($layout === 'carousel')
        <div class="relative" style="position: relative;">
            <div class="{{ $gridClass }} pb-4" 
                 style="display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 1rem; scroll-snap-type: x mandatory;">
                @foreach($images as $image)
                    <div class="flex-none w-80" 
                         style="flex: none; width: 20rem; scroll-snap-align: start;">
                        <img src="{{ Storage::url($image) }}" 
                             alt="Gallery image" 
                             class="w-full {{ $sizeClass }} object-cover rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
                             style="width: 100%; height: 16rem; object-fit: cover; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); cursor: pointer; transition: box-shadow 0.3s ease;"
                             onclick="openLightbox(this)">
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="grid {{ $gridClass }} gap-6" 
             style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            @foreach($images as $image)
                <div class="group" style="position: relative;">
                    <img src="{{ Storage::url($image) }}" 
                         alt="Gallery image" 
                         class="w-full {{ $sizeClass }} object-cover rounded-lg shadow-md group-hover:shadow-lg transition-shadow cursor-pointer"
                         style="width: 100%; height: 16rem; object-fit: cover; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); cursor: pointer; transition: box-shadow 0.3s ease;"
                         onclick="openLightbox(this)">
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Lightbox Modal -->
<div id="lightbox" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center"
     style="position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.9); z-index: 50; display: none; align-items: center; justify-content: center;">
    <div class="relative max-w-4xl max-h-full p-4"
         style="position: relative; max-width: 56rem; max-height: 100%; padding: 1rem;">
        <img id="lightbox-image" src="" alt="Enlarged image" 
             class="max-w-full max-h-full rounded-lg"
             style="max-width: 100%; max-height: 100%; border-radius: 0.5rem;">
        <button onclick="closeLightbox()" 
                class="absolute top-4 right-4 text-white text-4xl font-bold hover:text-gray-300"
                style="position: absolute; top: 1rem; right: 1rem; color: white; font-size: 2.25rem; font-weight: bold; background: none; border: none; cursor: pointer;">
            &times;
        </button>
        <button onclick="previousImage()" 
                class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white text-3xl font-bold hover:text-gray-300"
                style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: white; font-size: 1.875rem; font-weight: bold; background: none; border: none; cursor: pointer;">
            &#8249;
        </button>
        <button onclick="nextImage()" 
                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white text-3xl font-bold hover:text-gray-300"
                style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: white; font-size: 1.875rem; font-weight: bold; background: none; border: none; cursor: pointer;">
            &#8250;
        </button>
    </div>
</div>

<script>
let currentImages = [];
let currentImageIndex = 0;

function openLightbox(imgElement) {
    const gallery = imgElement.closest('div').parentElement;
    currentImages = Array.from(gallery.querySelectorAll('img')).map(img => img.src);
    currentImageIndex = currentImages.indexOf(imgElement.src);
    
    document.getElementById('lightbox-image').src = imgElement.src;
    document.getElementById('lightbox').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightbox').classList.add('hidden');
    document.body.style.overflow = '';
}

function previousImage() {
    currentImageIndex = (currentImageIndex - 1 + currentImages.length) % currentImages.length;
    document.getElementById('lightbox-image').src = currentImages[currentImageIndex];
}

function nextImage() {
    currentImageIndex = (currentImageIndex + 1) % currentImages.length;
    document.getElementById('lightbox-image').src = currentImages[currentImageIndex];
}

// Close lightbox on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLightbox();
    } else if (e.key === 'ArrowLeft') {
        previousImage();
    } else if (e.key === 'ArrowRight') {
        nextImage();
    }
});

// Close lightbox when clicking outside the image
document.getElementById('lightbox').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLightbox();
    }
});
</script>