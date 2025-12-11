{{-- Horizontal Menu Style (typically for headers) --}}
<ul class="flex flex-wrap items-center space-x-8">
    @foreach($items as $item)
        <x-menu-item :item="$item" :level="0" />
    @endforeach
</ul>