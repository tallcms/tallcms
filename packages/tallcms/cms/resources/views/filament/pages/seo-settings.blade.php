<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-8 flex items-center gap-4">
            <x-filament::button type="submit" color="primary">
                Save Settings
            </x-filament::button>

            <x-filament::button type="button" color="gray" wire:click="clearSitemapCache">
                Clear Sitemap Cache
            </x-filament::button>
        </div>
    </form>

    @if(file_exists(public_path('robots.txt')))
        <div class="mt-8 p-4 rounded-lg bg-warning-50 dark:bg-warning-500/10 border border-warning-300 dark:border-warning-500/50">
            <div class="flex gap-3">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-500 flex-shrink-0 mt-0.5" />
                <div>
                    <h3 class="font-medium text-warning-800 dark:text-warning-200">Static robots.txt Detected</h3>
                    <p class="mt-1 text-sm text-warning-700 dark:text-warning-300">
                        A static <code class="px-1 bg-warning-200/50 dark:bg-warning-500/20 rounded">public/robots.txt</code> file exists and may be served instead of the dynamic one.
                        Delete this file manually to use the database-backed robots.txt settings.
                    </p>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
