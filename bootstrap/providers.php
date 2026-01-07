<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ThemeServiceProvider::class,
    App\Providers\PluginServiceProvider::class,  // Must be before AdminPanelProvider
    App\Providers\Filament\AdminPanelProvider::class,
];
