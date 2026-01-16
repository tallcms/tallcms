<x-filament-panels::page>
    {{-- Current Version Info --}}
    <x-filament::section>
        <x-slot name="heading">TallCMS Core</x-slot>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Current version</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">v{{ $currentVersion }}</p>
                </div>

                @if($updateAvailable && $latestRelease)
                    <div class="text-right">
                        <x-filament::badge color="success" size="lg">
                            Update available: v{{ $latestRelease['version'] }}
                        </x-filament::badge>
                        @if($latestRelease['published_at'])
                            <p class="text-xs text-gray-500 mt-1">
                                Released: {{ \Carbon\Carbon::parse($latestRelease['published_at'])->format('M j, Y') }}
                            </p>
                        @endif
                    </div>
                @else
                    <x-filament::badge color="gray" size="lg">
                        Up to date
                    </x-filament::badge>
                @endif
            </div>

            @if($updateAvailable && $latestRelease && $latestRelease['body'])
                <div class="pt-4 border-t border-gray-200 dark:border-white/10">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">What's new:</h4>
                    <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-400">
                        {!! \Illuminate\Support\Str::markdown($latestRelease['body']) !!}
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>

    @if($updateAvailable)
        {{-- Preflight Checks --}}
        <x-filament::section>
            <x-slot name="heading">Pre-Update Checks</x-slot>

            <div class="space-y-3">
                @foreach($preflightChecks as $check => $result)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-white/5 last:border-0">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ match($check) {
                                'sodium' => 'Signature Verification',
                                'public_key' => 'Public Key Configuration',
                                'disk_space' => 'Disk Space',
                                'lock' => 'Update Lock',
                                'exec' => 'Background Execution',
                                'queue' => 'Queue System',
                                default => ucfirst(str_replace('_', ' ', $check))
                            } }}
                        </span>
                        <div class="flex items-center gap-2">
                            @if($result['status'] === 'pass')
                                <x-heroicon-s-check-circle class="w-5 h-5 text-green-500" />
                            @elseif($result['status'] === 'warn')
                                <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-500" />
                            @else
                                <x-heroicon-s-x-circle class="w-5 h-5 text-red-500" />
                            @endif
                            <span class="text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate" title="{{ $result['message'] }}">
                                {{ $result['message'] }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Clear stale lock button --}}
            @if(($preflightChecks['lock']['status'] ?? '') === 'fail')
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-white/10">
                    <x-filament::button
                        wire:click="clearStaleLock"
                        color="warning"
                        size="sm"
                    >
                        Clear Stale Lock
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>

        {{-- Database Backup Info --}}
        <x-filament::section>
            <x-slot name="heading">Database Backup</x-slot>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Driver</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($dbBackupCapability['driver'] ?? 'unknown') }}</span>
                </div>

                @if($dbBackupCapability['size_mb'] !== null)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Database Size</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $dbBackupCapability['size_mb'] }} MB</span>
                    </div>
                @endif

                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Backup Method</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $dbBackupCapability['method'] ?? 'Not available' }}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Auto-backup</span>
                    @if($dbBackupCapability['capable'])
                        <x-filament::badge color="success" size="sm">Available</x-filament::badge>
                    @else
                        <x-filament::badge color="warning" size="sm">Not Available</x-filament::badge>
                    @endif
                </div>

                @if($dbBackupCapability['warning'])
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                        <p class="text-sm text-amber-700 dark:text-amber-300">
                            {{ $dbBackupCapability['warning'] }}
                        </p>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Update Action --}}
        <div class="flex justify-end gap-4">
            @php
                $canUpdate = collect($preflightChecks)->every(fn($c) => $c['status'] !== 'fail');
            @endphp

            <x-filament::button
                wire:click="startUpdate"
                size="lg"
                :disabled="!$canUpdate"
            >
                <x-heroicon-s-arrow-down-tray class="w-5 h-5 mr-2" />
                Update to v{{ $latestRelease['version'] }}
            </x-filament::button>
        </div>
    @else
        {{-- Up to date message --}}
        <div class="flex flex-col items-center justify-center p-12 text-center">
            <div class="rounded-full bg-green-100 dark:bg-green-900/20 p-3 mb-4">
                <x-heroicon-o-check-circle class="w-8 h-8 text-green-500" />
            </div>
            <h3 class="text-lg font-semibold text-gray-950 dark:text-white">You're up to date!</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                TallCMS v{{ $currentVersion }} is the latest version.
            </p>
        </div>
    @endif
</x-filament-panels::page>
