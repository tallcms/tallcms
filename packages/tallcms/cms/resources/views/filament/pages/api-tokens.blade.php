<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Documentation Link --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-book-open class="w-5 h-5 text-primary-500" />
                    API Documentation
                </div>
            </x-slot>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                Use the TallCMS REST API to programmatically manage your content. The API provides full CRUD operations for Pages, Posts, Categories, and Media.
            </p>

            <div class="mt-4 flex items-center gap-4">
                <div class="text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Base URL:</span>
                    <code class="ml-2 px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-primary-600 dark:text-primary-400">
                        {{ url(config('tallcms.api.prefix', 'api/v1/tallcms')) }}
                    </code>
                </div>
            </div>
        </x-filament::section>

        {{-- Tokens List --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-key class="w-5 h-5 text-primary-500" />
                    Your API Tokens
                </div>
            </x-slot>

            <x-slot name="description">
                API tokens allow external applications to access the TallCMS API on your behalf. Keep your tokens secure!
            </x-slot>

            @if($this->tokens->isEmpty())
                <div class="text-center py-8">
                    <x-heroicon-o-key class="w-12 h-12 mx-auto text-gray-400" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                        No API Tokens
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Create your first API token to start using the API.
                    </p>
                    <div class="mt-4">
                        {{ ($this->createTokenAction)(['color' => 'primary']) }}
                    </div>
                </div>
            @else
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->tokens as $token)
                        <div class="py-4 @if($loop->first) pt-0 @endif @if($loop->last) pb-0 @endif">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                            {{ $token['name'] }}
                                        </h4>
                                        @if($token['is_expired'])
                                            <x-filament::badge color="danger" size="sm">
                                                Expired
                                            </x-filament::badge>
                                        @endif
                                    </div>

                                    <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                        <span>Created: {{ $token['created_at'] }}</span>
                                        @if($token['expires_at'])
                                            <span>Expires: {{ $token['expires_at'] }}</span>
                                        @endif
                                        @if($token['last_used_at'])
                                            <span>Last used: {{ $token['last_used_at'] }}</span>
                                        @else
                                            <span class="text-gray-400">Never used</span>
                                        @endif
                                    </div>

                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach($token['abilities'] as $ability)
                                            <x-filament::badge color="gray" size="sm">
                                                {{ $ability }}
                                            </x-filament::badge>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="ml-4 flex-shrink-0">
                                    {{ ($this->revokeTokenAction)(['id' => $token['id']]) }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- Token Created Modal --}}
    <x-filament::modal id="token-created-modal" width="lg" :close-by-clicking-away="false">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-check-circle class="w-6 h-6 text-success-500" />
                Token Created Successfully
            </div>
        </x-slot>

        <div class="space-y-4">
            <div class="p-4 bg-warning-50 dark:bg-warning-900/20 rounded-lg border border-warning-200 dark:border-warning-700">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-500 flex-shrink-0 mt-0.5" />
                    <div class="text-sm text-warning-800 dark:text-warning-200">
                        <strong>Important:</strong> Copy this token now. You won't be able to see it again!
                    </div>
                </div>
            </div>

            @if($newToken)
                <div class="relative">
                    <input
                        type="text"
                        value="{{ $newToken }}"
                        readonly
                        class="w-full px-3 py-2 pr-20 font-mono text-sm bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg"
                        onclick="this.select()"
                    />
                    <button
                        type="button"
                        onclick="navigator.clipboard.writeText('{{ $newToken }}'); this.innerHTML = '<svg class=\'w-4 h-4\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'></path></svg>'"
                        class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1 text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400"
                    >
                        <x-heroicon-o-clipboard class="w-4 h-4" />
                    </button>
                </div>
            @endif

            <div class="text-sm text-gray-500 dark:text-gray-400">
                <strong>Usage:</strong> Include this token in the Authorization header of your API requests:
                <code class="block mt-2 px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded text-xs">
                    Authorization: Bearer {{ $newToken ?? 'your-token-here' }}
                </code>
            </div>
        </div>

        <x-slot name="footerActions">
            <x-filament::button wire:click="closeTokenModal" x-on:click="$dispatch('close-modal', { id: 'token-created-modal' })" color="primary">
                Done
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
