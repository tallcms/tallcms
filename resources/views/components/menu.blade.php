@props(['location', 'style' => 'horizontal'])

@php
    $menuData = menu($location);

    if (!$menuData) {
        return;
    }

    // Themes can override menu styles by creating: themes/{slug}/resources/views/components/menu/{$style}.blade.php
    $themeView = "components.menu.{$style}";

    // Fallback to horizontal if style doesn't exist
    if (!view()->exists($themeView)) {
        $themeView = "components.menu.horizontal";
    }
@endphp

@if($menuData)
    <nav {{ $attributes }} data-menu-location="{{ $location }}">
        @include($themeView, ['items' => $menuData, 'location' => $location])
    </nav>
@endif