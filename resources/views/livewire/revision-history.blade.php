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
            @php
                $selectedLabel = $selectedRevision === 'current'
                    ? 'Current Version'
                    : 'Revision #' . ($this->revisions->firstWhere('id', $selectedRevision)?->revision_number ?? '?');
            @endphp
            <div class="flex items-center justify-between bg-primary-50 dark:bg-primary-900/20 rounded-lg p-3">
                <div class="text-sm text-primary-700 dark:text-primary-300">
                    @if($selectedRevision && !$compareRevision)
                        <span class="font-medium">{{ $selectedLabel }}</span>
                        <span class="text-primary-500"> — select another to compare</span>
                    @elseif($selectedRevision && $compareRevision)
                        <span>Comparing versions</span>
                    @endif
                </div>
                <button
                    wire:click="clearSelection"
                    class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400 font-medium"
                >
                    Clear
                </button>
            </div>
        @endif

        {{-- Revision timeline --}}
        <div class="relative">
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

            <div class="space-y-3">
                {{-- Current version --}}
                @php
                    $isCurrentSelected = $selectedRevision === 'current' || $compareRevision === 'current';
                @endphp
                <div
                    wire:click="selectRevision('current')"
                    class="relative pl-10 cursor-pointer group"
                >
                    <div class="absolute left-2.5 top-2 w-3 h-3 rounded-full border-2 transition-colors
                        {{ $isCurrentSelected
                            ? 'bg-primary-500 border-primary-500'
                            : 'bg-green-500 border-green-500 group-hover:bg-green-400' }}">
                    </div>
                    <div class="rounded-lg border transition-all
                        {{ $isCurrentSelected
                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 ring-1 ring-primary-500'
                            : 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20 hover:border-green-400' }}">
                        <div class="p-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-900 dark:text-white">Current Version</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                        Live
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Revisions --}}
                @foreach($this->revisions as $revision)
                    @php
                        $isSelected = $selectedRevision === $revision->id || $compareRevision === $revision->id;
                        $hasPrevious = !$loop->last;
                    @endphp
                    <div class="relative pl-10 group">
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
                            <div class="p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div
                                        wire:click="selectRevision({{ $revision->id }})"
                                        class="flex-1 cursor-pointer"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-900 dark:text-white">
                                                Revision #{{ $revision->revision_number }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ $revision->created_at->diffForHumans() }}
                                            @if($revision->user)
                                                by {{ $revision->user->name }}
                                            @endif
                                        </p>
                                    </div>

                                    {{-- Quick actions --}}
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button
                                            wire:click.stop="compareToCurrent({{ $revision->id }})"
                                            class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300"
                                            title="Compare to current"
                                        >
                                            vs Current
                                        </button>
                                        @if($hasPrevious)
                                            <button
                                                wire:click.stop="compareToPrevious({{ $revision->id }})"
                                                class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300"
                                                title="Compare to previous revision"
                                            >
                                                vs Prev
                                            </button>
                                        @endif
                                        @can($this->record instanceof \App\Models\CmsPost ? 'RestoreRevision:CmsPost' : 'RestoreRevision:CmsPage')
                                            <button
                                                wire:click.stop="restoreRevision({{ $revision->id }})"
                                                wire:confirm="Are you sure you want to restore Revision #{{ $revision->revision_number }}? This will overwrite the current content."
                                                class="px-2 py-1 text-xs rounded bg-amber-100 hover:bg-amber-200 dark:bg-amber-900/50 dark:hover:bg-amber-900 text-amber-800 dark:text-amber-200"
                                                title="Restore this revision"
                                            >
                                                Restore
                                            </button>
                                        @endcan
                                    </div>
                                </div>
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
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium text-gray-900 dark:text-white">
                            Comparing: <span class="text-red-600 dark:text-red-400">{{ $diff['older_label'] }}</span>
                            → <span class="text-green-600 dark:text-green-400">{{ $diff['newer_label'] }}</span>
                        </h3>
                        @if($diff['restorable_id'])
                            <button
                                wire:click="restoreRevision({{ $diff['restorable_id'] }})"
                                wire:confirm="Are you sure you want to restore this revision? This will overwrite the current content."
                                class="text-sm px-3 py-1 rounded bg-amber-100 hover:bg-amber-200 text-amber-800 dark:bg-amber-900/50 dark:hover:bg-amber-900 dark:text-amber-200"
                            >
                                Restore {{ $diff['restorable_label'] }}
                            </button>
                        @endif
                    </div>
                </div>

                <div class="p-4 space-y-4">
                    {{-- Field diffs --}}
                    @foreach(['title' => 'Title', 'excerpt' => 'Excerpt', 'meta_title' => 'Meta Title', 'meta_description' => 'Meta Description'] as $field => $label)
                        @if($diff[$field])
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $label }}</h4>
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-2">
                                        <span class="text-red-600 dark:text-red-400 text-xs font-medium">Before:</span>
                                        <p class="text-gray-700 dark:text-gray-300 mt-1">{{ $diff[$field]['old'] ?: '(empty)' }}</p>
                                    </div>
                                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded p-2">
                                        <span class="text-green-600 dark:text-green-400 text-xs font-medium">After:</span>
                                        <p class="text-gray-700 dark:text-gray-300 mt-1">{{ $diff[$field]['new'] ?: '(empty)' }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach

                    {{-- Content diff --}}
                    @if($diff['content'] && !empty($diff['content']['has_changes']))
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Content</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-xs font-medium text-red-600 dark:text-red-400 mb-1">Before</p>
                                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 rounded text-sm max-h-80 overflow-y-auto prose prose-sm dark:prose-invert max-w-none prose-headings:text-gray-800 dark:prose-headings:text-gray-200">
                                        {!! $diff['content']['old_html'] ?: '<em class="text-gray-400">(empty)</em>' !!}
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-green-600 dark:text-green-400 mb-1">After</p>
                                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3 rounded text-sm max-h-80 overflow-y-auto prose prose-sm dark:prose-invert max-w-none prose-headings:text-gray-800 dark:prose-headings:text-gray-200">
                                        {!! $diff['content']['new_html'] ?: '<em class="text-gray-400">(empty)</em>' !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- No changes message --}}
                    @php
                        $hasAnyChanges = $diff['title'] || $diff['excerpt'] || $diff['meta_title'] || $diff['meta_description'] || !empty($diff['content']['has_changes']);
                    @endphp
                    @if(!$hasAnyChanges)
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                            No differences found between these versions.
                        </p>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
