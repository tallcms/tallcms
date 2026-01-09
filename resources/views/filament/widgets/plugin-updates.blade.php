<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="rounded-full bg-warning-100 dark:bg-warning-500/20 p-3">
                    <x-heroicon-o-arrow-path class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                    Plugin Updates Available
                </h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ count($updates) }} {{ Str::plural('plugin', count($updates)) }} {{ count($updates) === 1 ? 'has' : 'have' }} updates available.
                </p>

                <div class="mt-3 space-y-2">
                    @foreach($updates as $slug => $update)
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $update['plugin_name'] }}
                                </span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $update['current_version'] }} &rarr; {{ $update['latest_version'] }}
                                </span>
                            </div>
                            @if($update['download_url'])
                                <a
                                    href="{{ $update['download_url'] }}"
                                    target="_blank"
                                    class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                                >
                                    Download
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <a
                        href="{{ route('filament.admin.pages.plugin-licenses') }}"
                        class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                    >
                        View Plugin Licenses &rarr;
                    </a>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
