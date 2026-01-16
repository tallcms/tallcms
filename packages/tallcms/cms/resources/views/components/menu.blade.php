@props(['location', 'style' => 'horizontal'])

@php
    $menuData = menu($location);

    if (!$menuData) {
        return;
    }

    // Check for host app override first, then package views
    $themeView = "components.menu.{$style}";
    $packageView = "tallcms::components.menu.{$style}";

    // Use host app view if exists, otherwise package view
    if (!view()->exists($themeView)) {
        $themeView = view()->exists($packageView) ? $packageView : "tallcms::components.menu.horizontal";
    }
@endphp

@if($menuData)
    <nav {{ $attributes }} data-menu-location="{{ $location }}">
        @include($themeView, ['items' => $menuData, 'location' => $location])
    </nav>
@endif
