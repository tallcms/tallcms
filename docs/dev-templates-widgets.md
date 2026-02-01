---
title: "Template & Widget Development"
slug: "templates-widgets"
audience: "developer"
category: "developers"
order: 15
prerequisites:
  - "themes"
---

# Template & Widget Development

> **What you'll learn:** How to create custom page templates and sidebar widgets for TallCMS.

---

## Template System Architecture

TallCMS uses a registry-based template system:

```
TemplateRegistry → resolves template → Blade view
                                     ↓
                              WidgetRegistry → resolves widgets → widget components
```

Templates are discovered from:
1. Active theme (`themes/{slug}/resources/views/templates/`)
2. Package defaults (`packages/tallcms/cms/resources/views/templates/`)

---

## Creating Custom Templates

### Quick Start

1. Create a template file in your theme:

```bash
themes/my-theme/resources/views/templates/portfolio.blade.php
```

2. Register it in `theme.json`:

```json
{
  "templates": {
    "portfolio": {
      "label": "Portfolio",
      "description": "Grid layout for portfolio showcases",
      "supports": ["content_width"],
      "has_sidebar": false,
      "minimal_chrome": false
    }
  }
}
```

3. Create the Blade template:

```blade
{{-- Portfolio Template --}}
<div class="portfolio-layout">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <section id="content">
            {!! $renderedContent !!}
        </section>

        @foreach($allPages as $pageData)
            <section id="{{ $pageData['anchor'] }}">
                {!! $pageData['content'] !!}
            </section>
        @endforeach
    </div>
</div>
```

### Template Configuration Options

| Option | Type | Description |
|--------|------|-------------|
| `label` | string | Display name in admin |
| `description` | string | Helper text shown to editors |
| `supports` | array | Supported features: `content_width`, `breadcrumbs`, `sidebar`, `toc` |
| `has_sidebar` | bool | Whether template includes a sidebar |
| `sidebar_position` | string | `left` or `right` (if `has_sidebar` is true) |
| `default_widgets` | array | Default sidebar widgets |
| `minimal_chrome` | bool | Hide navigation/footer |

### Template Variables

Templates receive these variables:

| Variable | Type | Description |
|----------|------|-------------|
| `$page` | CmsPage | Current page model |
| `$renderedContent` | string | Rendered HTML content |
| `$allPages` | array | Additional pages (SPA mode) |
| `$sidebarWidgets` | array | Configured widgets |
| `$templateConfig` | array | Template configuration |

### Sidebar Template Example

```blade
{{-- Custom sidebar template --}}
<div class="custom-sidebar-layout">
    <div class="container mx-auto px-4 py-8 lg:flex lg:gap-8">
        {{-- Main content --}}
        <main class="flex-1 min-w-0">
            <section id="content">
                {!! $renderedContent !!}
            </section>

            @foreach($allPages as $pageData)
                <section id="{{ $pageData['anchor'] }}">
                    {!! $pageData['content'] !!}
                </section>
            @endforeach
        </main>

        {{-- Sidebar --}}
        <aside class="w-full lg:w-80 flex-shrink-0 mt-8 lg:mt-0">
            <div class="sticky top-24 space-y-6">
                <x-tallcms::widgets.sidebar
                    :page="$page"
                    :widgets="$sidebarWidgets"
                    :rendered-content="$renderedContent"
                />
            </div>
        </aside>
    </div>
</div>
```

---

## Creating Custom Widgets

### Quick Start

1. Create a widget component:

```bash
themes/my-theme/resources/views/components/widgets/newsletter.blade.php
```

```blade
@props(['page' => null, 'renderedContent' => '', 'settings' => []])

@php
    $title = $settings['title'] ?? 'Subscribe';
    $buttonText = $settings['button_text'] ?? 'Subscribe';
@endphp

<div class="bg-base-100 rounded-lg p-6 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">{{ $title }}</h3>
    <form action="/newsletter" method="POST" class="space-y-3">
        @csrf
        <input
            type="email"
            name="email"
            placeholder="your@email.com"
            class="input input-bordered w-full"
            required
        >
        <button type="submit" class="btn btn-primary w-full">
            {{ $buttonText }}
        </button>
    </form>
</div>
```

2. Register in a service provider:

```php
use TallCms\Cms\Services\WidgetRegistry;

public function boot(): void
{
    $this->app->booted(function () {
        $registry = app(WidgetRegistry::class);

        $registry->register('newsletter', [
            'label' => 'Newsletter Signup',
            'description' => 'Email subscription form',
            'component' => 'theme::components.widgets.newsletter',
            'settings_schema' => [
                'title' => [
                    'type' => 'text',
                    'default' => 'Subscribe',
                    'label' => 'Title',
                ],
                'button_text' => [
                    'type' => 'text',
                    'default' => 'Subscribe',
                    'label' => 'Button Text',
                ],
            ],
        ]);
    });
}
```

### Widget Component Props

All widgets receive:

| Prop | Type | Description |
|------|------|-------------|
| `$page` | CmsPage | Current page model |
| `$renderedContent` | string | Page HTML (for TOC extraction) |
| `$settings` | array | Widget settings from admin |

### Settings Schema Types

| Type | Description | Extra Options |
|------|-------------|---------------|
| `text` | Single-line text input | - |
| `textarea` | Multi-line text | - |
| `number` | Numeric input | - |
| `boolean` | Toggle switch | - |
| `select` | Dropdown selection | `options` array |

### Select Field Example

```php
'style' => [
    'type' => 'select',
    'default' => 'card',
    'label' => 'Display Style',
    'options' => [
        'card' => 'Card',
        'minimal' => 'Minimal',
        'bordered' => 'Bordered',
    ],
],
```

### Permission-Gated Widgets

Restrict widgets to specific user permissions:

```php
$registry->register('embed-code', [
    'label' => 'Embed Code',
    'description' => 'Custom embed snippets',
    'component' => 'theme::components.widgets.embed',
    'requires_permission' => 'ManageSettings',  // Only admins
    'settings_schema' => [
        'code' => ['type' => 'textarea', 'label' => 'Embed Code'],
    ],
]);
```

---

## TemplateRegistry API

```php
use TallCms\Cms\Services\TemplateRegistry;

$registry = app(TemplateRegistry::class);

// Get all available templates
$templates = $registry->getAvailableTemplates();

// Get options for Select field
$options = $registry->getTemplateOptions();

// Resolve template view path
$view = $registry->resolveTemplateView('sidebar-left');
// Returns: 'tallcms::templates.sidebar-left'

// Get template configuration
$config = $registry->getTemplateConfig('documentation');
// Returns: ['label' => 'Documentation', 'has_sidebar' => true, ...]
```

---

## WidgetRegistry API

```php
use TallCms\Cms\Services\WidgetRegistry;

$registry = app(WidgetRegistry::class);

// Get all widgets (optionally filtered by user permissions)
$widgets = $registry->getAvailableWidgets(auth()->user());

// Get options for Select field
$options = $registry->getWidgetOptions(auth()->user());

// Get single widget config
$widget = $registry->getWidget('recent-posts');

// Get settings schema for form building
$schema = $registry->getSettingsSchema('recent-posts');

// Register a custom widget
$registry->register('my-widget', [
    'label' => 'My Widget',
    'component' => 'theme::widgets.my-widget',
    'settings_schema' => [...],
]);
```

---

## Heading IDs for TOC

TallCMS automatically adds `id` attributes to `h2`, `h3`, and `h4` headings for TOC navigation. The ID is generated from the heading text using `Str::slug()`.

```html
<!-- Input -->
<h2>Getting Started</h2>

<!-- Output -->
<h2 id="getting-started">Getting Started</h2>
```

Headings that already have IDs are preserved.

---

## Overriding Built-in Templates

Override package templates by creating files at:

```
resources/views/vendor/tallcms/templates/{template}.blade.php
```

Or in your theme:

```
themes/{slug}/resources/views/templates/{template}.blade.php
```

---

## Common Pitfalls

**"Widget not appearing in dropdown"**
Ensure the widget is registered during the `booted` event, not `register`.

**"Template not found"**
Check the template is registered in `theme.json` and the view file exists.

**"Widget settings not saving"**
Verify your settings schema types match the form field types in `CmsPageForm.php`.

**"TOC widget shows no headings"**
Headings must be `h2`, `h3`, or `h4`. The widget uses `$renderedContent`, which must be passed from the template.

---

## Next Steps

- [Theme development](themes)
- [Block development](blocks)
- [Plugin development](plugins)
