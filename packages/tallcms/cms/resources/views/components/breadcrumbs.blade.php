@props(['items' => [], 'overHero' => false])

@if(count($items) > 1)
<div {{ $attributes->merge([
    'class' => 'breadcrumbs text-sm px-4 sm:px-6 lg:px-8 py-2 relative z-40 ' . ($overHero ? 'text-white [&_a]:text-white/80 [&_a:hover]:text-white' : '')
]) }}>
    <ul>
        @foreach($items as $index => $item)
            <li>
                @if($index < count($items) - 1)
                    <a href="{{ $item['url'] }}">
                        @if($index === 0)
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-4 w-4 stroke-current">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                            {{ $item['name'] }}
                        @else
                            {{ $item['name'] }}
                        @endif
                    </a>
                @else
                    {{ $item['name'] }}
                @endif
            </li>
        @endforeach
    </ul>
</div>
@endif
