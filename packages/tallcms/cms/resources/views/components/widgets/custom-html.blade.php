@props(['page' => null, 'renderedContent' => '', 'settings' => []])

@php
    $content = $settings['content'] ?? '';

    // Sanitize HTML using HtmlSanitizerService (HTMLPurifier-based)
    $safeContent = \TallCms\Cms\Services\HtmlSanitizerService::sanitize($content);
@endphp

@if($safeContent)
<div class="bg-base-100 rounded-lg p-4 shadow-sm custom-html-widget">
    {!! $safeContent !!}
</div>
@endif
