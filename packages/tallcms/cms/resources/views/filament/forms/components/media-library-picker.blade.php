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
            $set('alt', item.alt_text ?? '');
        },
    }"
>
    {{-- Search bar --}}
    <div style="display: flex; align-items: center; gap: 0.5rem; border: 1px solid rgba(0,0,0,0.1); border-radius: 0.5rem; padding: 0.5rem 0.75rem; margin-bottom: 0.75rem;">
        <div style="flex-shrink: 0; color: #9ca3af;">
            {!! \Filament\Support\generate_icon_html('heroicon-m-magnifying-glass', 'h-4 w-4')->toHtml() !!}
        </div>
        <input
            type="text"
            x-model="searchQuery"
            placeholder="Search images..."
            style="display: block; width: 100%; border: none; background: transparent; padding: 0; font-size: 0.875rem; outline: none;"
        />
    </div>

    {{-- Table list --}}
    <div
        x-show="filteredMedia.length > 0"
        style="max-height: 24rem; overflow-y: auto; border: 1px solid rgba(0,0,0,0.1); border-radius: 0.5rem;"
    >
        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(0,0,0,0.1);">
                    <th style="padding: 0.5rem 0.75rem; text-align: start; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; width: 3.5rem;"></th>
                    <th style="padding: 0.5rem 0.75rem; text-align: start; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">File Name</th>
                    <th style="padding: 0.5rem 0.75rem; text-align: start; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Dimensions</th>
                    <th style="padding: 0.5rem 0.75rem; text-align: end; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Size</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="item in filteredMedia" :key="item.id">
                    <tr
                        x-on:click="select(item)"
                        style="border-bottom: 1px solid rgba(0,0,0,0.05); cursor: pointer; transition: background-color 0.1s;"
                        :style="selectedId === item.id
                            ? 'background-color: var(--primary-50, #fffbeb);'
                            : ''"
                        x-on:mouseenter="if (selectedId !== item.id) $el.style.backgroundColor = 'rgba(0,0,0,0.02)'"
                        x-on:mouseleave="if (selectedId !== item.id) $el.style.backgroundColor = ''"
                    >
                        {{-- Thumbnail --}}
                        <td style="padding: 0.375rem 0.75rem; width: 3.5rem; vertical-align: middle;">
                            <div style="position: relative; width: 2.5rem; height: 2.5rem; border-radius: 0.375rem; overflow: hidden; background: #f3f4f6; flex-shrink: 0;">
                                <img
                                    :src="item.thumbnail"
                                    :alt="item.alt_text || item.name"
                                    style="width: 100%; height: 100%; object-fit: cover; display: block;"
                                    loading="lazy"
                                />
                                {{-- Selected check overlay --}}
                                <div
                                    x-show="selectedId === item.id"
                                    x-cloak
                                    style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.4);"
                                >
                                    <div style="color: #fff;">
                                        {!! \Filament\Support\generate_icon_html('heroicon-m-check', 'h-4 w-4')->toHtml() !!}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- File name --}}
                        <td style="padding: 0.375rem 0.75rem; vertical-align: middle;">
                            <span
                                style="font-weight: 500; color: #111827;"
                                x-text="item.name || item.file_name"
                            ></span>
                        </td>

                        {{-- Dimensions --}}
                        <td style="padding: 0.375rem 0.75rem; vertical-align: middle; color: #6b7280;">
                            <span x-text="item.dimensions || '—'"></span>
                        </td>

                        {{-- Size --}}
                        <td style="padding: 0.375rem 0.75rem; vertical-align: middle; text-align: end; color: #6b7280;">
                            <span x-text="item.human_size || '—'"></span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- Empty state --}}
    <div
        x-show="filteredMedia.length === 0"
        x-cloak
        style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 0; text-align: center;"
    >
        <div style="margin-bottom: 0.5rem; color: #9ca3af;">
            {!! \Filament\Support\generate_icon_html('heroicon-o-photo', 'h-10 w-10')->toHtml() !!}
        </div>
        <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">No images found.</p>
    </div>
</div>
