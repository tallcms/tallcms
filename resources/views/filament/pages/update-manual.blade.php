<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-s-exclamation-triangle class="w-6 h-6 text-amber-500" />
                Manual Update Required
            </div>
        </x-slot>

        <div class="space-y-6">
            <p class="text-gray-700 dark:text-gray-300">
                Automatic updates are not available on this server. This is typically because:
            </p>

            <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 space-y-1 ml-4">
                @if(!$this->execAvailable)
                    <li>The <code class="px-1 py-0.5 bg-gray-100 dark:bg-white/10 rounded text-sm">exec()</code> function is disabled</li>
                @endif
                @if(!$this->queueAvailable)
                    <li>Queue driver is set to "sync" (no background job processing)</li>
                @endif
            </ul>

            <div class="p-4 bg-gray-50 dark:bg-white/5 rounded-lg border border-gray-200 dark:border-white/10">
                <p class="font-medium text-gray-900 dark:text-white mb-3">
                    Run this command via SSH or your hosting control panel:
                </p>
                <div class="relative">
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg text-sm overflow-x-auto font-mono">php artisan tallcms:update --target={{ $this->targetVersion }} --force</pre>
                    <button
                        type="button"
                        x-data="{ copied: false }"
                        x-on:click="
                            navigator.clipboard.writeText('php artisan tallcms:update --target={{ $this->targetVersion }} --force');
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                        "
                        class="absolute top-2 right-2 p-2 text-gray-400 hover:text-white transition-colors"
                        title="Copy to clipboard"
                    >
                        <template x-if="!copied">
                            <x-heroicon-o-clipboard class="w-5 h-5" />
                        </template>
                        <template x-if="copied">
                            <x-heroicon-s-check class="w-5 h-5 text-green-400" />
                        </template>
                    </button>
                </div>
            </div>

            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">After running the command:</h4>
                <ol class="list-decimal list-inside text-blue-800 dark:text-blue-200 space-y-1 text-sm">
                    <li>Wait for the update to complete (usually 1-2 minutes)</li>
                    <li>Click "Check Progress" below to verify the update status</li>
                    <li>Clear your browser cache if you notice any issues</li>
                </ol>
            </div>

            @if($this->updateState['version'] ?? null)
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-arrow-up-circle class="w-5 h-5" />
                    <span>Target version: <strong class="text-gray-900 dark:text-white">v{{ $this->updateState['version'] }}</strong></span>
                </div>
            @endif
        </div>
    </x-filament::section>

    <div class="flex justify-between items-center">
        <x-filament::button
            wire:click="cancelUpdate"
            color="gray"
        >
            Cancel Update
        </x-filament::button>

        <x-filament::button
            wire:click="checkProgress"
        >
            <x-heroicon-s-arrow-path class="w-5 h-5 mr-2" />
            Check Progress
        </x-filament::button>
    </div>
</x-filament-panels::page>
