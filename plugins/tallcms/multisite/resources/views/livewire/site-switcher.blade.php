<div class="px-3 py-2" x-data="{ open: @entangle('isOpen') }" @keydown.escape.window="open = false">
    {{-- Trigger Button --}}
    <button
        @click="open = !open"
        class="flex w-full items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
    >
        <x-heroicon-m-globe-alt class="h-4 w-4 text-gray-400" />
        <span class="flex-1 truncate text-left">
            {{ $this->isAllSitesMode ? 'All Sites' : ($this->currentSite?->name ?? 'Select Site') }}
        </span>
        <x-heroicon-m-chevron-up-down class="h-4 w-4 text-gray-400" />
    </button>

    {{-- Modal --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.outside="open = false"
        class="absolute left-3 right-3 z-50 mt-1 max-h-[28rem] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800"
        style="display: none"
    >
        {{-- Search Input --}}
        <div class="border-b border-gray-100 p-2 dark:border-gray-700">
            <div class="relative">
                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Search sites..."
                    class="w-full rounded-lg border-0 bg-gray-50 py-2 px-3 text-sm text-gray-700 placeholder-gray-400 ring-1 ring-gray-200 focus:bg-white focus:ring-2 focus:ring-primary-500 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-600 dark:focus:ring-primary-500"
                    x-ref="searchInput"
                    @keydown.escape="open = false"
                />
                @if($search)
                    <button wire:click="$set('search', '')" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <x-heroicon-m-x-mark class="h-4 w-4" />
                    </button>
                @endif
            </div>
        </div>

        <div class="max-h-[22rem] overflow-y-auto">
            {{-- All Sites Option (super-admin only) --}}
            @if($this->canUseAllSites)
                <button
                    wire:click="switchSite(null)"
                    @class([
                        'flex w-full items-center gap-2 px-3 py-2 text-sm transition hover:bg-gray-50 dark:hover:bg-gray-700',
                        'font-semibold text-primary-600 dark:text-primary-400' => $this->isAllSitesMode,
                        'text-gray-600 dark:text-gray-400' => !$this->isAllSitesMode,
                    ])
                >
                    <x-heroicon-m-squares-2x2 class="h-4 w-4 flex-shrink-0" />
                    <span class="flex-1 text-left">All Sites</span>
                    <span class="text-xs text-gray-400">{{ $this->totalSites }} sites</span>
                </button>

                <div class="border-t border-gray-100 dark:border-gray-700"></div>
            @endif

            @if(!$search)
                {{-- Recent Sites (only when not searching) --}}
                @if($this->recentSites->isNotEmpty())
                    <div class="px-3 pb-1 pt-2 text-xs font-semibold uppercase tracking-wider text-gray-400">
                        Recent
                    </div>
                    @foreach ($this->recentSites as $site)
                        <button
                            wire:click="switchSite({{ $site->id }})"
                            @class([
                                'flex w-full items-center gap-2 px-3 py-1.5 text-sm transition hover:bg-gray-50 dark:hover:bg-gray-700',
                                'font-semibold text-primary-600 dark:text-primary-400' => !$this->isAllSitesMode && $this->currentSite?->id === $site->id,
                                'text-gray-600 dark:text-gray-400' => $this->isAllSitesMode || $this->currentSite?->id !== $site->id,
                            ])
                        >
                            <x-heroicon-m-clock class="h-3.5 w-3.5 flex-shrink-0 text-gray-300" />
                            <span class="flex-1 truncate text-left">{{ $site->name }}</span>
                            <span class="text-xs text-gray-400">{{ $site->domain }}</span>
                        </button>
                    @endforeach
                    <div class="border-t border-gray-100 dark:border-gray-700"></div>
                @endif
            @endif

            {{-- Site List --}}
            <div class="px-3 pb-1 pt-2 text-xs font-semibold uppercase tracking-wider text-gray-400">
                @if($search)
                    Results
                @elseif($this->canUseAllSites)
                    All Sites
                @else
                    Your Sites
                @endif
            </div>

            @forelse ($this->filteredSites as $site)
                <button
                    wire:click="switchSite({{ $site->id }})"
                    @class([
                        'flex w-full items-center gap-2 px-3 py-1.5 text-sm transition hover:bg-gray-50 dark:hover:bg-gray-700',
                        'font-semibold text-primary-600 dark:text-primary-400' => !$this->isAllSitesMode && $this->currentSite?->id === $site->id,
                        'text-gray-600 dark:text-gray-400' => $this->isAllSitesMode || $this->currentSite?->id !== $site->id,
                    ])
                >
                    <x-heroicon-m-globe-alt class="h-3.5 w-3.5 flex-shrink-0" />
                    <span class="flex-1 truncate text-left">{{ $site->name }}</span>
                    <span class="text-xs text-gray-400">{{ $site->domain }}</span>
                    @if(!$this->isAllSitesMode && $this->currentSite?->id === $site->id)
                        <x-heroicon-m-check class="h-4 w-4 text-primary-500" />
                    @endif
                </button>
            @empty
                <div class="px-3 py-4 text-center text-sm text-gray-400">
                    No sites found
                </div>
            @endforelse
        </div>
    </div>
</div>
