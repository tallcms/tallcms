<x-filament-panels::page>
    {{-- Plugin List --}}
    <div class="space-y-4">
        @foreach($this->plugins as $plugin)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-start justify-between gap-4">
                    {{-- Plugin Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ $plugin['name'] }}
                            </h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                v{{ $plugin['version'] }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $plugin['fullSlug'] }}
                            </span>
                        </div>

                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                            {{ $plugin['description'] ?: 'No description available.' }}
                        </p>

                        {{-- Feature Badges --}}
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @if($plugin['hasFilamentPlugin'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                                    <x-heroicon-s-squares-2x2 class="w-3 h-3 mr-1" />
                                    Filament
                                </span>
                            @endif
                            @if($plugin['hasPublicRoutes'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                    <x-heroicon-s-globe-alt class="w-3 h-3 mr-1" />
                                    Public Routes
                                </span>
                            @endif
                            @if($plugin['hasPrefixedRoutes'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300">
                                    <x-heroicon-s-link class="w-3 h-3 mr-1" />
                                    API Routes
                                </span>
                            @endif
                            @if($plugin['hasMigrations'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                                    <x-heroicon-s-circle-stack class="w-3 h-3 mr-1" />
                                    Migrations
                                </span>
                            @endif
                            @foreach($plugin['tags'] as $tag)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                    {{ $tag }}
                                </span>
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
            </div>
        @endforeach
    </div>

    {{-- Empty State --}}
    @if($this->plugins->isEmpty())
        <div class="text-center py-12">
            <x-heroicon-o-puzzle-piece class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No plugins installed</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Upload a plugin ZIP file to get started.
            </p>
        </div>
    @endif

    {{-- Plugin Details Modal --}}
    <x-filament::modal id="plugin-details-modal" width="2xl" slide-over>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <span>{{ $pluginDetails['name'] ?? 'Plugin Details' }}</span>
                @if($pluginDetails)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        v{{ $pluginDetails['version'] }}
                    </span>
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
                    <div class="p-3 bg-danger-50 dark:bg-danger-900/20 rounded-lg border border-danger-200 dark:border-danger-800">
                        <p class="font-medium text-danger-700 dark:text-danger-300">Requirements Not Met</p>
                        <ul class="text-danger-600 dark:text-danger-400 mt-1 space-y-0.5">
                            @foreach($pluginDetails['unmetRequirements'] as $requirement)
                                <li>{{ $requirement }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Features --}}
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Features</h4>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                        <div class="flex items-center gap-1.5">
                            @if($pluginDetails['hasFilamentPlugin'])
                                <x-heroicon-s-check-circle class="w-4 h-4 text-green-500" />
                                <span class="text-gray-700 dark:text-gray-300">Filament Integration</span>
                            @else
                                <x-heroicon-s-x-circle class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                                <span class="text-gray-400 dark:text-gray-500">Filament Integration</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if($pluginDetails['hasPublicRoutes'])
                                <x-heroicon-s-check-circle class="w-4 h-4 text-green-500" />
                                <span class="text-gray-700 dark:text-gray-300">Public Routes</span>
                            @else
                                <x-heroicon-s-x-circle class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                                <span class="text-gray-400 dark:text-gray-500">Public Routes</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if($pluginDetails['hasPrefixedRoutes'])
                                <x-heroicon-s-check-circle class="w-4 h-4 text-green-500" />
                                <span class="text-gray-700 dark:text-gray-300">Prefixed Routes</span>
                            @else
                                <x-heroicon-s-x-circle class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                                <span class="text-gray-400 dark:text-gray-500">Prefixed Routes</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if($pluginDetails['hasMigrations'])
                                <x-heroicon-s-check-circle class="w-4 h-4 text-green-500" />
                                <span class="text-gray-700 dark:text-gray-300">Migrations</span>
                            @else
                                <x-heroicon-s-x-circle class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                                <span class="text-gray-400 dark:text-gray-500">Migrations</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Public Routes --}}
                @if(!empty($pluginDetails['publicRoutes']))
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Public Routes</h4>
                        <ul class="space-y-1 font-mono text-xs">
                            @foreach($pluginDetails['publicRoutes'] as $route)
                                <li class="text-gray-700 dark:text-gray-300">{{ $route }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Migrations --}}
                @if(!empty($pluginDetails['migrations']))
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Migrations</h4>
                        <ul class="space-y-1">
                            @foreach($pluginDetails['migrations'] as $migration)
                                <li class="flex items-center gap-2">
                                    @if($migration['ran'])
                                        <x-heroicon-s-check-circle class="w-4 h-4 text-green-500 shrink-0" />
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
                    </div>
                @endif

                {{-- Tags --}}
                @if(!empty($pluginDetails['tags']))
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Tags</h4>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($pluginDetails['tags'] as $tag)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Plugin Information --}}
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Information</h4>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Author</dt>
                            <dd class="text-gray-900 dark:text-white">
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
                                <dd class="text-gray-900 dark:text-white">{{ $pluginDetails['license'] }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Namespace</dt>
                            <dd class="text-gray-900 dark:text-white font-mono text-xs">{{ $pluginDetails['namespace'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Provider</dt>
                            <dd class="text-gray-900 dark:text-white font-mono text-xs truncate" title="{{ $pluginDetails['provider'] }}">{{ class_basename($pluginDetails['provider']) }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Compatibility --}}
                @if(!empty($pluginDetails['compatibility']))
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Compatibility</h4>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                            @if(!empty($pluginDetails['compatibility']['php']))
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">PHP</dt>
                                    <dd class="text-gray-900 dark:text-white font-mono text-xs">{{ $pluginDetails['compatibility']['php'] }}</dd>
                                </div>
                            @endif
                            @if(!empty($pluginDetails['compatibility']['tallcms']))
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">TallCMS</dt>
                                    <dd class="text-gray-900 dark:text-white font-mono text-xs">{{ $pluginDetails['compatibility']['tallcms'] }}</dd>
                                </div>
                            @endif
                            @if(!empty($pluginDetails['compatibility']['extensions']))
                                <div class="col-span-2">
                                    <dt class="text-gray-500 dark:text-gray-400">Extensions</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ implode(', ', $pluginDetails['compatibility']['extensions']) }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @endif

                {{-- Backups / Rollback --}}
                @if(!empty($pluginDetails['backups']))
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Available Backups</h4>
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
                    </div>
                @endif

                {{-- Footer --}}
                <div class="pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
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
