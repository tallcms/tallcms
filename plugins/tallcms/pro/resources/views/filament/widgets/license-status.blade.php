@php
    $status = $this->getStatus();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if($status['status'] === 'active')
                    <div class="w-10 h-10 rounded-full bg-success-100 dark:bg-success-500/20 flex items-center justify-center">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-success-600 dark:text-success-400" />
                    </div>
                @elseif($status['status'] === 'none')
                    <div class="w-10 h-10 rounded-full bg-warning-100 dark:bg-warning-500/20 flex items-center justify-center">
                        <x-heroicon-o-key class="w-5 h-5 text-warning-600 dark:text-warning-400" />
                    </div>
                @else
                    <div class="w-10 h-10 rounded-full bg-danger-100 dark:bg-danger-500/20 flex items-center justify-center">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-danger-600 dark:text-danger-400" />
                    </div>
                @endif

                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium">TallCMS Pro</span>
                        <x-filament::badge size="sm" :color="$status['status_color']">
                            {{ $status['status_label'] }}
                        </x-filament::badge>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $status['message'] }}
                    </p>
                </div>
            </div>

            <a href="{{ \Tallcms\Pro\Filament\Pages\ProLicense::getUrl() }}" class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400">
                @if($status['has_license'])
                    Manage License
                @else
                    Activate License
                @endif
                <x-heroicon-m-arrow-right class="w-4 h-4 inline" />
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
