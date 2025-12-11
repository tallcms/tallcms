@php
    $sectionClasses = collect([
        'w-full',
        'px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16',
        'py-16 sm:py-24',
        $first_section ? 'pt-32 sm:pt-36' : 'py-16 sm:py-24'
    ])->filter()->join(' ');
@endphp

<section class="{{ $sectionClasses }}" 
         style="width: 100%; padding-left: clamp(1rem, 5vw, 4rem); padding-right: clamp(1rem, 5vw, 4rem); {{ $first_section ? 'padding-top: 8rem;' : 'padding-top: 4rem; padding-bottom: 4rem;' }}">
    
    <div class="max-w-4xl mx-auto" 
         style="max-width: 56rem; margin: 0 auto;">
        
        @if($title)
            <header class="mb-12 text-center" style="margin-bottom: 3rem; text-align: center;">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold text-gray-900 leading-tight" 
                    style="font-size: clamp(2.25rem, 6vw, 4.5rem); font-weight: bold; line-height: 1.1; color: #111827;">
                    {{ $title }}
                </h1>
            </header>
        @endif
        
        @if($body)
            <article class="prose prose-lg prose-gray max-w-none
                prose-headings:font-semibold prose-headings:text-gray-900
                prose-h1:text-3xl prose-h1:lg:text-4xl prose-h1:leading-tight prose-h1:mb-8
                prose-h2:text-2xl prose-h2:lg:text-3xl prose-h2:mt-12 prose-h2:mb-6
                prose-h3:text-xl prose-h3:lg:text-2xl prose-h3:mt-8 prose-h3:mb-4
                prose-p:text-gray-600 prose-p:leading-relaxed prose-p:mb-6 prose-p:text-lg
                prose-a:text-blue-600 prose-a:no-underline hover:prose-a:underline
                prose-strong:text-gray-900 prose-strong:font-semibold
                prose-em:text-gray-700
                prose-blockquote:border-l-4 prose-blockquote:border-blue-500
                prose-blockquote:bg-blue-50 prose-blockquote:py-4 prose-blockquote:px-6
                prose-blockquote:rounded-r-lg prose-blockquote:not-italic prose-blockquote:my-8
                prose-ul:my-6 prose-ul:text-gray-600 prose-ul:text-lg
                prose-ol:my-6 prose-ol:text-gray-600 prose-ol:text-lg
                prose-li:my-2 prose-li:leading-relaxed"
                 style="max-width: none; font-size: 1.125rem; line-height: 1.75; color: #4b5563;">
                {!! $body !!}
            </article>
        @endif
        
    </div>
</section>