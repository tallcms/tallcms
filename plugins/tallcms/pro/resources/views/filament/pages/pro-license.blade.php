<x-filament-panels::page>
    <div class="space-y-6">
        {{-- License Status Card --}}
        <x-filament::section>
            <x-slot name="heading">
                License Status
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
            </div>

            @if($status['has_license'])
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
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
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Last Validated</dt>
                            <dd>{{ $status['last_validated'] ?? 'Never' }}</dd>
                        </div>
                    </dl>
                </div>
            @endif
        </x-filament::section>

        {{-- Activation Form --}}
        @if(!$status['has_license'])
            <x-filament::section>
                <x-slot name="heading">
                    Activate License
                </x-slot>

                <x-slot name="description">
                    Enter your license key to activate TallCMS Pro features.
                </x-slot>

                <form wire:submit="activateLicense" class="space-y-4">
                    {{ $this->form }}

                    <x-filament::button type="submit">
                        Activate License
                    </x-filament::button>
                </form>
            </x-filament::section>
        @else
            <x-filament::section>
                <x-slot name="heading">
                    Manage License
                </x-slot>

                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Deactivating your license will remove it from this site. You can reactivate it on this or another site later.
                </p>

                <x-filament::button
                    wire:click="deactivateLicense"
                    color="danger"
                    outlined
                >
                    Deactivate License
                </x-filament::button>
            </x-filament::section>
        @endif

        {{-- Pro Features --}}
        <x-filament::section>
            <x-slot name="heading">
                Pro Features
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @php
                    $features = [
                        ['icon' => 'heroicon-o-squares-plus', 'title' => 'Advanced Blocks', 'desc' => 'Accordion, Tabs, Counters, Video, Maps, and more'],
                        ['icon' => 'heroicon-o-chart-bar', 'title' => 'Analytics Dashboard', 'desc' => 'Google Analytics, Plausible, Fathom integration'],
                        ['icon' => 'heroicon-o-envelope', 'title' => 'Email Marketing', 'desc' => 'Mailchimp, ConvertKit, Sendinblue'],
                        ['icon' => 'heroicon-o-map', 'title' => 'Interactive Maps', 'desc' => 'Google Maps, Mapbox, OpenStreetMap'],
                        ['icon' => 'heroicon-o-video-camera', 'title' => 'Video Embeds', 'desc' => 'YouTube, Vimeo, self-hosted'],
                        ['icon' => 'heroicon-o-code-bracket', 'title' => 'Code Snippets', 'desc' => 'Syntax highlighting with copy'],
                    ];
                @endphp

                @foreach($features as $feature)
                    <div class="flex gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="flex-shrink-0">
                            <x-dynamic-component :component="$feature['icon']" class="w-5 h-5 text-primary-500" />
                        </div>
                        <div>
                            <h4 class="font-medium text-sm">{{ $feature['title'] }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $feature['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
