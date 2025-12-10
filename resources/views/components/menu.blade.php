@props(['location', 'theme' => 'default', 'style' => 'horizontal'])

@php
    $menuData = menu($location);
    
    if (!$menuData) {
        return;
    }
    
    // Future: Theme override system will check themes/{$theme}/views/components/menu/{$style}.blade.php
    // For now, we'll use a simple view structure that can be easily moved to themes later
    $themeView = "components.menu.{$style}";
    
    // Fallback to horizontal if style doesn't exist
    if (!view()->exists($themeView)) {
        $themeView = "components.menu.horizontal";
    }
@endphp

@if($menuData)
    <nav {{ $attributes->merge(['class' => "menu menu-{$location} menu-theme-{$theme} menu-style-{$style}"]) }} data-menu-location="{{ $location }}">
        @include($themeView, ['items' => $menuData, 'location' => $location])
    </nav>
@endif