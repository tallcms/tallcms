<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Info Section --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-primary-500" />
                    About Webhooks
                </div>
            </x-slot>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                Webhooks notify external services when content changes in TallCMS. Each webhook receives a signed JSON payload containing event details.
            </p>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-lock-closed class="w-4 h-4 text-gray-400" />
                    <span class="text-gray-600 dark:text-gray-400">HTTPS only</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-shield-check class="w-4 h-4 text-gray-400" />
                    <span class="text-gray-600 dark:text-gray-400">SHA-256 signed</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-arrow-path class="w-4 h-4 text-gray-400" />
                    <span class="text-gray-600 dark:text-gray-400">Auto-retry on failure</span>
                </div>
            </div>
        </x-filament::section>

        {{-- Webhooks List --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-arrow-path-rounded-square class="w-5 h-5 text-primary-500" />
                    Configured Webhooks
                </div>
            </x-slot>

            @if($this->webhooks->isEmpty())
                <div class="text-center py-8">
                    <x-heroicon-o-arrow-path-rounded-square class="w-12 h-12 mx-auto text-gray-400" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                        No Webhooks Configured
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Add a webhook to start receiving notifications when content changes.
                    </p>
                    <div class="mt-4">
                        {{ ($this->createWebhookAction)(['color' => 'primary']) }}
                    </div>
                </div>
            @else
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->webhooks as $webhook)
                        <div class="py-4 @if($loop->first) pt-0 @endif @if($loop->last) pb-0 @endif">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                            {{ $webhook['name'] }}
                                        </h4>
                                        @if($webhook['is_active'])
                                            <x-filament::badge color="success" size="sm">
                                                Active
                                            </x-filament::badge>
                                        @else
                                            <x-filament::badge color="gray" size="sm">
                                                Inactive
                                            </x-filament::badge>
                                        @endif
                                    </div>

                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ $webhook['url'] }}
                                    </div>

                                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                                        <span class="text-gray-500 dark:text-gray-400">
                                            {{ count($webhook['events']) }} events
                                        </span>
                                        @if($webhook['recent_success_count'] > 0)
                                            <span class="text-success-600 dark:text-success-400">
                                                {{ $webhook['recent_success_count'] }} successful (7d)
                                            </span>
                                        @endif
                                        @if($webhook['recent_failure_count'] > 0)
                                            <span class="text-danger-600 dark:text-danger-400">
                                                {{ $webhook['recent_failure_count'] }} failed (7d)
                                            </span>
                                        @endif
                                        <span class="text-gray-400">
                                            by {{ $webhook['creator_name'] }}
                                        </span>
                                    </div>
                                </div>

                                <div class="ml-4 flex-shrink-0 flex items-center gap-2">
                                    <x-filament::button
                                        wire:click="showWebhookDetails({{ $webhook['id'] }})"
                                        color="gray"
                                        size="sm"
                                        icon="heroicon-o-eye"
                                    >
                                        Details
                                    </x-filament::button>
                                    {{ ($this->testWebhookAction)(['id' => $webhook['id']]) }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- Webhook Details Modal --}}
    <x-filament::modal id="webhook-details-modal" width="2xl">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-arrow-path-rounded-square class="w-6 h-6 text-primary-500" />
                Webhook Details
            </div>
        </x-slot>

        @if($webhookDetails)
            <div class="space-y-6">
                {{-- Webhook Info --}}
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Name</dt>
                        <dd class="font-medium">{{ $webhookDetails['name'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                        <dd>
                            @if($webhookDetails['is_active'])
                                <x-filament::badge color="success">Active</x-filament::badge>
                            @else
                                <x-filament::badge color="gray">Inactive</x-filament::badge>
                            @endif
                        </dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">URL</dt>
                        <dd class="font-mono text-xs break-all">{{ $webhookDetails['url'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Timeout</dt>
                        <dd>{{ $webhookDetails['timeout'] }} seconds</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Created</dt>
                        <dd>{{ $webhookDetails['created_at'] }}</dd>
                    </div>
                </div>

                {{-- Events --}}
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subscribed Events</h4>
                    <div class="flex flex-wrap gap-1">
                        @foreach($webhookDetails['events'] as $event)
                            <x-filament::badge color="gray" size="sm">
                                {{ $event }}
                            </x-filament::badge>
                        @endforeach
                    </div>
                </div>

                {{-- Recent Deliveries --}}
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recent Deliveries</h4>
                    @if(empty($webhookDetails['deliveries']))
                        <p class="text-sm text-gray-500 dark:text-gray-400">No deliveries yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                        <th class="pb-2 font-medium">Event</th>
                                        <th class="pb-2 font-medium">Status</th>
                                        <th class="pb-2 font-medium">Attempt</th>
                                        <th class="pb-2 font-medium">Duration</th>
                                        <th class="pb-2 font-medium">Time</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($webhookDetails['deliveries'] as $delivery)
                                        <tr>
                                            <td class="py-2">{{ $delivery['event'] }}</td>
                                            <td class="py-2">
                                                @if($delivery['success'])
                                                    <span class="text-success-600 dark:text-success-400">
                                                        {{ $delivery['status_code'] }}
                                                    </span>
                                                @else
                                                    <span class="text-danger-600 dark:text-danger-400">
                                                        {{ $delivery['status_code'] ?? 'Error' }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-2">{{ $delivery['attempt'] }}</td>
                                            <td class="py-2">{{ $delivery['duration_ms'] }}ms</td>
                                            <td class="py-2 text-gray-500">{{ $delivery['created_at'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <x-slot name="footerActions">
            @if($webhookDetails)
                {{ ($this->editWebhookAction)(['id' => $webhookDetails['id']]) }}
                {{ ($this->deleteWebhookAction)(['id' => $webhookDetails['id']]) }}
            @endif
            <x-filament::button wire:click="closeWebhookDetails" x-on:click="$dispatch('close-modal', { id: 'webhook-details-modal' })" color="gray">
                Close
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
