<div class="relative overflow-hidden bg-gradient-to-br from-blue-600 to-purple-600 text-white">
    @if($background_image)
        <div class="absolute inset-0 z-0">
            <img src="{{ Storage::url($background_image) }}" alt="Hero background" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        </div>
    @endif
    
    <div class="relative z-10 px-6 py-24 sm:px-12 sm:py-32 lg:px-16">
        <div class="mx-auto max-w-4xl text-center">
            @if($heading)
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                    {{ $heading }}
                </h1>
            @endif
            
            @if($subheading)
                <p class="mt-6 text-xl leading-8 text-gray-100">
                    {{ $subheading }}
                </p>
            @endif
            
            @if($button_text && $button_url)
                <div class="mt-10">
                    <a href="{{ $button_url }}" 
                       class="inline-block rounded-lg bg-white px-8 py-4 text-lg font-semibold text-blue-600 shadow-lg transition hover:bg-gray-50 hover:shadow-xl">
                        {{ $button_text }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>