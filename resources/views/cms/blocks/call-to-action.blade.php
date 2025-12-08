@php
    $styleClasses = [
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white',
        'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white',
        'success' => 'bg-green-600 hover:bg-green-700 text-white',
        'warning' => 'bg-yellow-500 hover:bg-yellow-600 text-white',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white',
    ];
    
    $styleInlines = [
        'primary' => 'background-color: #2563eb; color: white;',
        'secondary' => 'background-color: #4b5563; color: white;',
        'success' => 'background-color: #059669; color: white;',
        'warning' => 'background-color: #eab308; color: white;',
        'danger' => 'background-color: #dc2626; color: white;',
    ];
    
    $buttonClass = $styleClasses[$style] ?? $styleClasses['primary'];
    $buttonStyle = $styleInlines[$style] ?? $styleInlines['primary'];
@endphp

<div class="bg-gray-50 py-16 px-6 sm:px-12 lg:px-16" 
     style="background-color: #f9fafb; padding: 4rem 1.5rem;">
    <div class="mx-auto max-w-3xl text-center" 
         style="max-width: 48rem; text-align: center; margin: 0 auto;">
        @if($title)
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl" 
                style="font-size: 1.875rem; font-weight: bold; line-height: 1.2; color: #111827; margin-bottom: 1.5rem;">
                {{ $title }}
            </h2>
        @endif
        
        @if($description)
            <p class="mt-6 text-lg leading-8 text-gray-600" 
               style="margin-top: 1.5rem; font-size: 1.125rem; line-height: 2; color: #4b5563;">
                {{ $description }}
            </p>
        @endif
        
        @if($button_text && $button_url)
            <div class="mt-10" style="margin-top: 2.5rem;">
                <a href="{{ $button_url }}" 
                   class="inline-block rounded-lg {{ $buttonClass }} px-8 py-4 text-lg font-semibold shadow-lg transition hover:shadow-xl"
                   style="display: inline-block; border-radius: 0.5rem; {{ $buttonStyle }} padding: 1rem 2rem; font-size: 1.125rem; font-weight: 600; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1); text-decoration: none; transition: all 0.3s ease;">
                    {{ $button_text }}
                </a>
            </div>
        @endif
    </div>
</div>