<div
    x-data="{
        searchQuery: '',
        selectedId: null,
        media: @js($media->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'file_name' => $m->file_name,
            'thumbnail' => $m->getVariantUrl('thumbnail'),
            'alt_text' => $m->alt_text,
            'dimensions' => $m->dimensions,
            'human_size' => $m->human_size,
        ])),

        get filteredMedia() {
            const query = this.searchQuery.toLowerCase().trim();
            if (!query) return this.media;
            return this.media.filter(m =>
                (m.name && m.name.toLowerCase().includes(query)) ||
                (m.file_name && m.file_name.toLowerCase().includes(query))
            );
        },

        select(item) {
            this.selectedId = item.id;
            $set('selected_media_id', item.id);
            if (item.alt_text) {
                $set('alt', item.alt_text);
            }
        },
    }"
    class="space-y-3"
>
    {{-- Search bar --}}
    <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-white/5">
        <div class="shrink-0 text-gray-400 dark:text-gray-500">
            {!! \Filament\Support\generate_icon_html('heroicon-m-magnifying-glass', 'h-4 w-4')->toHtml() !!}
        </div>
        <input
            type="text"
            x-model="searchQuery"
            placeholder="Search images..."
            class="block w-full border-none bg-transparent py-0 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
        />
    </div>

    {{-- Thumbnail grid --}}
    <div
        class="grid grid-cols-4 gap-3 sm:grid-cols-5"
        x-show="filteredMedia.length > 0"
    >
        <template x-for="item in filteredMedia" :key="item.id">
            <button
                type="button"
                x-on:click="select(item)"
                class="group relative flex flex-col overflow-hidden rounded-lg border-2 transition-all focus:outline-none"
                :class="selectedId === item.id
                    ? 'border-primary-500 ring-2 ring-primary-500/30'
                    : 'border-gray-200 hover:border-gray-300 dark:border-white/10 dark:hover:border-white/20'"
            >
                {{-- Thumbnail --}}
                <div class="aspect-square overflow-hidden bg-gray-100 dark:bg-white/5">
                    <img
                        :src="item.thumbnail"
                        :alt="item.alt_text || item.name"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    />
                </div>

                {{-- Info --}}
                <div class="px-2 py-1.5 text-start">
                    <p
                        class="truncate text-xs font-medium text-gray-700 dark:text-gray-300"
                        x-text="item.name || item.file_name"
                    ></p>
                    <p class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">
                        <span x-text="item.dimensions || ''"></span>
                        <span x-show="item.dimensions && item.human_size"> &middot; </span>
                        <span x-text="item.human_size || ''"></span>
                    </p>
                </div>

                {{-- Selected check --}}
                <div
                    x-show="selectedId === item.id"
                    x-cloak
                    class="absolute right-1.5 top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-primary-500 text-white shadow"
                >
                    {!! \Filament\Support\generate_icon_html('heroicon-m-check', 'h-3 w-3')->toHtml() !!}
                </div>
            </button>
        </template>
    </div>

    {{-- Empty state --}}
    <div
        x-show="filteredMedia.length === 0"
        x-cloak
        class="flex flex-col items-center justify-center py-12 text-center"
    >
        <div class="mb-2 text-gray-400 dark:text-gray-500">
            {!! \Filament\Support\generate_icon_html('heroicon-o-photo', 'h-10 w-10')->toHtml() !!}
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">No images found.</p>
    </div>
</div>
