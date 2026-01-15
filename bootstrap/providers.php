<?php

return [
    App\Providers\AppServiceProvider::class,
    // ThemeServiceProvider and PluginServiceProvider are now provided by tallcms/cms package
    // App\Providers\ThemeServiceProvider::class,
    // App\Providers\PluginServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
];
