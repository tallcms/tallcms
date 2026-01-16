{{-- Vertical Menu Style (typically for sidebars) --}}
<ul class="menu w-full">
    @foreach($items as $item)
        @include('tallcms::components.menu-item', ['item' => $item, 'level' => 0])
    @endforeach
</ul>
