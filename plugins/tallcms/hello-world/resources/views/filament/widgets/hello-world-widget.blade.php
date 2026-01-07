<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Hello World Plugin
        </x-slot>

        <x-slot name="description">
            This widget is provided by the Hello World plugin
        </x-slot>

        <div class="flex items-center gap-4">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/50 rounded-full flex items-center justify-center">
                    <x-heroicon-o-sparkles class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
            </div>
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    The plugin system is working! This widget demonstrates how plugins can extend the admin panel.
                </p>
                <a href="/hello" target="_blank" class="inline-flex items-center gap-1 mt-2 text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                    Visit plugin page
                    <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4" />
                </a>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
