{{--
    CMS Rich Editor - Enhanced Block Panel

    This view extends Filament's RichEditor with:
    - Search functionality for finding blocks quickly
    - Blocks grouped by category
    - Icons displayed alongside block names

    IMPORTANT - FILAMENT UPGRADE CHECKLIST:
    ========================================
    This view is copied from Filament v4.x rich-editor.blade.php with modifications
    to the custom blocks panel section only. When upgrading Filament:

    1. Compare vendor/filament/forms/resources/views/components/rich-editor.blade.php
       with this file using diff
    2. The ONLY modified section is inside: <div x-show="isPanelActive('customBlocks')">
       Look for "Enhanced Block Panel with Search and Categories" comment
    3. Apply any Filament changes to the unmodified sections of this view
    4. Test block insertion, search, and category collapse functionality
    5. Verify editorSelection variable still exists in richEditorFormComponent

    Last synced with: Filament Forms v4.x (January 2026)
    Modified section: Lines ~180-295 (customBlocks panel)
--}}
@php
    $customBlocks = $getCustomBlocks();
    $extraAttributeBag = $getExtraAttributeBag();
    $fieldWrapperView = $getFieldWrapperView();
    $id = $getId();
    $isDisabled = $isDisabled();
    $livewireKey = $getLivewireKey();
    $key = $getKey();
    $mergeTags = $getMergeTags();
    $statePath = $getStatePath();
    $mentions = $getMentionsForJs();
    $tools = $getTools();
    $toolbarButtons = $getToolbarButtons();
    $floatingToolbars = $getFloatingToolbars();
    $linkProtocols = $getLinkProtocols();
    $fileAttachmentsMaxSize = $getFileAttachmentsMaxSize();
    $fileAttachmentsAcceptedFileTypes = $getFileAttachmentsAcceptedFileTypes();

    // Enhanced block panel data (this view is only loaded when Filament v4.x is detected)
    $groupedBlocks = $getGroupedBlocks();
    $blockCategories = $getBlockCategories();
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <x-filament::input.wrapper
        :valid="! $errors->has($statePath)"
        x-cloak
        :attributes="
            \Filament\Support\prepare_inherited_attributes($extraAttributeBag)
                ->class(['fi-fo-rich-editor'])
        "
    >
        <div
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('rich-editor', 'filament/forms') }}"
            x-data="richEditorFormComponent({
                        acceptedFileTypes: @js($fileAttachmentsAcceptedFileTypes),
                        acceptedFileTypesValidationMessage: @js($fileAttachmentsAcceptedFileTypes ? __('filament-forms::components.rich_editor.file_attachments_accepted_file_types_message', ['values' => implode(', ', $fileAttachmentsAcceptedFileTypes)]) : null),
                        activePanel: @js($getActivePanel()),
                        canAttachFiles: @js($hasFileAttachments()),
                        deleteCustomBlockButtonIconHtml: @js(\Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::Trash, alias: \Filament\Forms\View\FormsIconAlias::COMPONENTS_RICH_EDITOR_PANELS_CUSTOM_BLOCK_DELETE_BUTTON)->toHtml()),
                        editCustomBlockButtonIconHtml: @js(\Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::PencilSquare, alias: \Filament\Forms\View\FormsIconAlias::COMPONENTS_RICH_EDITOR_PANELS_CUSTOM_BLOCK_EDIT_BUTTON)->toHtml()),
                        extensions: @js($getTipTapJsExtensions()),
                        floatingToolbars: @js($floatingToolbars),
                        getMentionLabelsUsing: async (mentions) => {
                            return await $wire.callSchemaComponentMethod(
                                @js($key),
                                'getMentionLabelsForJs',
                                { mentions },
                            )
                        },
                        getMentionSearchResultsUsing: async (query, char) => {
                            return await $wire.callSchemaComponentMethod(
                                @js($key),
                                'getMentionSearchResultsForJs',
                                { search: query, char },
                            )
                        },
                        hasResizableImages: @js($hasResizableImages()),
                        isDisabled: @js($isDisabled),
                        isLiveDebounced: @js($isLiveDebounced()),
                        isLiveOnBlur: @js($isLiveOnBlur()),
                        key: @js($key),
                        linkProtocols: @js($linkProtocols),
                        liveDebounce: @js($getNormalizedLiveDebounce()),
                        livewireId: @js($this->getId()),
                        maxFileSize: @js($fileAttachmentsMaxSize),
                        maxFileSizeValidationMessage: @js($fileAttachmentsMaxSize ? trans_choice('filament-forms::components.rich_editor.file_attachments_max_size_message', $fileAttachmentsMaxSize, ['max' => $fileAttachmentsMaxSize]) : null),
                        mentions: @js($mentions),
                        mergeTags: @js($mergeTags),
                        noMergeTagSearchResultsMessage: @js($getNoMergeTagSearchResultsMessage()),
                        placeholder: @js($getPlaceholder()),
                        state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')", isOptimisticallyLive: false) }},
                        statePath: @js($statePath),
                        textColors: @js($getTextColorsForJs()),
                        uploadingFileMessage: @js($getUploadingFileMessage()),
                    })"
            x-bind:class="{
                'fi-fo-rich-editor-uploading-file': isUploadingFile,
            }"
            wire:ignore
            wire:key="{{ $livewireKey }}.{{
                substr(md5(serialize([
                    $isDisabled,
                ])), 0, 64)
            }}"
        >
            @if ((! $isDisabled) && filled($toolbarButtons))
                <div class="fi-fo-rich-editor-toolbar">
                    @foreach ($toolbarButtons as $button => $buttonGroup)
                        <div class="fi-fo-rich-editor-toolbar-group">
                            @foreach ($buttonGroup as $button)
                                {{ $tools[$button] ?? throw new LogicException("Toolbar button [{$button}] cannot be found.") }}
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif

            <div
                x-show="isUploadingFile"
                x-cloak
                class="fi-fo-rich-editor-uploading-file-message"
            >
                {{ \Filament\Support\generate_loading_indicator_html() }}

                <span>
                    {{ $getUploadingFileMessage() }}
                </span>
            </div>

            <div
                x-show="! isUploadingFile && fileValidationMessage"
                x-cloak
                class="fi-fo-rich-editor-file-validation-message"
            >
                <span
                    x-text="fileValidationMessage"
                    x-show="! isUploadingFile && fileValidationMessage"
                ></span>
            </div>

            <div
                {{ $getExtraInputAttributeBag()->class(['fi-fo-rich-editor-main']) }}
            >
                <div class="fi-fo-rich-editor-content fi-prose" x-ref="editor">
                    @foreach ($floatingToolbars as $nodeName => $buttons)
                        <div
                            x-ref="floatingToolbar::{{ $nodeName }}"
                            class="fi-fo-rich-editor-floating-toolbar fi-not-prose"
                        >
                            @foreach ($buttons as $button)
                                {{ $tools[$button] }}
                            @endforeach
                        </div>
                    @endforeach
                </div>

                @if (! $isDisabled)
                    <div
                        x-show="isPanelActive()"
                        x-cloak
                        class="fi-fo-rich-editor-panels"
                    >
                        <div
                            x-show="isPanelActive('customBlocks')"
                            x-cloak
                            class="fi-fo-rich-editor-panel flex flex-col"
                        >
                            <div class="fi-fo-rich-editor-panel-header">
                                <p class="fi-fo-rich-editor-panel-heading">
                                    {{ __('filament-forms::components.rich_editor.tools.custom_blocks') }}
                                </p>

                                <div
                                    class="fi-fo-rich-editor-panel-close-btn-ctn"
                                >
                                    <button
                                        type="button"
                                        x-on:click="togglePanel()"
                                        class="fi-icon-btn"
                                    >
                                        {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::XMark, alias: \Filament\Forms\View\FormsIconAlias::COMPONENTS_RICH_EDITOR_PANELS_CUSTOM_BLOCKS_CLOSE_BUTTON) }}
                                    </button>
                                </div>
                            </div>

                            {{-- Enhanced Block Panel with Search and Categories --}}
                            @if (count($groupedBlocks) > 0)
                                <div
                                    class="fi-fo-rich-editor-custom-blocks-list flex flex-col h-full"
                                    x-data="{
                                        searchQuery: '',
                                        blocks: @js($groupedBlocks),
                                        categories: @js($blockCategories),
                                        componentKey: @js($key),
                                        expandedCategories: Object.keys(@js($groupedBlocks)),
                                        editorSelectionWarned: false,

                                        get filteredBlocks() {
                                            const query = this.searchQuery.toLowerCase().trim().replace(/\s+/g, ' ');
                                            if (!query) return this.blocks;

                                            const result = {};
                                            for (const [cat, catBlocks] of Object.entries(this.blocks)) {
                                                const filtered = catBlocks.filter(b => b.searchable.includes(query));
                                                if (filtered.length > 0) {
                                                    result[cat] = filtered;
                                                }
                                            }
                                            return result;
                                        },

                                        get totalFilteredCount() {
                                            return Object.values(this.filteredBlocks).flat().length;
                                        },

                                        get hasMultipleCategories() {
                                            return Object.keys(this.filteredBlocks).length > 1;
                                        },

                                        isExpanded(category) {
                                            return this.expandedCategories.includes(category);
                                        },

                                        toggleCategory(category) {
                                            if (this.isExpanded(category)) {
                                                this.expandedCategories = this.expandedCategories.filter(c => c !== category);
                                            } else {
                                                this.expandedCategories.push(category);
                                            }
                                        },

                                        insertBlock(blockId, selection) {
                                            $wire.mountAction(
                                                'customBlock',
                                                { editorSelection: selection, id: blockId, mode: 'insert' },
                                                { schemaComponent: this.componentKey },
                                            );
                                        }
                                    }"
                                >
                                    {{-- Search Input --}}
                                    <div class="fi-cms-block-search flex items-center gap-2 border-b border-gray-200 px-3 py-2 dark:border-white/10">
                                        <div class="shrink-0 text-gray-400 dark:text-gray-500">
                                            {!! \Filament\Support\generate_icon_html('heroicon-m-magnifying-glass', 'h-4 w-4')->toHtml() !!}
                                        </div>
                                        <input
                                            type="text"
                                            x-model="searchQuery"
                                            placeholder="Search blocks..."
                                            class="fi-input block w-full border-none bg-transparent py-1 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                                        />
                                    </div>

                                    {{-- Grouped Blocks --}}
                                    <div class="fi-cms-block-categories flex-1 overflow-y-auto">
                                        <template x-for="(catBlocks, category) in filteredBlocks" :key="category">
                                            <div class="fi-cms-block-category" x-show="catBlocks.length > 0">
                                                {{-- Collapsible Category Header --}}
                                                <template x-if="hasMultipleCategories">
                                                    <button
                                                        type="button"
                                                        x-on:click="toggleCategory(category)"
                                                        class="fi-cms-block-category-heading sticky top-0 z-10 flex w-full items-center justify-between gap-2 bg-gray-50 px-3 py-2 text-start text-xs font-semibold uppercase tracking-wider text-gray-600 transition-colors hover:bg-gray-100 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800"
                                                    >
                                                        <span x-text="categories[category]?.label ?? 'Other'"></span>
                                                        <span class="transition-transform duration-200" :class="{ 'rotate-180': isExpanded(category) }">
                                                            {!! \Filament\Support\generate_icon_html('heroicon-m-chevron-down', 'h-4 w-4')->toHtml() !!}
                                                        </span>
                                                    </button>
                                                </template>

                                                {{-- Block Buttons --}}
                                                <div
                                                    x-show="!hasMultipleCategories || isExpanded(category)"
                                                    x-cloak
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                                    x-transition:enter-end="opacity-100 translate-y-0"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100 translate-y-0"
                                                    x-transition:leave-end="opacity-0 -translate-y-1"
                                                    class="fi-cms-block-category-items flex flex-col gap-0.5 px-2 py-1"
                                                >
                                                    <template x-for="block in catBlocks" :key="block.id">
                                                        <button
                                                            draggable="true"
                                                            type="button"
                                                            x-data="{ isLoading: false }"
                                                            x-on:click="
                                                                if (typeof editorSelection === 'undefined') {
                                                                    if (!editorSelectionWarned) {
                                                                        console.warn('CmsRichEditor: editorSelection not found in parent scope');
                                                                        editorSelectionWarned = true;
                                                                    }
                                                                    return;
                                                                }
                                                                isLoading = true;
                                                                insertBlock(block.id, editorSelection);
                                                            "
                                                            x-on:dragstart="$event.dataTransfer.setData('customBlock', block.id)"
                                                            x-on:open-modal.window="isLoading = false"
                                                            x-on:run-rich-editor-commands.window="isLoading = false"
                                                            class="fi-fo-rich-editor-custom-block-btn fi-cms-block-btn flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start text-sm transition-colors hover:bg-gray-100 dark:hover:bg-white/5"
                                                        >
                                                            {{-- Loading Indicator --}}
                                                            <template x-if="isLoading">
                                                                {!! \Filament\Support\generate_loading_indicator_html((new \Illuminate\View\ComponentAttributeBag([])))->toHtml() !!}
                                                            </template>

                                                            {{-- Block Icon --}}
                                                            <template x-if="!isLoading">
                                                                <span class="fi-cms-block-icon h-4 w-4 shrink-0" x-html="block.iconHtml"></span>
                                                            </template>

                                                            {{-- Block Label --}}
                                                            <span x-text="block.label"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        {{-- No Results Message --}}
                                        <div
                                            x-show="totalFilteredCount === 0"
                                            x-cloak
                                            class="fi-cms-block-no-results px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            <p>No blocks match your search.</p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                {{-- Fallback: Standard Block List --}}
                                <div class="fi-fo-rich-editor-custom-blocks-list">
                                    @foreach ($customBlocks as $block)
                                        @php
                                            $blockId = $block::getId();
                                        @endphp

                                        <button
                                            draggable="true"
                                            type="button"
                                            x-data="{ isLoading: false }"
                                            x-on:click="
                                                isLoading = true

                                                $wire.mountAction(
                                                    'customBlock',
                                                    { editorSelection, id: @js($blockId), mode: 'insert' },
                                                    { schemaComponent: @js($key) },
                                                )
                                            "
                                            x-on:dragstart="$event.dataTransfer.setData('customBlock', @js($blockId))"
                                            x-on:open-modal.window="isLoading = false"
                                            x-on:run-rich-editor-commands.window="isLoading = false"
                                            class="fi-fo-rich-editor-custom-block-btn"
                                        >
                                            {{
                                                \Filament\Support\generate_loading_indicator_html((new \Illuminate\View\ComponentAttributeBag([
                                                    'x-show' => 'isLoading',
                                                ])))
                                            }}

                                            {{ $block::getLabel() }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div
                            x-show="isPanelActive('mergeTags')"
                            x-cloak
                            class="fi-fo-rich-editor-panel"
                        >
                            <div class="fi-fo-rich-editor-panel-header">
                                <p class="fi-fo-rich-editor-panel-heading">
                                    {{ __('filament-forms::components.rich_editor.tools.merge_tags') }}
                                </p>

                                <div
                                    class="fi-fo-rich-editor-panel-close-btn-ctn"
                                >
                                    <button
                                        type="button"
                                        x-on:click="togglePanel()"
                                        class="fi-icon-btn"
                                    >
                                        {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::XMark, alias: \Filament\Forms\View\FormsIconAlias::COMPONENTS_RICH_EDITOR_PANELS_MERGE_TAGS_CLOSE_BUTTON) }}
                                    </button>
                                </div>
                            </div>

                            <div class="fi-fo-rich-editor-merge-tags-list">
                                @foreach ($mergeTags as $tagId => $tagLabel)
                                    <button
                                        draggable="true"
                                        type="button"
                                        x-on:click="insertMergeTag(@js($tagId))"
                                        x-on:dragstart="$event.dataTransfer.setData('mergeTag', @js($tagId))"
                                        class="fi-fo-rich-editor-merge-tag-btn"
                                    >
                                        <span
                                            data-type="mergeTag"
                                            data-id="{{ $tagId }}"
                                        >
                                            {{ $tagLabel }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::input.wrapper>
</x-dynamic-component>
