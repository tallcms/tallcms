<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chart-bar-square class="h-5 w-5 text-primary-500" />
                    <span>Analytics Overview</span>
                    @if(!$isLicensed)
                        <span class="text-xs text-gray-400">(Pro)</span>
                    @endif
                </div>

                @if($isConfigured && $isLicensed)
                    <div class="flex items-center gap-2">
                        <select
                            wire:change="setPeriod($event.target.value)"
                            class="text-sm border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                            @foreach($periods as $value => $label)
                                <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <button
                            wire:click="refreshData"
                            wire:loading.attr="disabled"
                            class="p-2 text-gray-500 hover:text-primary-500 transition-colors"
                            title="Refresh data"
                        >
                            <x-heroicon-o-arrow-path class="h-4 w-4" wire:loading.class="animate-spin" />
                        </button>
                    </div>
                @endif
            </div>
        </x-slot>

        @if(!$isLicensed)
            {{-- Unlicensed state --}}
            <div class="text-center py-8">
                <x-heroicon-o-lock-closed class="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                <p class="text-gray-500 dark:text-gray-400 mb-4">
                    Analytics integration requires a valid TallCMS Pro license.
                </p>
                <x-filament::button
                    tag="a"
                    href="{{ route('filament.admin.pages.pro-license') }}"
                    size="sm"
                >
                    Activate License
                </x-filament::button>
            </div>

        @elseif(!$isConfigured)
            {{-- Not configured state --}}
            <div class="text-center py-8">
                <x-heroicon-o-cog-6-tooth class="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                <p class="text-gray-500 dark:text-gray-400 mb-4">
                    Connect your Google Analytics account to view site statistics.
                </p>
                <x-filament::button
                    tag="a"
                    href="{{ route('filament.admin.pages.pro-settings') }}"
                    size="sm"
                >
                    Configure Analytics
                </x-filament::button>
            </div>

        @else
            {{-- Stats grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                {{-- Visitors --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Visitors</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($metrics['visitors'] ?? 0) }}
                    </div>
                    @if(isset($metrics['visitors_change']))
                        <div class="text-xs mt-1 {{ $metrics['visitors_change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $metrics['visitors_change'] >= 0 ? '+' : '' }}{{ $metrics['visitors_change'] }}%
                        </div>
                    @endif
                </div>

                {{-- Pageviews --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Pageviews</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($metrics['pageviews'] ?? 0) }}
                    </div>
                    @if(isset($metrics['pageviews_change']))
                        <div class="text-xs mt-1 {{ $metrics['pageviews_change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $metrics['pageviews_change'] >= 0 ? '+' : '' }}{{ $metrics['pageviews_change'] }}%
                        </div>
                    @endif
                </div>

                {{-- Bounce Rate --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Bounce Rate</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $metrics['bounce_rate'] ?? 0 }}%
                    </div>
                </div>

                {{-- Avg. Session Duration --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Avg. Duration</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        @php
                            $seconds = $metrics['avg_session_duration'] ?? 0;
                            $minutes = floor($seconds / 60);
                            $secs = $seconds % 60;
                        @endphp
                        {{ $minutes }}:{{ str_pad($secs, 2, '0', STR_PAD_LEFT) }}
                    </div>
                </div>
            </div>

            {{-- Two column layout for tables --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Top Pages --}}
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Top Pages</h4>
                    @if(count($topPages) > 0)
                        <div class="space-y-2">
                            @foreach($topPages as $page)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400 truncate max-w-[200px]" title="{{ $page['path'] }}">
                                        {{ $page['title'] ?: $page['path'] }}
                                    </span>
                                    <span class="text-gray-900 dark:text-white font-medium">
                                        {{ number_format($page['views']) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400">No data available</p>
                    @endif
                </div>

                {{-- Traffic Sources --}}
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Traffic Sources</h4>
                    @if(count($trafficSources) > 0)
                        <div class="space-y-2">
                            @foreach($trafficSources as $source)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400 capitalize">
                                        {{ $source['source'] === '(direct)' ? 'Direct' : $source['source'] }}
                                    </span>
                                    <span class="text-gray-900 dark:text-white font-medium">
                                        {{ number_format($source['sessions']) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400">No data available</p>
                    @endif
                </div>
            </div>

            {{-- Visitor Trend Chart (simple bar representation) --}}
            @if(count($visitorTrend) > 0)
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Visitor Trend</h4>
                    <div class="flex items-end gap-1 h-24">
                        @php
                            $maxVisitors = max(array_column($visitorTrend, 'visitors')) ?: 1;
                        @endphp
                        @foreach($visitorTrend as $day)
                            @php
                                $height = ($day['visitors'] / $maxVisitors) * 100;
                            @endphp
                            <div
                                class="flex-1 bg-primary-500 rounded-t hover:bg-primary-600 transition-colors cursor-pointer group relative"
                                style="height: {{ max($height, 4) }}%"
                                title="{{ $day['date'] }}: {{ number_format($day['visitors']) }} visitors"
                            >
                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10">
                                    {{ $day['date'] }}<br>{{ number_format($day['visitors']) }} visitors
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
