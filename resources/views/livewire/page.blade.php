{{-- Check if this is the welcome page --}}
@if($renderedContent === 'WELCOME_PAGE')
    @include('welcome.tallcms')
@else
    {{-- Block Canvas - All content is composed of blocks --}}
    <div class="w-full">
        {!! $renderedContent !!}
    </div>
@endif