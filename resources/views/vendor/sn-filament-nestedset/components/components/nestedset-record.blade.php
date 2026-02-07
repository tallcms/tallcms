@props(['record', 'first', 'last', 'current-level'])

@php
    $hasChild = $record->children->count() > 0;
    $hasActive = $this->getHasActive($record);
@endphp

<li
    class="flex flex-col"
    @if ($hasChild)
        x-data="{ isExpanded: {{ $hasActive ? 'true' : 'false' }} }"
        aria-controls="accordionItemCategory{{$record->id}}"
        :aria-expanded="isExpanded ? 'true' : 'false'"
        aria-haspopup="true"
    @endif
    role="menuitem"
>
    <a @class([
            'flex w-full h-10 justify-between items-center px-2 gap-2 rounded-md group',
            'hover:text-primary-500 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-white/5',
            'text-gray-900 dark:text-white' => !$hasActive,
            'text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-500/10 font-medium' => $hasActive,
        ])
        @if ($hasChild)
            @click="isExpanded = ! isExpanded"
            wire:click="$dispatch('sn-filament-nestedset-node-click', { recordId: {{ $record->id }}, hasChild: {{ $hasChild ? 1 : 0 }} })"
            {{ $this->getRecordUrl($record) ?? 'href=javascript:;' }}
        @else
            wire:click="$dispatch('sn-filament-nestedset-leaf-click', { recordId: {{ $record->id }}, hasChild: {{ $hasChild ? 1 : 0 }} })"
            {{ $this->getRecordUrl($record) ?? 'href=javascript:;' }}
        @endif
    >
        <div class="flex items-center gap-1">
            @if ($currentLevel > 2)
                @for ($i = 0; $i < ($currentLevel - 2); $i++)
                    <div class="relative flex h-12 w-6 items-center justify-center">
                        <div class="absolute h-full w-px bg-gray-400 dark:bg-gray-500"></div>
                    </div>
                @endfor
            @endif

            @if ($currentLevel > 1)
                <div class="relative flex h-7 w-6 items-center justify-center">
                    @if (!$first)
                        <div class="absolute -top-1/2 bottom-1/2 w-px bg-gray-400 dark:bg-gray-500"></div>
                    @endif
                    @if (!$last)
                        <div class="absolute -bottom-1/2 top-1/2 w-px bg-gray-400 dark:bg-gray-500"></div>
                    @endif
                    <div @class([
                        'relative h-2.5 w-2.5 rounded-full border-2',
                        'border-gray-400 dark:border-gray-400 bg-white dark:bg-gray-900 group-hover:border-primary-500' => !$hasActive,
                        'border-primary-500 bg-primary-500' => $hasActive,
                    ])>
                    </div>
                </div>
            @endif
            {{ $this->getRecordLabel($record) }}
        </div>
        @if ($hasChild)
            <x-filament::icon icon="heroicon-m-chevron-down" class="size-6 font-bold transform transition-transform duration-300" ::class="isExpanded ? 'rotate-180' : ''" aria-hidden="true" />
        @endif
    </a>

    @if ($hasChild) 
        @php
            $currentLevel++;
        @endphp
        <ul @class([
            'w-full flex flex-col',
        ])
            id="accordionItemCategory{{$record->id}}"
            x-cloak x-show="isExpanded"
            aria-labelledby="controlsAccordionItemOne{{$record->id}}"
            x-collapse
            role="menu"
        >
            @foreach ($record->children as $child)
                <x-dynamic-component 
                    @class([
                        'w-full',
                    ]) 
                    :component="$this->getRecordView()" 
                    key="categories-component-{{ $child->getKey() }}" 
                    :record="$child" 
                    :first="$loop->first" 
                    :last="$loop->last" 
                    :current-level="$currentLevel" 
                />
            @endforeach
        </ul>
    @endif 
</li>
