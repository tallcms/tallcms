@php
    use App\Services\CustomBlockDiscoveryService;
    use App\Services\MergeTagService;
    use App\Services\HtmlSanitizerService;
    use Filament\Forms\Components\RichEditor\RichContentRenderer;
    use Illuminate\Support\Facades\Storage;

    // Get theme presets
    $textPresets = theme_text_presets();
    $textPreset = $textPresets['primary'] ?? [
        'heading' => '#111827',
        'description' => '#374151'
    ];

    // Build inline CSS custom properties
    $customProperties = collect([
        '--block-heading-color: ' . $textPreset['heading'],
        '--block-text-color: ' . $textPreset['description'],
        '--block-link-color: ' . ($textPreset['link'] ?? '#2563eb'),
        '--block-link-hover-color: ' . ($textPreset['link_hover'] ?? '#1d4ed8')
    ])->join('; ') . ';';

    // Render post content blocks
    $renderedContent = '';
    if (!empty($post->content)) {
        $renderedContent = RichContentRenderer::make($post->content)
            ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
            ->toUnsafeHtml();
        $renderedContent = MergeTagService::replaceTags($renderedContent, $post);
    }
@endphp

<article class="post-detail" style="{{ $customProperties }}">
    {{-- Featured Image --}}
    @if($post->featured_image)
        <div class="post-detail__hero relative w-full h-64 sm:h-80 md:h-96 overflow-hidden">
            <img
                src="{{ Storage::disk(cms_media_disk())->url($post->featured_image) }}"
                alt="{{ $post->title }}"
                class="w-full h-full object-cover"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
        </div>
    @endif

    {{-- Post Header --}}
    <header class="post-detail__header w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16 {{ $post->featured_image ? '-mt-24 relative z-10' : 'pt-12 sm:pt-16' }}">
        <div class="max-w-4xl mx-auto {{ $post->featured_image ? 'bg-white rounded-t-2xl shadow-lg p-6 sm:p-10' : '' }}">
            {{-- Categories --}}
            @if($post->categories->isNotEmpty())
                <div class="post-detail__categories flex flex-wrap gap-2 mb-4">
                    @foreach($post->categories as $category)
                        @php
                            $categoryUrl = $parentSlug
                                ? route('cms.page', ['slug' => $parentSlug]) . '?category=' . $category->slug
                                : route('cms.home') . '?category=' . $category->slug;
                        @endphp
                        <a
                            href="{{ $categoryUrl }}"
                            class="inline-block text-sm font-medium px-3 py-1 rounded-full transition-opacity hover:opacity-80"
                            style="background-color: {{ $category->color ?? '#e5e7eb' }}20; color: {{ $category->color ?? '#374151' }};"
                        >
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Title --}}
            <h1 class="post-detail__title text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight mb-6" style="color: var(--block-heading-color);">
                {{ $post->title }}
            </h1>

            {{-- Meta --}}
            <div class="post-detail__meta flex flex-wrap items-center gap-4 text-sm" style="color: var(--block-text-color); opacity: 0.8;">
                @if($post->author)
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                            <span class="text-sm font-medium text-gray-600">
                                {{ strtoupper(substr($post->author->name, 0, 1)) }}
                            </span>
                        </div>
                        <span class="font-medium">{{ $post->author->name }}</span>
                    </div>
                @endif

                @if($post->published_at)
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <time datetime="{{ $post->published_at->toISOString() }}">
                            {{ $post->published_at->format('F j, Y') }}
                        </time>
                    </div>
                @endif

                @if($post->reading_time)
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $post->reading_time }} min read</span>
                    </div>
                @endif
            </div>

            {{-- Excerpt --}}
            @if($post->excerpt)
                <p class="post-detail__excerpt mt-6 text-lg leading-relaxed" style="color: var(--block-text-color);">
                    {{ $post->excerpt }}
                </p>
            @endif
        </div>
    </header>

    {{-- Post Content --}}
    <div class="post-detail__content w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16 py-8 sm:py-12">
        <div class="max-w-4xl mx-auto">
            @if($renderedContent)
                <div class="post-content-body" style="color: var(--block-text-color);">
                    {!! $renderedContent !!}
                </div>
            @else
                <p class="text-center py-8" style="color: var(--block-text-color);">
                    This post has no content yet.
                </p>
            @endif
        </div>
    </div>

    {{-- Back Link --}}
    <footer class="post-detail__footer w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16 py-12">
        <div class="max-w-4xl mx-auto">
            <a
                href="{{ $parentSlug ? route('cms.page', ['slug' => $parentSlug]) : route('cms.home') }}"
                class="inline-flex items-center text-sm font-medium transition-colors"
                style="color: var(--block-link-color);"
            >
                <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to all posts
            </a>
        </div>
    </footer>
</article>
