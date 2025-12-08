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

<div class="py-8">
    @if($title)
        <h3 class="text-2xl font-bold text-gray-900 text-center mb-8">
            {{ $title }}
        </h3>
    @endif
    
    @if($layout === 'masonry')
        <div class="{{ $gridClass }} gap-4 space-y-4">
            @foreach($images as $image)
                <div class="break-inside-avoid">
                    <img src="{{ Storage::url($image) }}" 
                         alt="Gallery image" 
                         class="w-full rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
                         onclick="openLightbox(this)">
                </div>
            @endforeach
        </div>
    @elseif($layout === 'carousel')
        <div class="relative">
            <div class="{{ $gridClass }} pb-4" style="scroll-snap-type: x mandatory;">
                @foreach($images as $image)
                    <div class="flex-none w-80" style="scroll-snap-align: start;">
                        <img src="{{ Storage::url($image) }}" 
                             alt="Gallery image" 
                             class="w-full {{ $sizeClass }} object-cover rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
                             onclick="openLightbox(this)">
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="grid {{ $gridClass }} gap-6">
            @foreach($images as $image)
                <div class="group">
                    <img src="{{ Storage::url($image) }}" 
                         alt="Gallery image" 
                         class="w-full {{ $sizeClass }} object-cover rounded-lg shadow-md group-hover:shadow-lg transition-shadow cursor-pointer"
                         onclick="openLightbox(this)">
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Lightbox Modal -->
<div id="lightbox" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center">
    <div class="relative max-w-4xl max-h-full p-4">
        <img id="lightbox-image" src="" alt="Enlarged image" class="max-w-full max-h-full rounded-lg">
        <button onclick="closeLightbox()" 
                class="absolute top-4 right-4 text-white text-4xl font-bold hover:text-gray-300">
            &times;
        </button>
        <button onclick="previousImage()" 
                class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white text-3xl font-bold hover:text-gray-300">
            &#8249;
        </button>
        <button onclick="nextImage()" 
                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white text-3xl font-bold hover:text-gray-300">
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