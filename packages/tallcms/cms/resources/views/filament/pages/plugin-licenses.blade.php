<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $availablePlugins = $this->getAvailablePlugins();
        @endphp

        {{-- Available Plugins from Catalog --}}
        @if(!empty($availablePlugins))
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-sparkles class="w-5 h-5 text-primary-500" />
                        Available Premium Plugins
                    </div>
                </x-slot>

                <x-slot name="description">
                    Download and install premium plugins to unlock advanced features.
                </x-slot>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($availablePlugins as $plugin)
                        <div class="bg-gradient-to-br from-primary-50 to-white dark:from-primary-900/20 dark:to-gray-800 rounded-lg border border-primary-200 dark:border-primary-700 p-4 relative overflow-hidden">
                            @if($plugin['featured'] ?? false)
                                <div class="absolute top-0 right-0 bg-primary-500 text-white text-xs font-bold px-2 py-0.5 rounded-bl">
                                    Featured
                                </div>
                            @endif

                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center">
                                    @if($plugin['icon'] ?? null)
                                        <x-dynamic-component :component="$plugin['icon']" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                    @else
                                        <x-heroicon-o-puzzle-piece class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                    @endif
                                </div>

                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $plugin['name'] }}
                                    </h4>
                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 line-clamp-2">
                                        {{ $plugin['description'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 flex items-center gap-2">
                                @if($plugin['download_url'] ?? null)
                                    <x-filament::button
                                        tag="a"
                                        href="{{ $plugin['download_url'] }}"
                                        target="_blank"
                                        color="primary"
                                        size="xs"
                                        icon="heroicon-o-arrow-down-tray"
                                    >
                                        Download
                                    </x-filament::button>
                                @endif
                                @if($plugin['purchase_url'] ?? null)
                                    <x-filament::button
                                        tag="a"
                                        href="{{ $plugin['purchase_url'] }}"
                                        target="_blank"
                                        color="gray"
                                        size="xs"
                                        icon="heroicon-o-shopping-cart"
                                        outlined
                                    >
                                        Purchase License
                                    </x-filament::button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        @if(empty($licensablePlugins) && empty($availablePlugins))
            {{-- No Licensable Plugins at all --}}
            <x-filament::section>
                <div class="text-center py-8">
                    <x-heroicon-o-key class="w-12 h-12 mx-auto text-gray-400" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                        No Licensable Plugins
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        No plugins require licensing.
                    </p>
                </div>
            </x-filament::section>
        @elseif(!empty($licensablePlugins))
            {{-- Plugin License Cards --}}
            @foreach($statuses as $pluginSlug => $status)
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            {{ $status['plugin_name'] ?? $pluginSlug }}
                            <span class="text-sm font-normal text-gray-500">v{{ $status['plugin_version'] ?? '1.0.0' }}</span>
                        </div>
                    </x-slot>

                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0">
                            @if($status['status'] === 'active')
                                <div class="w-12 h-12 rounded-full bg-success-100 dark:bg-success-500/20 flex items-center justify-center">
                                    <x-heroicon-o-check-circle class="w-6 h-6 text-success-600 dark:text-success-400" />
                                </div>
                            @elseif($status['status'] === 'none')
                                <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-500/20 flex items-center justify-center">
                                    <x-heroicon-o-key class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                                </div>
                            @elseif($status['status'] === 'expired')
                                <div class="w-12 h-12 rounded-full bg-warning-100 dark:bg-warning-500/20 flex items-center justify-center">
                                    <x-heroicon-o-clock class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                                </div>
                            @else
                                <div class="w-12 h-12 rounded-full bg-danger-100 dark:bg-danger-500/20 flex items-center justify-center">
                                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-danger-600 dark:text-danger-400" />
                                </div>
                            @endif
                        </div>

                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-medium">
                                    {{ $status['status_label'] }}
                                </h3>
                                <x-filament::badge :color="$status['status_color']">
                                    {{ $status['status'] }}
                                </x-filament::badge>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $status['message'] }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            {{-- Download button - always shown if URL exists --}}
                            @if($status['download_url'] ?? null)
                                <x-filament::button
                                    tag="a"
                                    href="{{ $status['download_url'] }}"
                                    target="_blank"
                                    color="gray"
                                    size="sm"
                                    icon="heroicon-o-arrow-down-tray"
                                >
                                    Download
                                </x-filament::button>
                            @endif

                            @if($status['has_license'] && ($status['is_valid'] ?? false))
                                {{-- Valid license: Check Updates + Refresh --}}
                                <x-filament::button
                                    wire:click="checkForUpdates('{{ $pluginSlug }}')"
                                    color="gray"
                                    size="sm"
                                    icon="heroicon-o-sparkles"
                                >
                                    Check Updates
                                </x-filament::button>
                                <x-filament::button
                                    wire:click="refreshLicenseStatus('{{ $pluginSlug }}')"
                                    color="gray"
                                    size="sm"
                                    icon="heroicon-o-arrow-path"
                                >
                                    Refresh
                                </x-filament::button>
                            @else
                                {{-- No license or invalid: Purchase + optional Refresh --}}
                                @if($status['purchase_url'] ?? null)
                                    <x-filament::button
                                        tag="a"
                                        href="{{ $status['purchase_url'] }}"
                                        target="_blank"
                                        color="primary"
                                        size="sm"
                                        icon="heroicon-o-shopping-cart"
                                    >
                                        Purchase License
                                    </x-filament::button>
                                @endif
                                @if($status['has_license'])
                                    <x-filament::button
                                        wire:click="refreshLicenseStatus('{{ $pluginSlug }}')"
                                        color="gray"
                                        size="sm"
                                        icon="heroicon-o-arrow-path"
                                    >
                                        Refresh
                                    </x-filament::button>
                                @endif
                            @endif
                        </div>
                    </div>

                    @if($status['has_license'])
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">License Key</dt>
                                    <dd class="font-mono">{{ $status['license_key'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">Domain</dt>
                                    <dd>{{ $status['domain'] ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">Activated</dt>
                                    <dd>{{ $status['activated_at'] ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">Expires</dt>
                                    <dd>{{ $status['expires_at'] ?? 'Never' }}</dd>
                                </div>
                            </dl>

                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-xs text-gray-400">
                                    Last validated: {{ $status['last_validated'] ?? 'Never' }}
                                </span>
                                <x-filament::button
                                    wire:click="deactivateLicense('{{ $pluginSlug }}')"
                                    color="danger"
                                    outlined
                                    size="sm"
                                >
                                    Deactivate
                                </x-filament::button>
                            </div>
                        </div>
                    @endif
                </x-filament::section>
            @endforeach

            {{-- Activation Form - show when no license OR license is invalid/expired --}}
            @if(collect($statuses)->contains(fn($s) => !$s['has_license'] || !($s['is_valid'] ?? false)))
                <x-filament::section>
                    <x-slot name="heading">
                        Activate License
                    </x-slot>

                    <x-slot name="description">
                        Enter a license key to activate a plugin.
                    </x-slot>

                    <form wire:submit="activateLicense" class="space-y-4">
                        {{ $this->form }}

                        <x-filament::button type="submit">
                            Activate License
                        </x-filament::button>
                    </form>
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament-panels::page>
