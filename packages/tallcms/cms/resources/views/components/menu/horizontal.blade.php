{{-- Horizontal Menu Style (typically for headers) --}}
<ul class="menu menu-horizontal px-1">
    @foreach($items as $item)
        @include('tallcms::components.menu-item', ['item' => $item, 'level' => 0])
    @endforeach
</ul>
