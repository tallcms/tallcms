{{-- Vertical Menu Style (typically for sidebars) --}}
<ul class="space-y-2 text-gray-700">
    @foreach($items as $item)
        <x-menu-item :item="$item" :level="0" />
    @endforeach
</ul>