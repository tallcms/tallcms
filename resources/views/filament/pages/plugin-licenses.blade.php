<x-filament-panels::page>
    <div class="space-y-6">
        @if(empty($licensablePlugins))
            {{-- No Licensable Plugins --}}
            <x-filament::section>
                <div class="text-center py-8">
                    <x-heroicon-o-key class="w-12 h-12 mx-auto text-gray-400" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                        No Licensable Plugins
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        No installed plugins require licensing.
                    </p>
                </div>
            </x-filament::section>
        @else
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

                        @if($status['has_license'])
                            <div class="flex gap-2">
                                <x-filament::button
                                    wire:click="refreshLicenseStatus('{{ $pluginSlug }}')"
                                    color="gray"
                                    size="sm"
                                    icon="heroicon-o-arrow-path"
                                >
                                    Refresh
                                </x-filament::button>
                            </div>
                        @endif
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

            {{-- Activation Form --}}
            @if(collect($statuses)->contains(fn($s) => !$s['has_license']))
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
