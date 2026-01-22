@props(['paginator', 'paramName' => 'page'])

@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();

        // Build the window of page numbers to show
        $window = 2; // Pages to show on each side of current
        $start = max(1, $currentPage - $window);
        $end = min($lastPage, $currentPage + $window);

        // Adjust if we're near the beginning or end
        if ($currentPage <= $window + 1) {
            $end = min($lastPage, ($window * 2) + 1);
        }
        if ($currentPage >= $lastPage - $window) {
            $start = max(1, $lastPage - ($window * 2));
        }

        // Helper to build URL with the custom parameter
        $buildUrl = function($page) use ($paginator, $paramName) {
            if ($page < 1 || $page > $paginator->lastPage()) {
                return null;
            }

            $query = request()->query();
            $query[$paramName] = $page;

            // Remove page param if it's page 1
            if ($page === 1) {
                unset($query[$paramName]);
            }

            $queryString = http_build_query($query);
            $baseUrl = strtok(request()->fullUrl(), '?');

            return $queryString ? "{$baseUrl}?{$queryString}" : $baseUrl;
        };
    @endphp

    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-center">
        <div class="join">
            {{-- Previous Page --}}
            @if ($paginator->onFirstPage())
                <button class="join-item btn btn-disabled" aria-disabled="true">
                    <x-heroicon-m-chevron-left class="w-4 h-4" />
                </button>
            @else
                <a wire:navigate href="{{ $buildUrl($currentPage - 1) }}" class="join-item btn" rel="prev" aria-label="Previous page">
                    <x-heroicon-m-chevron-left class="w-4 h-4" />
                </a>
            @endif

            {{-- First Page + Ellipsis --}}
            @if ($start > 1)
                <a wire:navigate href="{{ $buildUrl(1) }}" class="join-item btn" aria-label="Go to page 1">1</a>
                @if ($start > 2)
                    <button class="join-item btn btn-disabled">...</button>
                @endif
            @endif

            {{-- Page Numbers --}}
            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $currentPage)
                    <button class="join-item btn btn-active" aria-current="page" aria-label="Page {{ $page }}">
                        {{ $page }}
                    </button>
                @else
                    <a wire:navigate href="{{ $buildUrl($page) }}" class="join-item btn" aria-label="Go to page {{ $page }}">
                        {{ $page }}
                    </a>
                @endif
            @endfor

            {{-- Last Page + Ellipsis --}}
            @if ($end < $lastPage)
                @if ($end < $lastPage - 1)
                    <button class="join-item btn btn-disabled">...</button>
                @endif
                <a wire:navigate href="{{ $buildUrl($lastPage) }}" class="join-item btn" aria-label="Go to page {{ $lastPage }}">
                    {{ $lastPage }}
                </a>
            @endif

            {{-- Next Page --}}
            @if ($paginator->hasMorePages())
                <a wire:navigate href="{{ $buildUrl($currentPage + 1) }}" class="join-item btn" rel="next" aria-label="Next page">
                    <x-heroicon-m-chevron-right class="w-4 h-4" />
                </a>
            @else
                <button class="join-item btn btn-disabled" aria-disabled="true">
                    <x-heroicon-m-chevron-right class="w-4 h-4" />
                </button>
            @endif
        </div>
    </nav>
@endif
