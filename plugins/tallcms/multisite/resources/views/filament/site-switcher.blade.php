<div class="px-3 py-2" x-data="{ open: false }">
    <div class="relative">
        <button
            @click="open = !open"
            class="flex w-full items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
            <x-heroicon-m-globe-alt class="h-4 w-4 text-gray-400" />
            <span class="flex-1 truncate text-left">
                {{ $allSitesMode ? 'All Sites' : ($currentSite?->name ?? 'Select Site') }}
            </span>
            <x-heroicon-m-chevron-up-down class="h-4 w-4 text-gray-400" />
        </button>

        <div
            x-show="open"
            @click.outside="open = false"
            x-transition
            class="absolute left-0 right-0 z-50 mt-1 rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
            {{-- All Sites option --}}
            <form method="POST" action="{{ url('/_plugins/tallcms/multisite/switch-site') }}">
                @csrf
                <input type="hidden" name="site_id" value="">
                <button
                    type="submit"
                    @class([
                        'flex w-full items-center gap-2 px-3 py-2 text-sm transition hover:bg-gray-50 dark:hover:bg-gray-700',
                        'font-semibold text-primary-600 dark:text-primary-400' => $allSitesMode,
                        'text-gray-600 dark:text-gray-400' => !$allSitesMode,
                    ])
                >
                    <x-heroicon-m-squares-2x2 class="h-4 w-4" />
                    All Sites
                </button>
            </form>

            <div class="border-t border-gray-100 dark:border-gray-700"></div>

            {{-- Individual sites --}}
            @foreach ($sites as $site)
                <form method="POST" action="{{ url('/_plugins/tallcms/multisite/switch-site') }}">
                    @csrf
                    <input type="hidden" name="site_id" value="{{ $site->id }}">
                    <button
                        type="submit"
                        @class([
                            'flex w-full items-center gap-2 px-3 py-2 text-sm transition hover:bg-gray-50 dark:hover:bg-gray-700',
                            'font-semibold text-primary-600 dark:text-primary-400' => !$allSitesMode && $currentSite?->id === $site->id,
                            'text-gray-600 dark:text-gray-400' => $allSitesMode || $currentSite?->id !== $site->id,
                        ])
                    >
                        <x-heroicon-m-globe-alt class="h-4 w-4" />
                        <span class="flex-1 truncate text-left">{{ $site->name }}</span>
                        <span class="text-xs text-gray-400">{{ $site->domain }}</span>
                    </button>
                </form>
            @endforeach
        </div>
    </div>
</div>
