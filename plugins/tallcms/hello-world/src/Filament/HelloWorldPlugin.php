<?php

namespace Tallcms\HelloWorld\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Tallcms\HelloWorld\Filament\Widgets\HelloWorldWidget;

class HelloWorldPlugin implements Plugin
{
    public function getId(): string
    {
        return 'tallcms-helloworld';
    }

    public function register(Panel $panel): void
    {
        $panel->widgets([
            HelloWorldWidget::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
