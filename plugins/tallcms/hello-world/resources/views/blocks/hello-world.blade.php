@php
    $alignmentClasses = match($alignment) {
        'left' => 'text-left',
        'right' => 'text-right',
        default => 'text-center',
    };

    $styleClasses = match($style) {
        'gradient' => 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white',
        'bordered' => 'border-2 border-indigo-500 bg-white',
        'minimal' => 'bg-transparent',
        default => 'bg-indigo-50 border border-indigo-100',
    };
@endphp

<div class="hello-world-block my-8 p-6 rounded-xl {{ $styleClasses }} {{ $alignmentClasses }}" data-block-id="{{ $id }}">
    <div class="flex items-center justify-center gap-3 mb-4">
        @if($style !== 'minimal')
            <svg class="w-8 h-8 {{ $style === 'gradient' ? 'text-white' : 'text-indigo-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
            </svg>
        @endif
        <h3 class="text-2xl font-bold {{ $style === 'gradient' ? 'text-white' : ($style === 'minimal' ? 'text-gray-900' : 'text-indigo-900') }}">
            {{ $greeting }}
        </h3>
    </div>

    @if($message)
        <p class="{{ $style === 'gradient' ? 'text-indigo-100' : ($style === 'minimal' ? 'text-gray-600' : 'text-indigo-700') }} max-w-2xl mx-auto">
            {{ $message }}
        </p>
    @endif

    <div class="mt-4 pt-4 border-t {{ $style === 'gradient' ? 'border-white/20' : 'border-indigo-200' }}">
        <span class="inline-flex items-center gap-1.5 text-xs {{ $style === 'gradient' ? 'text-indigo-200' : 'text-indigo-400' }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            Powered by Hello World Plugin
        </span>
    </div>
</div>
