<x-filament-panels::page>
    <div wire:poll.2s>
        @php
            $state = $this->updateState;
            $status = $state['status'] ?? 'unknown';
        @endphp

        <x-filament::section>
            <x-slot name="heading">
                @if($status === 'in_progress')
                    Updating to v{{ $state['version'] ?? 'unknown' }}...
                @elseif($status === 'completed')
                    Update Complete
                @elseif($status === 'failed')
                    Update Failed
                @else
                    Update Status
                @endif
            </x-slot>

            {{-- Status Indicator --}}
            <div class="flex flex-col items-center justify-center py-8">
                @if($status === 'in_progress')
                    <div class="relative">
                        <div class="animate-spin rounded-full h-16 w-16 border-4 border-primary-200 border-t-primary-600"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <x-heroicon-s-arrow-path class="w-6 h-6 text-primary-600 animate-pulse" />
                        </div>
                    </div>
                    <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                        {{ match($state['current_step'] ?? '') {
                            'preflight' => 'Running preflight checks...',
                            'checking' => 'Checking for updates...',
                            'downloading' => 'Downloading release...',
                            'verifying' => 'Verifying signature...',
                            'backup_files' => 'Backing up files...',
                            'backup_database' => 'Backing up database...',
                            'extracting' => 'Extracting release...',
                            'validating' => 'Validating release...',
                            'analyzing' => 'Analyzing changes...',
                            'applying' => 'Applying update...',
                            'migrating' => 'Running migrations...',
                            'clearing_cache' => 'Clearing caches...',
                            default => 'Processing...'
                        } }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        This may take a few minutes. Please don't close this page.
                    </p>
                @elseif($status === 'completed')
                    <div class="rounded-full bg-green-100 dark:bg-green-900/20 p-4">
                        <x-heroicon-s-check-circle class="w-12 h-12 text-green-500" />
                    </div>
                    <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                        Successfully updated to v{{ $state['version'] ?? '' }}!
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Your TallCMS installation has been updated.
                    </p>
                    <x-filament::button
                        wire:click="backToUpdates"
                        class="mt-6"
                    >
                        Back to System Updates
                    </x-filament::button>
                @elseif($status === 'failed')
                    <div class="rounded-full bg-red-100 dark:bg-red-900/20 p-4">
                        <x-heroicon-s-x-circle class="w-12 h-12 text-red-500" />
                    </div>
                    <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                        Update Failed
                    </p>
                    @if(!empty($state['error']))
                        <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg max-w-lg">
                            <p class="text-sm text-red-700 dark:text-red-300">
                                {{ $state['error'] }}
                            </p>
                        </div>
                    @endif
                    <div class="mt-6 flex gap-4">
                        <x-filament::button
                            wire:click="clearAndRetry"
                            color="warning"
                        >
                            Clear Lock & Retry
                        </x-filament::button>
                        <x-filament::button
                            wire:click="backToUpdates"
                            color="gray"
                        >
                            Back to System Updates
                        </x-filament::button>
                    </div>
                @else
                    <div class="rounded-full bg-gray-100 dark:bg-white/5 p-4">
                        <x-heroicon-s-question-mark-circle class="w-12 h-12 text-gray-400" />
                    </div>
                    <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                        Unknown Status
                    </p>
                    <x-filament::button
                        wire:click="backToUpdates"
                        class="mt-6"
                    >
                        Back to System Updates
                    </x-filament::button>
                @endif
            </div>

            {{-- Progress Details --}}
            @if($status === 'in_progress' && !empty($state['steps']))
                <div class="mt-8 pt-8 border-t border-gray-200 dark:border-white/10">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Progress</h4>
                    <div class="space-y-2">
                        @foreach($state['steps'] as $step)
                            <div class="flex items-center gap-3">
                                @if($step['status'] === 'completed')
                                    <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 shrink-0" />
                                @elseif($step['status'] === 'in_progress')
                                    <div class="w-5 h-5 shrink-0">
                                        <div class="animate-spin rounded-full h-5 w-5 border-2 border-primary-200 border-t-primary-600"></div>
                                    </div>
                                @else
                                    <div class="w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600 shrink-0"></div>
                                @endif
                                <span class="text-sm {{ $step['status'] === 'completed' ? 'text-gray-500' : ($step['status'] === 'in_progress' ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-400') }}">
                                    {{ ucwords(str_replace('_', ' ', $step['name'])) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Meta Info --}}
            @if(!empty($state['started_at']))
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-white/10">
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>Started: {{ \Carbon\Carbon::parse($state['started_at'])->format('M j, Y g:i A') }}</span>
                        @if(!empty($state['execution_method']))
                            <span>Method: {{ ucfirst($state['execution_method']) }}</span>
                        @endif
                    </div>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
