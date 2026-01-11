{{-- Vertical Menu Style (typically for sidebars) --}}
<ul class="menu w-full">
    @foreach($items as $item)
        <x-menu-item :item="$item" :level="0" />
    @endforeach
</ul>