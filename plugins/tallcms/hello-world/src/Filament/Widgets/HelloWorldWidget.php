<?php

namespace Tallcms\HelloWorld\Filament\Widgets;

use Filament\Widgets\Widget;

class HelloWorldWidget extends Widget
{
    protected string $view = 'tallcms-helloworld::filament.widgets.hello-world-widget';

    protected int|string|array $columnSpan = 'full';
}
