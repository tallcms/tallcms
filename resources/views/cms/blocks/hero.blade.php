<div class="relative overflow-hidden bg-gradient-to-br from-blue-600 to-purple-600 text-white" 
     style="position: relative; overflow: hidden; background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%); color: white; min-height: 300px;">
    @if($background_image)
        <div class="absolute inset-0 z-0" style="position: absolute; inset: 0; z-index: 0;">
            <img src="{{ Storage::url($background_image) }}" alt="Hero background" 
                 class="w-full h-full object-cover" style="width: 100%; height: 100%; object-fit: cover;">
            <div class="absolute inset-0 bg-black bg-opacity-50" style="position: absolute; inset: 0; background-color: rgba(0, 0, 0, 0.5);"></div>
        </div>
    @endif
    
    <div class="relative z-10 px-6 py-24 sm:px-12 sm:py-32 lg:px-16" 
         style="position: relative; z-index: 10; padding: 6rem 1.5rem;">
        <div class="mx-auto max-w-4xl text-center" style="max-width: 56rem; text-align: center; margin: 0 auto;">
            @if($heading)
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl" 
                    style="font-size: 2.25rem; font-weight: bold; line-height: 1.2; margin-bottom: 1.5rem;">
                    {{ $heading }}
                </h1>
            @endif
            
            @if($subheading)
                <p class="mt-6 text-xl leading-8 text-gray-100" 
                   style="margin-top: 1.5rem; font-size: 1.25rem; line-height: 2; color: rgba(255, 255, 255, 0.9);">
                    {{ $subheading }}
                </p>
            @endif
            
            @if($button_text)
                <div class="mt-10" style="margin-top: 2.5rem;">
                    <a href="{{ $button_url ?? '#' }}" 
                       class="inline-block rounded-lg bg-white px-8 py-4 text-lg font-semibold text-blue-600 shadow-lg transition hover:bg-gray-50 hover:shadow-xl"
                       style="display: inline-block; border-radius: 0.5rem; background-color: white; padding: 1rem 2rem; font-size: 1.125rem; font-weight: 600; color: #2563eb; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1); text-decoration: none;">
                        {{ $button_text }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>