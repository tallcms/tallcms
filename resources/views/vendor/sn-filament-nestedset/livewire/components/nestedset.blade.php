@php
    $nestedset = $this->getNestedset();
@endphp

<ul
    @class([
        'w-full flex flex-col',
    ])
    role="menu"
>
    @forelse($nestedset as $treeKey => $record)
        <x-dynamic-component 
            @class([
                'w-full',
            ]) 
            :component="$this->getRecordView()" 
            key="categories-component-{{ $record->getKey() }}" 
            :record="$record" 
            :first="$loop->first" 
            :last="$loop->last" 
            :current-level="1" 
        />
    @empty
        <li class="w-full px-3 py-2 text-center">
            {{ $this->getEmptyLabel() ?: __('sn-filament-nestedset::nestedset.tree.empty_label')}}
        </li>
    @endforelse
</ul>
