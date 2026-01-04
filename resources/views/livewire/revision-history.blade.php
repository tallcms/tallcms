<div class="space-y-4">
    @if($this->revisions->isEmpty())
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <x-heroicon-o-document-text class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p>No revisions yet.</p>
            <p class="text-sm">Revisions are created when you save changes.</p>
        </div>
    @else
        {{-- Selection info --}}
        @if($selectedRevision || $compareRevision)
            <div class="flex items-center justify-between bg-primary-50 dark:bg-primary-900/20 rounded-lg p-3">
                <div class="text-sm text-primary-700 dark:text-primary-300">
                    @if($selectedRevision && !$compareRevision)
                        <span>Select another revision to compare</span>
                    @elseif($selectedRevision && $compareRevision)
                        <span>Comparing revisions</span>
                    @endif
                </div>
                <button
                    wire:click="clearSelection"
                    class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400"
                >
                    Clear selection
                </button>
            </div>
        @endif

        {{-- Revision timeline --}}
        <div class="relative">
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

            <div class="space-y-4">
                @foreach($this->revisions as $revision)
                    @php
                        $isSelected = $selectedRevision === $revision->id || $compareRevision === $revision->id;
                    @endphp
                    <div
                        wire:click="selectRevision({{ $revision->id }})"
                        class="relative pl-10 cursor-pointer group"
                    >
                        {{-- Timeline dot --}}
                        <div class="absolute left-2.5 top-2 w-3 h-3 rounded-full border-2 transition-colors
                            {{ $isSelected
                                ? 'bg-primary-500 border-primary-500'
                                : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 group-hover:border-primary-400' }}">
                        </div>

                        {{-- Revision card --}}
                        <div class="rounded-lg border transition-all
                            {{ $isSelected
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 ring-1 ring-primary-500'
                                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            <div class="p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-900 dark:text-white">
                                                Revision #{{ $revision->revision_number }}
                                            </span>
                                            @if($loop->first)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    Latest
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $revision->getChangeSummary() }}
                                        </p>
                                    </div>
                                    <div class="text-right text-sm">
                                        <div class="text-gray-900 dark:text-white">
                                            {{ $revision->user?->name ?? 'System' }}
                                        </div>
                                        <div class="text-gray-500 dark:text-gray-400">
                                            {{ $revision->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>

                                {{-- Restore button --}}
                                @if(!$loop->first)
                                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                                        <button
                                            wire:click.stop="restoreRevision({{ $revision->id }})"
                                            wire:confirm="Are you sure you want to restore this revision? Current content will be replaced."
                                            class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300"
                                        >
                                            <x-heroicon-o-arrow-path class="w-4 h-4 inline mr-1" />
                                            Restore this revision
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Diff viewer --}}
        @if($diff)
            <div class="mt-6 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-medium text-gray-900 dark:text-white">Changes</h3>
                </div>
                <div class="p-4 space-y-4">
                    {{-- Title diff --}}
                    @if($diff['title'])
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Title</h4>
                            <div class="space-y-1">
                                <div class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 px-3 py-2 rounded text-sm">
                                    <span class="text-red-500 mr-2">-</span>{{ $diff['title']['old'] ?? '(empty)' }}
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 px-3 py-2 rounded text-sm">
                                    <span class="text-green-500 mr-2">+</span>{{ $diff['title']['new'] ?? '(empty)' }}
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Excerpt diff --}}
                    @if($diff['excerpt'])
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Excerpt</h4>
                            <div class="space-y-1">
                                <div class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 px-3 py-2 rounded text-sm">
                                    <span class="text-red-500 mr-2">-</span>{{ $diff['excerpt']['old'] ?? '(empty)' }}
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 px-3 py-2 rounded text-sm">
                                    <span class="text-green-500 mr-2">+</span>{{ $diff['excerpt']['new'] ?? '(empty)' }}
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Meta title diff --}}
                    @if($diff['meta_title'])
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Meta Title</h4>
                            <div class="space-y-1">
                                <div class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 px-3 py-2 rounded text-sm">
                                    <span class="text-red-500 mr-2">-</span>{{ $diff['meta_title']['old'] ?? '(empty)' }}
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 px-3 py-2 rounded text-sm">
                                    <span class="text-green-500 mr-2">+</span>{{ $diff['meta_title']['new'] ?? '(empty)' }}
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Meta description diff --}}
                    @if($diff['meta_description'])
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Meta Description</h4>
                            <div class="space-y-1">
                                <div class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 px-3 py-2 rounded text-sm">
                                    <span class="text-red-500 mr-2">-</span>{{ $diff['meta_description']['old'] ?? '(empty)' }}
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 px-3 py-2 rounded text-sm">
                                    <span class="text-green-500 mr-2">+</span>{{ $diff['meta_description']['new'] ?? '(empty)' }}
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Content diff --}}
                    @if($diff['content'] && !empty($diff['content']['has_changes']))
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Content</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs font-medium text-red-600 dark:text-red-400 mb-1">Before</p>
                                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 rounded text-sm max-h-80 overflow-y-auto prose prose-sm dark:prose-invert max-w-none">
                                        {!! $diff['content']['old_html'] ?: '<em class="text-gray-400">(empty)</em>' !!}
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-green-600 dark:text-green-400 mb-1">After</p>
                                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3 rounded text-sm max-h-80 overflow-y-auto prose prose-sm dark:prose-invert max-w-none">
                                        {!! $diff['content']['new_html'] ?: '<em class="text-gray-400">(empty)</em>' !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- No changes message --}}
                    @if(!$diff['title'] && !$diff['excerpt'] && !$diff['meta_title'] && !$diff['meta_description'] && empty($diff['content']['has_changes']))
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                            No differences found between these revisions.
                        </p>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
