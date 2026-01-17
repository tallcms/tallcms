<x-filament-panels::page>
    {{-- Available Plugins (not installed) --}}
    @if($this->availablePlugins->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-sparkles class="w-5 h-5 text-primary-500" />
                    Available Plugins
                </div>
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($this->availablePlugins as $plugin)
                    <x-filament::section class="!p-4 relative">
                        {{-- Featured badge --}}
                        @if($plugin['featured'] ?? false)
                            <x-filament::badge color="primary" class="absolute top-2 right-2">
                                Featured
                            </x-filament::badge>
                        @endif

                        <div class="flex items-start gap-3">
                            {{-- Icon --}}
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-gray-100 dark:bg-white/5 flex items-center justify-center">
                                @if($plugin['icon'] ?? null)
                                    <x-dynamic-component :component="$plugin['icon']" class="w-5 h-5 text-primary-500" />
                                @else
                                    <x-heroicon-o-puzzle-piece class="w-5 h-5 text-primary-500" />
                                @endif
                            </div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <h3 class="text-base font-semibold">
                                    {{ $plugin['name'] }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                                    {{ $plugin['description'] }}
                                </p>
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                    by {{ $plugin['author'] }}
                                </p>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="mt-4 flex items-center gap-2 flex-wrap">
                            @if($plugin['download_url'] ?? null)
                                <x-filament::button
                                    tag="a"
                                    href="{{ $plugin['download_url'] }}"
                                    target="_blank"
                                    color="primary"
                                    size="sm"
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
                                    size="sm"
                                    icon="heroicon-o-shopping-cart"
                                    outlined
                                >
                                    Purchase License
                                </x-filament::button>
                            @endif
                            @if($plugin['homepage'] ?? null)
                                <x-filament::button
                                    tag="a"
                                    href="{{ $plugin['homepage'] }}"
                                    target="_blank"
                                    color="gray"
                                    size="sm"
                                    outlined
                                >
                                    Learn More
                                </x-filament::button>
                            @endif
                        </div>
                    </x-filament::section>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    {{-- Installed Plugins --}}
    @if($this->plugins->isNotEmpty())
        <div class="flex items-center gap-2 mb-4">
            <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
            <h2 class="text-lg font-semibold">Installed Plugins</h2>
        </div>
    @endif

    {{-- Plugin List --}}
    <div class="space-y-3">
        @foreach($this->plugins as $plugin)
            <x-filament::section class="!p-4">
                <div class="flex items-start justify-between gap-4">
                    {{-- Plugin Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-base font-semibold">
                                {{ $plugin['name'] }}
                            </h3>
                            <x-filament::badge color="gray" size="sm">
                                v{{ $plugin['version'] }}
                            </x-filament::badge>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $plugin['fullSlug'] }}
                            </span>
                        </div>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                            {{ $plugin['description'] ?: 'No description available.' }}
                        </p>

                        {{-- Feature Badges --}}
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @if($plugin['hasFilamentPlugin'])
                                <x-filament::badge color="info" size="sm">
                                    <x-heroicon-s-squares-2x2 class="w-3 h-3 mr-1" />
                                    Filament
                                </x-filament::badge>
                            @endif
                            @if($plugin['hasPublicRoutes'])
                                <x-filament::badge color="primary" size="sm">
                                    <x-heroicon-s-globe-alt class="w-3 h-3 mr-1" />
                                    Public Routes
                                </x-filament::badge>
                            @endif
                            @if($plugin['hasPrefixedRoutes'])
                                <x-filament::badge color="info" size="sm">
                                    <x-heroicon-s-link class="w-3 h-3 mr-1" />
                                    API Routes
                                </x-filament::badge>
                            @endif
                            @if($plugin['hasMigrations'])
                                <x-filament::badge color="warning" size="sm">
                                    <x-heroicon-s-circle-stack class="w-3 h-3 mr-1" />
                                    Migrations
                                </x-filament::badge>
                            @endif
                            @foreach($plugin['tags'] as $tag)
                                <x-filament::badge color="gray" size="sm">
                                    {{ $tag }}
                                </x-filament::badge>
                            @endforeach
                        </div>

                        {{-- Warnings --}}
                        @if(!$plugin['meetsRequirements'])
                            <div class="mt-2 flex items-center gap-1.5 text-danger-600 dark:text-danger-400">
                                <x-heroicon-s-exclamation-triangle class="w-4 h-4" />
                                <span class="text-xs">Requirements not met</span>
                            </div>
                        @elseif($plugin['hasPendingMigrations'])
                            <div class="mt-2 flex items-center gap-1.5 text-warning-600 dark:text-warning-400">
                                <x-heroicon-s-exclamation-triangle class="w-4 h-4" />
                                <span class="text-xs">Pending migrations</span>
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 shrink-0">
                        @if($plugin['hasPendingMigrations'])
                            <x-filament::button
                                wire:click="runMigrations('{{ $plugin['vendor'] }}', '{{ $plugin['slug'] }}')"
                                color="warning"
                                size="sm"
                            >
                                Run Migrations
                            </x-filament::button>
                        @endif

                        <x-filament::button
                            wire:click="showPluginDetails('{{ $plugin['vendor'] }}', '{{ $plugin['slug'] }}')"
                            color="gray"
                            size="sm"
                            outlined
                        >
                            Details
                        </x-filament::button>

                        <x-filament::button
                            wire:click="mountAction('uninstall', { vendor: '{{ $plugin['vendor'] }}', slug: '{{ $plugin['slug'] }}', name: '{{ addslashes($plugin['name']) }}' })"
                            color="danger"
                            size="sm"
                            outlined
                        >
                            Uninstall
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @endforeach
    </div>

    {{-- Empty State --}}
    @if($this->plugins->isEmpty())
        <x-filament::section>
            <div class="text-center py-8">
                <x-heroicon-o-puzzle-piece class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
                <h3 class="mt-2 text-sm font-semibold">No plugins installed</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if($this->availablePlugins->isNotEmpty())
                        Download a plugin from above or upload a ZIP file to get started.
                    @else
                        Upload a plugin ZIP file to get started.
                    @endif
                </p>
            </div>
        </x-filament::section>
    @endif

    {{-- Plugin Details Modal --}}
    <x-filament::modal id="plugin-details-modal" width="2xl" slide-over>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <span>{{ $pluginDetails['name'] ?? 'Plugin Details' }}</span>
                @if($pluginDetails)
                    <x-filament::badge color="gray" size="sm">
                        v{{ $pluginDetails['version'] }}
                    </x-filament::badge>
                @endif
            </div>
        </x-slot>

        @if($pluginDetails)
            <div class="space-y-4 text-sm">
                {{-- Description --}}
                <p class="text-gray-600 dark:text-gray-300">
                    {{ $pluginDetails['description'] ?: 'No description available.' }}
                </p>

                {{-- Quick Actions --}}
                <div class="flex gap-2">
                    @if($pluginDetails['hasPendingMigrations'] ?? false)
                        <x-filament::button
                            wire:click="runMigrations('{{ $pluginDetails['vendor'] }}', '{{ $pluginDetails['slug'] }}')"
                            color="warning"
                            size="sm"
                        >
                            Run Migrations
                        </x-filament::button>
                    @endif
                    <x-filament::button
                        wire:click="mountAction('uninstall', { vendor: '{{ $pluginDetails['vendor'] }}', slug: '{{ $pluginDetails['slug'] }}', name: '{{ addslashes($pluginDetails['name']) }}' })"
                        color="danger"
                        size="sm"
                        outlined
                    >
                        Uninstall
                    </x-filament::button>
                </div>

                {{-- Requirements Warning --}}
                @if(!$pluginDetails['meetsRequirements'])
                    <x-filament::section compact class="!bg-danger-50 dark:!bg-danger-950 !border-danger-200 dark:!border-danger-800">
                        <x-slot name="heading">
                            <span class="text-danger-700 dark:text-danger-300">Requirements Not Met</span>
                        </x-slot>
                        <ul class="text-danger-600 dark:text-danger-400 space-y-0.5">
                            @foreach($pluginDetails['unmetRequirements'] as $requirement)
                                <li>{{ $requirement }}</li>
                            @endforeach
                        </ul>
                    </x-filament::section>
                @endif

                {{-- Features --}}
                <x-filament::section compact>
                    <x-slot name="heading">Features</x-slot>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                        <div class="flex items-center gap-1.5">
                            @if($pluginDetails['hasFilamentPlugin'])
                                <x-heroicon-s-check-circle class="w-4 h-4 text-success-500" />
                                <span class="text-gray-700 dark:text-gray-300">Filament Integration</span>
                            @else
                                <x-heroicon-s-x-circle class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                                <span class="text-gray-400 dark:text-gray-500">Filament Integration</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if($pluginDetails['hasPublicRoutes'])
                                <x-heroicon-s-check-circle class="w-4 h-4 text-success-500" />
                                <span class="text-gray-700 dark:text-gray-300">Public Routes</span>
                            @else
                                <x-heroicon-s-x-circle class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                                <span class="text-gray-400 dark:text-gray-500">Public Routes</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if($pluginDetails['hasPrefixedRoutes'])
                                <x-heroicon-s-check-circle class="w-4 h-4 text-success-500" />
                                <span class="text-gray-700 dark:text-gray-300">Prefixed Routes</span>
                            @else
                                <x-heroicon-s-x-circle class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                                <span class="text-gray-400 dark:text-gray-500">Prefixed Routes</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if($pluginDetails['hasMigrations'])
                                <x-heroicon-s-check-circle class="w-4 h-4 text-success-500" />
                                <span class="text-gray-700 dark:text-gray-300">Migrations</span>
                            @else
                                <x-heroicon-s-x-circle class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                                <span class="text-gray-400 dark:text-gray-500">Migrations</span>
                            @endif
                        </div>
                    </div>
                </x-filament::section>

                {{-- Public Routes --}}
                @if(!empty($pluginDetails['publicRoutes']))
                    <x-filament::section compact>
                        <x-slot name="heading">Public Routes</x-slot>
                        <ul class="space-y-1 font-mono text-xs">
                            @foreach($pluginDetails['publicRoutes'] as $route)
                                <li class="text-gray-700 dark:text-gray-300">{{ $route }}</li>
                            @endforeach
                        </ul>
                    </x-filament::section>
                @endif

                {{-- Migrations --}}
                @if(!empty($pluginDetails['migrations']))
                    <x-filament::section compact>
                        <x-slot name="heading">Migrations</x-slot>
                        <ul class="space-y-1">
                            @foreach($pluginDetails['migrations'] as $migration)
                                <li class="flex items-center gap-2">
                                    @if($migration['ran'])
                                        <x-heroicon-s-check-circle class="w-4 h-4 text-success-500 shrink-0" />
                                    @else
                                        <x-heroicon-s-clock class="w-4 h-4 text-warning-500 shrink-0" />
                                    @endif
                                    <span class="font-mono text-xs text-gray-700 dark:text-gray-300 truncate">{{ $migration['name'] }}</span>
                                    @if($migration['batch'])
                                        <span class="text-xs text-gray-400 dark:text-gray-500">Batch {{ $migration['batch'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </x-filament::section>
                @endif

                {{-- Tags --}}
                @if(!empty($pluginDetails['tags']))
                    <x-filament::section compact>
                        <x-slot name="heading">Tags</x-slot>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($pluginDetails['tags'] as $tag)
                                <x-filament::badge color="gray" size="sm">
                                    {{ $tag }}
                                </x-filament::badge>
                            @endforeach
                        </div>
                    </x-filament::section>
                @endif

                {{-- Plugin Information --}}
                <x-filament::section compact>
                    <x-slot name="heading">Information</x-slot>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Author</dt>
                            <dd class="">
                                @if($pluginDetails['authorUrl'])
                                    <a href="{{ $pluginDetails['authorUrl'] }}" target="_blank" class="text-primary-600 hover:underline">
                                        {{ $pluginDetails['author'] }}
                                    </a>
                                @else
                                    {{ $pluginDetails['author'] }}
                                @endif
                            </dd>
                        </div>
                        @if($pluginDetails['license'])
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">License</dt>
                                <dd class="">{{ $pluginDetails['license'] }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Namespace</dt>
                            <dd class="font-mono text-xs">{{ $pluginDetails['namespace'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Provider</dt>
                            <dd class="font-mono text-xs truncate" title="{{ $pluginDetails['provider'] }}">{{ class_basename($pluginDetails['provider']) }}</dd>
                        </div>
                    </dl>
                </x-filament::section>

                {{-- Compatibility --}}
                @if(!empty($pluginDetails['compatibility']))
                    <x-filament::section compact>
                        <x-slot name="heading">Compatibility</x-slot>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                            @if(!empty($pluginDetails['compatibility']['php']))
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">PHP</dt>
                                    <dd class="font-mono text-xs">{{ $pluginDetails['compatibility']['php'] }}</dd>
                                </div>
                            @endif
                            @if(!empty($pluginDetails['compatibility']['tallcms']))
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">TallCMS</dt>
                                    <dd class="font-mono text-xs">{{ $pluginDetails['compatibility']['tallcms'] }}</dd>
                                </div>
                            @endif
                            @if(!empty($pluginDetails['compatibility']['extensions']))
                                <div class="col-span-2">
                                    <dt class="text-gray-500 dark:text-gray-400">Extensions</dt>
                                    <dd class="">{{ implode(', ', $pluginDetails['compatibility']['extensions']) }}</dd>
                                </div>
                            @endif
                        </dl>
                    </x-filament::section>
                @endif

                {{-- Backups / Rollback --}}
                @if(!empty($pluginDetails['backups']))
                    <x-filament::section compact>
                        <x-slot name="heading">Available Backups</x-slot>
                        <ul class="space-y-2">
                            @foreach($pluginDetails['backups'] as $backup)
                                <li class="flex items-center justify-between">
                                    <div>
                                        <span class="font-mono text-xs text-gray-700 dark:text-gray-300">v{{ $backup['version'] }}</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500 ml-2">{{ $backup['date'] }}</span>
                                    </div>
                                    <x-filament::button
                                        wire:click="mountAction('rollback', { vendor: '{{ $pluginDetails['vendor'] }}', slug: '{{ $pluginDetails['slug'] }}', name: '{{ addslashes($pluginDetails['name']) }}', version: '{{ $backup['version'] }}' })"
                                        color="warning"
                                        size="xs"
                                        outlined
                                    >
                                        Rollback
                                    </x-filament::button>
                                </li>
                            @endforeach
                        </ul>
                    </x-filament::section>
                @endif

                {{-- Footer --}}
                <div class="pt-3 border-t border-gray-200 dark:border-white/10 flex items-center justify-between">
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-mono truncate max-w-[70%]" title="{{ $pluginDetails['path'] }}">
                        {{ $pluginDetails['path'] }}
                    </p>
                    @if($pluginDetails['homepage'])
                        <a
                            href="{{ $pluginDetails['homepage'] }}"
                            target="_blank"
                            class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 whitespace-nowrap"
                        >
                            Homepage
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </x-filament::modal>
</x-filament-panels::page>
