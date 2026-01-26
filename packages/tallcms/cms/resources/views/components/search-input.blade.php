@props(['placeholder' => null])

@if(config('tallcms.search.enabled', true) && Route::has('tallcms.search'))
<form action="{{ route('tallcms.search') }}" method="GET" {{ $attributes }}>
    <input
        type="search"
        name="q"
        value="{{ request('q') }}"
        placeholder="{{ $placeholder ?? __('Search...') }}"
        class="input input-bordered w-full"
        minlength="{{ config('tallcms.search.min_query_length', 2) }}"
    />
</form>
@endif
