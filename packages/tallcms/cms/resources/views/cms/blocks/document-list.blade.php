@php
    use TallCms\Cms\Models\TallcmsMedia;
    use Illuminate\Support\Str;

    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');

    // Animation config
    $animationType = $animation_type ?? '';
    $animationDuration = $animation_duration ?? 'anim-duration-700';
    $animationStagger = $animation_stagger ?? false;
    $staggerDelay = (int) ($animation_stagger_delay ?? 100);

    // Resolve documents from collections
    $documents = collect();

    if (!empty($collection_ids)) {
        $query = TallcmsMedia::query()
            ->inCollection($collection_ids)
            ->where(function ($q) use ($file_types) {
                if (!empty($file_types)) {
                    $q->whereIn('mime_type', $file_types);
                } else {
                    // Default: all non-image, non-video, non-audio types
                    $q->where('mime_type', 'not like', 'image/%')
                      ->where('mime_type', 'not like', 'video/%')
                      ->where('mime_type', 'not like', 'audio/%');
                }
            });

        $query = match($order ?? 'newest') {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'name' => $query->orderBy('name', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        if ($max_items ?? null) {
            $query->limit($max_items);
        }

        $documents = $query->get();
    }

    // File type icons and labels
    $fileTypeConfig = [
        'application/pdf' => ['icon' => 'heroicon-o-document-text', 'label' => 'PDF', 'color' => 'text-red-500'],
        'application/msword' => ['icon' => 'heroicon-o-document', 'label' => 'DOC', 'color' => 'text-blue-500'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['icon' => 'heroicon-o-document', 'label' => 'DOCX', 'color' => 'text-blue-500'],
        'application/vnd.ms-excel' => ['icon' => 'heroicon-o-table-cells', 'label' => 'XLS', 'color' => 'text-green-500'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['icon' => 'heroicon-o-table-cells', 'label' => 'XLSX', 'color' => 'text-green-500'],
        'application/zip' => ['icon' => 'heroicon-o-archive-box', 'label' => 'ZIP', 'color' => 'text-yellow-500'],
    ];

    $getFileConfig = function ($mimeType) use ($fileTypeConfig) {
        return $fileTypeConfig[$mimeType] ?? [
            'icon' => 'heroicon-o-document',
            'label' => strtoupper(Str::afterLast($mimeType, '/')),
            'color' => 'text-gray-500'
        ];
    };
@endphp

<x-tallcms::animation-wrapper
    tag="section"
    :animation="$animationType"
    :controller="true"
    :id="$anchor_id ?? null"
    class="document-list-block {{ $sectionPadding }} {{ $background ?? 'bg-base-100' }} {{ $css_classes ?? '' }}"
>
    <div class="{{ $contentWidthClass ?? 'max-w-7xl mx-auto' }} {{ $contentPadding ?? 'px-4 sm:px-6 lg:px-8' }}">
        {{-- Header --}}
        @if(($title ?? false) || ($description ?? false))
            <x-tallcms::animation-wrapper
                :animation="$animationType"
                :duration="$animationDuration"
                :use-parent="true"
                class="mb-8"
            >
                @if($title ?? false)
                    <h3 class="text-2xl font-bold text-base-content mb-4">
                        {{ $title }}
                    </h3>
                @endif

                @if($description ?? false)
                    <p class="text-base-content/70">
                        {{ $description }}
                    </p>
                @endif
            </x-tallcms::animation-wrapper>
        @endif

        @if($documents->isEmpty())
            <p class="text-base-content/50 text-center py-8">
                No documents available.
            </p>
        @else
            @if(($layout ?? 'list') === 'cards')
                {{-- Cards Layout --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($documents as $index => $doc)
                        @php
                            $config = $getFileConfig($doc->mime_type);
                            $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1)) : 0;
                        @endphp
                        <x-tallcms::animation-wrapper
                            :animation="$animationType"
                            :duration="$animationDuration"
                            :use-parent="true"
                            :delay="$itemDelay"
                        >
                            <a
                                href="{{ $doc->download_url }}"
                                download
                                class="block p-4 rounded-lg border border-base-300 hover:border-primary hover:shadow-md transition-all group h-full"
                            >
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 p-3 rounded-lg bg-base-200 group-hover:bg-primary/10 transition-colors">
                                        <x-dynamic-component :component="$config['icon']" class="w-8 h-8 {{ $config['color'] }}" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-medium text-base-content truncate group-hover:text-primary transition-colors">
                                            {{ $doc->name }}
                                        </h4>
                                        <div class="flex items-center gap-2 mt-1 text-sm text-base-content/60">
                                            @if($show_file_type ?? true)
                                                <span class="badge badge-sm badge-ghost">{{ $config['label'] }}</span>
                                            @endif
                                            @if($show_file_size ?? true)
                                                <span>{{ $doc->human_size }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </x-tallcms::animation-wrapper>
                    @endforeach
                </div>

            @elseif(($layout ?? 'list') === 'compact')
                {{-- Compact Layout --}}
                <div class="space-y-1">
                    @foreach($documents as $index => $doc)
                        @php
                            $config = $getFileConfig($doc->mime_type);
                            $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1)) : 0;
                        @endphp
                        <x-tallcms::animation-wrapper
                            :animation="$animationType"
                            :duration="$animationDuration"
                            :use-parent="true"
                            :delay="$itemDelay"
                        >
                            <a
                                href="{{ $doc->download_url }}"
                                download
                                class="flex items-center gap-3 py-2 px-3 rounded hover:bg-base-200 transition-colors group"
                            >
                                <x-dynamic-component :component="$config['icon']" class="w-5 h-5 {{ $config['color'] }} flex-shrink-0" />
                                <span class="flex-1 truncate text-base-content group-hover:text-primary transition-colors">
                                    {{ $doc->name }}
                                </span>
                                @if($show_file_type ?? true)
                                    <span class="text-xs text-base-content/50">{{ $config['label'] }}</span>
                                @endif
                                @if($show_file_size ?? true)
                                    <span class="text-xs text-base-content/50">{{ $doc->human_size }}</span>
                                @endif
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-base-content/30 group-hover:text-primary transition-colors" />
                            </a>
                        </x-tallcms::animation-wrapper>
                    @endforeach
                </div>

            @else
                {{-- List Layout (default) --}}
                <div class="divide-y divide-base-300">
                    @foreach($documents as $index => $doc)
                        @php
                            $config = $getFileConfig($doc->mime_type);
                            $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1)) : 0;
                        @endphp
                        <x-tallcms::animation-wrapper
                            :animation="$animationType"
                            :duration="$animationDuration"
                            :use-parent="true"
                            :delay="$itemDelay"
                        >
                            <a
                                href="{{ $doc->download_url }}"
                                download
                                class="flex items-center gap-4 py-4 hover:bg-base-200/50 -mx-4 px-4 transition-colors group"
                            >
                                <div class="flex-shrink-0 p-2 rounded-lg bg-base-200 group-hover:bg-primary/10 transition-colors">
                                    <x-dynamic-component :component="$config['icon']" class="w-6 h-6 {{ $config['color'] }}" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-base-content group-hover:text-primary transition-colors">
                                        {{ $doc->name }}
                                    </h4>
                                    @if(($show_file_type ?? true) || ($show_file_size ?? true))
                                        <div class="flex items-center gap-2 mt-0.5 text-sm text-base-content/60">
                                            @if($show_file_type ?? true)
                                                <span>{{ $config['label'] }}</span>
                                            @endif
                                            @if(($show_file_type ?? true) && ($show_file_size ?? true))
                                                <span>&middot;</span>
                                            @endif
                                            @if($show_file_size ?? true)
                                                <span>{{ $doc->human_size }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-base-content/30 group-hover:text-primary transition-colors flex-shrink-0" />
                            </a>
                        </x-tallcms::animation-wrapper>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</x-tallcms::animation-wrapper>
