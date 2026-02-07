@props(['record', 'level'])

@php
    use Filament\Support\Enums\Alignment;
    use Filament\Schemas\Schema;

    $infolistAlignment = $this->getInfolistAlignment();
    $infoListHiddenEndpoint = $this->getInfolistHiddenEndpoint();

    // 是否还有下级
    $hasNextLevel = is_null($level) || $level > ($record->depth + 1);

    $canCreateChildren = false;
    if ($this->showCreateChildNodeActionInRow() && $hasNextLevel) {
        $canCreateChildren = true;
    }
@endphp

<div
    x-data="{ open: $persist(true) }"
    wire:key="tree-item-{{ $record->getKey() }}"
    data-id="{{ $record->getKey() }}"
    class="fi-sn-tree-item"
    data-sortable-item
>
    <div class="fi-sn-tree-item-rowinfo flex justify-between relative group px-4 gap-4 hover:bg-gray-50 dark:hover:bg-white/5">
        <div class="flex gap-4 grow">
            <button
                class="fi-sn-tree-item-handle flex items-center ltr:rounded-l-lg rtl:rounded-r-lg"
                type="button"
                data-sortable-handle
            >
                @svg('heroicon-m-bars-2', 'text-gray-400 dark:text-gray-500 w-5 h-5 cursor-move ltr:-mr-2 rtl:-ml-2')
            </button>

            <div class="appearance-none px-3 py-4 ltr:text-left rtl:text-right inline-block text-gray-900 dark:text-white">
                <span>{{ $this->getRecordLabel($record) }}</span>
            </div>

            @if($record->children->isNotEmpty())
                <button type="button" x-on:click="open = !open" title="Toggle children" class="appearance-none text-gray-500 dark:text-gray-400">
                    <svg class="w-5 h-5 transition ease-in-out duration-200" x-bind:class="{
                        '-rotate-90': !open,
                    }" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                </button>
            @endif

            @if ($this->hasInfolist())
                <div @class([
                    'fi-sn-tree-infolist hidden grow gap-x-4 px-4 items-center',
                    match ($infoListHiddenEndpoint) {
                        'sm' => 'sm:flex',
                        'md' => 'md:flex',
                        'lg' => 'lg:flex',
                        'xl' => 'xl:flex',
                        '2xl' => '2xl:flex',
                    },
                    match ($infolistAlignment) {
                        Alignment::Left, Alignment::Start => 'justify-start',
                        Alignment::Center => 'justify-center',
                        Alignment::Right, Alignment::End => 'justify-end',
                    },
                ])>
                    {{ Schema::make($this)
                        ->record($record)
                        ->components($this->infolistSchema())
                        ->view('sn-filament-nestedset::components.pages.nestedset-infolist'); }}
                </div>
            @endif
        </div>

        <div class="flex grow-0 gap-3">
            {{-- 一级 depth = 0 --}}
            @if($canCreateChildren)
                {{ ($this->createChildAction)(['parentId' => $record->getKey()]) }}
            @endif

            {{ ($this->editAction)(['id' => $record->getKey()]) }}

            @if($this->canBeDeleted($record))
                {{ ($this->deleteAction)(['id' => $record->getKey()]) }}
            @endif
        </div>
    </div>

    @if ($hasNextLevel)
        <div x-show="open" x-collapse class="divide-y ltr:pl-6 rtl:pr-6">
            <div
                @class([
                    'fi-sn-child-tree divide-y divide-gray-200 dark:divide-white/10',
                    'border-t border-gray-200 dark:border-white/10' => $record->children->isNotEmpty()
                ])
                wire:key="tree-item-{{ $record->getKey() }}-children"
                data-id="{{ $record->getKey() }}"
                x-data="treeManager({
                    parentId: {{ $record->getKey() }}
                })"
            >
                @foreach ($record->children as $childKey => $child)
                    <x-sn-filament-nestedset::pages.nestedset-record :record="$child" key="tree-component-{{ $childKey }}" :level="$level" />
                @endforeach
            </div>
        </div>
    @endif
</div>
