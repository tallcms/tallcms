@php
    $styleClasses = [
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white',
        'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white',
        'success' => 'bg-green-600 hover:bg-green-700 text-white',
        'warning' => 'bg-yellow-500 hover:bg-yellow-600 text-white',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white',
    ];
    $buttonClass = $styleClasses[$style] ?? $styleClasses['primary'];
@endphp

<div class="bg-gray-50 py-16 px-6 sm:px-12 lg:px-16">
    <div class="mx-auto max-w-3xl text-center">
        @if($title)
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                {{ $title }}
            </h2>
        @endif
        
        @if($description)
            <p class="mt-6 text-lg leading-8 text-gray-600">
                {{ $description }}
            </p>
        @endif
        
        @if($button_text && $button_url)
            <div class="mt-10">
                <a href="{{ $button_url }}" 
                   class="inline-block rounded-lg {{ $buttonClass }} px-8 py-4 text-lg font-semibold shadow-lg transition hover:shadow-xl">
                    {{ $button_text }}
                </a>
            </div>
        @endif
    </div>
</div>