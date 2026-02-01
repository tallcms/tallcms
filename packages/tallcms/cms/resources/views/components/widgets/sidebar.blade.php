@props(['page', 'widgets' => [], 'renderedContent' => ''])

@php
    $registry = app(\TallCms\Cms\Services\WidgetRegistry::class);
@endphp

<div {{ $attributes->merge(['class' => 'space-y-6']) }}>
    @foreach($widgets as $widgetConfig)
        @php
            $widgetSlug = $widgetConfig['widget'] ?? null;
            $widgetDef = $widgetSlug ? $registry->getWidget($widgetSlug) : null;
        @endphp

        @if($widgetDef)
            @php
                // Merge default settings with configured settings
                $settings = array_merge(
                    collect($widgetDef['settings_schema'] ?? [])->mapWithKeys(fn($s, $k) => [$k => $s['default'] ?? null])->toArray(),
                    $widgetConfig['settings'] ?? []
                );
            @endphp

            <x-dynamic-component
                :component="$widgetDef['component']"
                :page="$page"
                :rendered-content="$renderedContent"
                :settings="$settings"
            />
        @endif
    @endforeach
</div>
