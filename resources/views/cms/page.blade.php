<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->meta_title ?? $page->title }} - {{ config('app.name') }}</title>
    
    @if($page->meta_description)
        <meta name="description" content="{{ $page->meta_description }}">
    @endif
    
    @if($page->featured_image)
        <meta property="og:image" content="{{ Storage::url($page->featured_image) }}">
    @endif
    
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white">
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-gray-900">{{ config('app.name') }}</a>
                </div>
                <div class="flex items-center space-x-8">
                    <a href="/" class="text-gray-600 hover:text-gray-900">Home</a>
                    <a href="/pages" class="text-gray-600 hover:text-gray-900">Pages</a>
                    <a href="/admin" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">Admin</a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        @if($page->featured_image)
            <div class="relative h-64 bg-gray-900">
                <img src="{{ Storage::url($page->featured_image) }}" 
                     alt="{{ $page->title }}" 
                     class="w-full h-full object-cover opacity-75">
                <div class="absolute inset-0 flex items-center justify-center">
                    <h1 class="text-4xl md:text-5xl font-bold text-white text-center">
                        {{ $page->title }}
                    </h1>
                </div>
            </div>
        @else
            <div class="bg-gray-50 py-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 text-center">
                        {{ $page->title }}
                    </h1>
                </div>
            </div>
        @endif

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            @if($page->categories->count() > 0)
                <div class="mb-8">
                    <div class="flex flex-wrap gap-2">
                        @foreach($page->categories as $category)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                  style="background-color: {{ $category->color }}20; color: {{ $category->color ?? '#6b7280' }}">
                                {{ $category->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="prose prose-lg max-w-none">
                {!! $content !!}
            </div>

            @if($page->children->count() > 0)
                <div class="mt-16">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Pages</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($page->children->where('status', 'published') as $child)
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                    <a href="{{ route('cms.page', $child->slug) }}" class="hover:text-blue-600">
                                        {{ $child->title }}
                                    </a>
                                </h3>
                                @if($child->meta_description)
                                    <p class="text-gray-600 text-sm">{{ Str::limit($child->meta_description, 120) }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </main>

    <footer class="bg-gray-50 border-t mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <p class="text-center text-gray-600">
                &copy; {{ date('Y') }} {{ config('app.name') }}. Built with Laravel & Filament.
            </p>
        </div>
    </footer>
</body>
</html>