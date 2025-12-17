<x-filament-panels::page>
    {{-- Theme Gallery Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($this->themes as $theme)
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border overflow-hidden transition-all duration-200 hover:shadow-md
                       {{ $theme['isActive'] ? 'border-primary-500 ring-2 ring-primary-500/20' : 'border-gray-200 dark:border-gray-700' }}"
            >
                {{-- Screenshot --}}
                <div class="aspect-video bg-gray-100 dark:bg-gray-900 relative overflow-hidden">
                    @if($theme['screenshot'])
                        <img
                            src="{{ $theme['screenshot'] }}"
                            alt="{{ $theme['name'] }} screenshot"
                            class="w-full h-full object-cover"
                            loading="lazy"
                        >
                    @else
                        <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 dark:text-gray-600">
                            <x-heroicon-o-photo class="w-16 h-16 mb-2" />
                            <span class="text-sm">No preview</span>
                        </div>
                    @endif

                    {{-- Active Badge --}}
                    @if($theme['isActive'])
                        <div class="absolute top-3 right-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary-500 text-white shadow-sm">
                                <x-heroicon-s-check-circle class="w-4 h-4 mr-1" />
                                Active
                            </span>
                        </div>
                    @endif

                    {{-- Status Badges --}}
                    @if(!$theme['meetsRequirements'])
                        <div class="absolute top-3 left-3">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-danger-500 text-white shadow-sm">
                                <x-heroicon-s-exclamation-triangle class="w-3 h-3 mr-1" />
                                Requirements not met
                            </span>
                        </div>
                    @elseif(!$theme['isBuilt'] && $theme['isPrebuilt'])
                        <div class="absolute top-3 left-3">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-warning-500 text-white shadow-sm">
                                <x-heroicon-s-wrench class="w-3 h-3 mr-1" />
                                Not built
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Theme Info --}}
                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                                {{ $theme['name'] }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                v{{ $theme['version'] }} by {{ $theme['author'] }}
                            </p>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 line-clamp-2">
                        {{ $theme['description'] ?: 'No description available.' }}
                    </p>

                    {{-- Feature Badges --}}
                    <div class="flex flex-wrap gap-2 mb-4">
                        @if($theme['supports']['dark_mode'] ?? false)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                <x-heroicon-m-moon class="w-3 h-3 mr-1" />
                                Dark Mode
                            </span>
                        @endif
                        @if($theme['supports']['responsive'] ?? false)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                <x-heroicon-m-device-phone-mobile class="w-3 h-3 mr-1" />
                                Responsive
                            </span>
                        @endif
                        @if($theme['parent'])
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                <x-heroicon-m-link class="w-3 h-3 mr-1" />
                                Child of {{ $theme['parent'] }}
                            </span>
                        @endif
                    </div>

                    {{-- Unmet Requirements Warning --}}
                    @if(!empty($theme['unmetRequirements']))
                        <div class="mb-4 p-2 bg-danger-50 dark:bg-danger-900/20 rounded-lg">
                            <p class="text-xs text-danger-700 dark:text-danger-300 font-medium mb-1">Requirements not met:</p>
                            <ul class="text-xs text-danger-600 dark:text-danger-400 list-disc list-inside">
                                @foreach($theme['unmetRequirements'] as $requirement)
                                    <li>{{ $requirement }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="flex items-center gap-2">
                        @unless($theme['isActive'])
                            <x-filament::button
                                wire:click="activateTheme('{{ $theme['slug'] }}')"
                                size="sm"
                                :disabled="!$theme['meetsRequirements']"
                            >
                                Activate
                            </x-filament::button>

                            <x-filament::button
                                wire:click="previewTheme('{{ $theme['slug'] }}')"
                                color="gray"
                                size="sm"
                            >
                                <x-heroicon-m-eye class="w-4 h-4 mr-1" />
                                Preview
                            </x-filament::button>
                        @else
                            <span class="text-sm text-primary-600 dark:text-primary-400 font-medium">
                                Currently Active
                            </span>
                        @endunless

                        <x-filament::button
                            wire:click="showThemeDetails('{{ $theme['slug'] }}')"
                            color="gray"
                            size="sm"
                            outlined
                            class="ml-auto"
                        >
                            Details
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Empty State --}}
    @if($this->themes->isEmpty())
        <div class="text-center py-12">
            <x-heroicon-o-paint-brush class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No themes found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Add themes to the <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">themes/</code> directory.
            </p>
        </div>
    @endif

    {{-- Theme Details Modal --}}
    <x-filament::modal id="theme-details-modal" width="2xl" slide-over>
        <x-slot name="heading">
            {{ $themeDetails['name'] ?? 'Theme Details' }}
        </x-slot>

        @if($themeDetails)
            <div class="space-y-6">
                {{-- Screenshot --}}
                @if($themeDetails['screenshot'])
                    <div class="aspect-video bg-gray-100 dark:bg-gray-900 rounded-lg overflow-hidden">
                        <img
                            src="{{ $themeDetails['screenshot'] }}"
                            alt="{{ $themeDetails['name'] }}"
                            class="w-full h-full object-cover"
                        >
                    </div>
                @endif

                {{-- Basic Info --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Version</span>
                        <p class="text-gray-900 dark:text-white">{{ $themeDetails['version'] }}</p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Author</span>
                        <p class="text-gray-900 dark:text-white">
                            @if($themeDetails['authorUrl'])
                                <a href="{{ $themeDetails['authorUrl'] }}" target="_blank" class="text-primary-600 hover:underline">
                                    {{ $themeDetails['author'] }}
                                </a>
                            @else
                                {{ $themeDetails['author'] }}
                            @endif
                        </p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Slug</span>
                        <p class="text-gray-900 dark:text-white font-mono text-sm">{{ $themeDetails['slug'] }}</p>
                    </div>
                    @if($themeDetails['license'])
                        <div>
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">License</span>
                            <p class="text-gray-900 dark:text-white">{{ $themeDetails['license'] }}</p>
                        </div>
                    @endif
                </div>

                {{-- Description --}}
                <div>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</span>
                    <p class="text-gray-900 dark:text-white mt-1">{{ $themeDetails['description'] ?: 'No description available.' }}</p>
                </div>

                {{-- Parent Theme --}}
                @if($themeDetails['parent'])
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Parent Theme</span>
                        <p class="text-gray-900 dark:text-white">{{ $themeDetails['parent'] }}</p>
                    </div>
                @endif

                {{-- Color Preview --}}
                @if(!empty($themeDetails['tailwind']['colors']['primary']))
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Primary Colors</span>
                        <div class="flex gap-1 mt-2">
                            @foreach($themeDetails['tailwind']['colors']['primary'] as $shade => $color)
                                <div
                                    class="w-8 h-8 rounded shadow-sm border border-gray-200 dark:border-gray-700"
                                    style="background-color: {{ $color }}"
                                    title="{{ $shade }}: {{ $color }}"
                                ></div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Supported Features --}}
                @if(!empty($themeDetails['supports']))
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Features</span>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($themeDetails['supports'] as $feature => $enabled)
                                @if($enabled === true)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                                        <x-heroicon-m-check class="w-3 h-3 mr-1" />
                                        {{ str_replace('_', ' ', ucfirst($feature)) }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Compatibility --}}
                @if(!empty($themeDetails['compatibility']))
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Compatibility</span>
                        <div class="mt-2 text-sm">
                            @if(!empty($themeDetails['compatibility']['php']))
                                <p class="text-gray-600 dark:text-gray-400">PHP: {{ $themeDetails['compatibility']['php'] }}</p>
                            @endif
                            @if(!empty($themeDetails['compatibility']['tallcms']))
                                <p class="text-gray-600 dark:text-gray-400">TallCMS: {{ $themeDetails['compatibility']['tallcms'] }}</p>
                            @endif
                            <p class="text-gray-600 dark:text-gray-400">
                                Type: {{ $themeDetails['isPrebuilt'] ? 'Prebuilt (no build required)' : 'Source (requires build)' }}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Requirements Status --}}
                @if(!$themeDetails['meetsRequirements'])
                    <div class="p-3 bg-danger-50 dark:bg-danger-900/20 rounded-lg">
                        <p class="text-sm font-medium text-danger-700 dark:text-danger-300 mb-2">Unmet Requirements:</p>
                        <ul class="text-sm text-danger-600 dark:text-danger-400 list-disc list-inside space-y-1">
                            @foreach($themeDetails['unmetRequirements'] as $requirement)
                                <li>{{ $requirement }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Path (for debugging) --}}
                <div>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Installation Path</span>
                    <p class="text-gray-600 dark:text-gray-400 font-mono text-xs break-all mt-1">{{ $themeDetails['path'] }}</p>
                </div>

                {{-- Homepage Link --}}
                @if($themeDetails['homepage'])
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a
                            href="{{ $themeDetails['homepage'] }}"
                            target="_blank"
                            class="inline-flex items-center text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400"
                        >
                            <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4 mr-1" />
                            View Theme Homepage
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </x-filament::modal>

    {{-- JavaScript for preview --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-preview', ({ url }) => {
                window.open(url, '_blank');
            });
        });
    </script>
</x-filament-panels::page>
