<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Pages - {{ config('app.name') }}</title>
    <meta name="description" content="Browse all published pages on {{ config('app.name') }}">
    
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
                    <a href="/pages" class="text-gray-900 font-medium">Pages</a>
                    <a href="/admin" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">Admin</a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        <div class="bg-gray-50 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 text-center mb-4">
                    All Pages
                </h1>
                <p class="text-lg text-gray-600 text-center max-w-2xl mx-auto">
                    Explore our collection of published pages built with our powerful CMS.
                </p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            @if($pages->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($pages as $page)
                        <article class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition">
                            @if($page->featured_image)
                                <div class="aspect-w-16 aspect-h-9">
                                    <img src="{{ Storage::url($page->featured_image) }}" 
                                         alt="{{ $page->title }}" 
                                         class="w-full h-48 object-cover">
                                </div>
                            @endif
                            
                            <div class="p-6">
                                <div class="flex items-center mb-4">
                                    @foreach($page->categories->take(3) as $category)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mr-2"
                                              style="background-color: {{ $category->color }}20; color: {{ $category->color ?? '#6b7280' }}">
                                            {{ $category->name }}
                                        </span>
                                    @endforeach
                                </div>
                                
                                <h2 class="text-xl font-bold text-gray-900 mb-2">
                                    <a href="{{ route('cms.page', $page->slug) }}" class="hover:text-blue-600">
                                        {{ $page->title }}
                                    </a>
                                </h2>
                                
                                @if($page->meta_description)
                                    <p class="text-gray-600 mb-4">
                                        {{ Str::limit($page->meta_description, 150) }}
                                    </p>
                                @endif
                                
                                <div class="flex items-center justify-between">
                                    <a href="{{ route('cms.page', $page->slug) }}" 
                                       class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                                        Read more
                                        <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    </a>
                                    
                                    @if($page->published_at)
                                        <time class="text-sm text-gray-500">
                                            {{ $page->published_at->format('M j, Y') }}
                                        </time>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16">
                    <div class="max-w-md mx-auto">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A9.971 9.971 0 0118 28a9.971 9.971 0 014 .286" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No pages found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating your first page in the admin panel.</p>
                        <div class="mt-6">
                            <a href="/admin" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Go to Admin Panel
                            </a>
                        </div>
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