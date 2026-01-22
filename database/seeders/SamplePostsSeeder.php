<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\CmsCategory;
use App\Models\CmsPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SamplePostsSeeder extends Seeder
{
    protected ?User $author = null;

    protected array $categories = [];

    public function run(): void
    {
        $this->author = User::first() ?? User::factory()->create([
            'name' => 'TallCMS',
            'email' => 'hello@tallcms.com',
        ]);

        $this->createCategories();
        $this->createPosts();

        $this->command->info('Created 50 sample blog posts with categories!');
    }

    protected function createCategories(): void
    {
        $categoryData = [
            ['name' => 'Tutorials', 'color' => '#3b82f6', 'description' => 'Step-by-step guides and how-tos'],
            ['name' => 'News', 'color' => '#10b981', 'description' => 'Latest updates and announcements'],
            ['name' => 'Tips & Tricks', 'color' => '#f59e0b', 'description' => 'Quick tips for better development'],
            ['name' => 'Case Studies', 'color' => '#8b5cf6', 'description' => 'Real-world project examples'],
            ['name' => 'Laravel', 'color' => '#ef4444', 'description' => 'Laravel framework topics'],
            ['name' => 'Livewire', 'color' => '#ec4899', 'description' => 'Livewire component development'],
            ['name' => 'Filament', 'color' => '#f97316', 'description' => 'Filament admin panel tips'],
            ['name' => 'Tailwind CSS', 'color' => '#06b6d4', 'description' => 'Styling with Tailwind'],
        ];

        foreach ($categoryData as $data) {
            $category = CmsCategory::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                $data
            );
            $this->categories[$data['name']] = $category;
        }

        $this->command->info('Created ' . count($this->categories) . ' categories');
    }

    protected function createPosts(): void
    {
        $posts = $this->getPostData();

        foreach ($posts as $index => $postData) {
            $post = CmsPost::updateOrCreate(
                ['slug' => $postData['slug']],
                [
                    'title' => $postData['title'],
                    'excerpt' => $postData['excerpt'],
                    'content' => $this->wrapContent($postData['content']),
                    'meta_title' => $postData['title'],
                    'meta_description' => $postData['excerpt'],
                    'status' => ContentStatus::Published->value,
                    'published_at' => now()->subDays($index),
                    'is_featured' => $postData['featured'] ?? false,
                    'author_id' => $this->author->id,
                ]
            );

            // Attach categories
            if (!empty($postData['categories'])) {
                $categoryIds = collect($postData['categories'])
                    ->map(fn ($name) => $this->categories[$name]->id ?? null)
                    ->filter()
                    ->toArray();
                $post->categories()->sync($categoryIds);
            }
        }

        $this->command->info('Created ' . count($posts) . ' blog posts');
    }

    protected function wrapContent(string $content): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'customBlock',
                    'attrs' => [
                        'id' => 'content_block',
                        'config' => [
                            'content' => $content,
                            'heading_level' => 'h2',
                            'width' => 'normal',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function getPostData(): array
    {
        return [
            [
                'slug' => 'getting-started-with-laravel-12',
                'title' => 'Getting Started with Laravel 12',
                'excerpt' => 'A comprehensive guide to setting up your first Laravel 12 project with all the modern tooling.',
                'content' => '<p>Laravel 12 brings exciting new features and improvements. In this guide, we\'ll walk through setting up a new project from scratch.</p><h3>Installation</h3><p>Start by creating a new project using Composer:</p><pre><code>composer create-project laravel/laravel my-app</code></pre><p>Laravel 12 requires PHP 8.2 or higher and comes with improved performance out of the box.</p>',
                'categories' => ['Tutorials', 'Laravel'],
                'featured' => true,
            ],
            [
                'slug' => 'mastering-livewire-3-components',
                'title' => 'Mastering Livewire 3 Components',
                'excerpt' => 'Learn how to build reactive components with Livewire 3 and create dynamic user interfaces.',
                'content' => '<p>Livewire 3 revolutionizes how we build interactive Laravel applications. This tutorial covers component creation, state management, and real-time updates.</p><h3>Creating Your First Component</h3><pre><code>php artisan make:livewire Counter</code></pre><p>Livewire components combine the simplicity of Blade with the reactivity of modern JavaScript frameworks.</p>',
                'categories' => ['Tutorials', 'Livewire'],
                'featured' => true,
            ],
            [
                'slug' => 'tailwind-css-best-practices',
                'title' => 'Tailwind CSS Best Practices for 2025',
                'excerpt' => 'Discover the best practices for using Tailwind CSS effectively in your projects.',
                'content' => '<p>Tailwind CSS has become the go-to utility-first framework. Here are the best practices we\'ve learned building dozens of projects.</p><h3>Use Component Extraction</h3><p>When you find yourself repeating the same utility combinations, extract them into components or use @apply sparingly.</p>',
                'categories' => ['Tips & Tricks', 'Tailwind CSS'],
            ],
            [
                'slug' => 'filament-v4-whats-new',
                'title' => 'Filament v4: What\'s New',
                'excerpt' => 'Explore all the new features and improvements in Filament v4 for Laravel.',
                'content' => '<p>Filament v4 is a major release with significant improvements to the admin panel framework. Let\'s explore what\'s new.</p><h3>Key Features</h3><ul><li>Improved performance</li><li>New schema builder</li><li>Better type safety</li><li>Enhanced theming</li></ul>',
                'categories' => ['News', 'Filament'],
                'featured' => true,
            ],
            [
                'slug' => 'building-a-blog-with-tallcms',
                'title' => 'Building a Blog with TallCMS',
                'excerpt' => 'Step-by-step guide to creating a fully-featured blog using TallCMS.',
                'content' => '<p>TallCMS makes building blogs incredibly easy. This tutorial walks you through creating a complete blog with categories, pagination, and RSS feeds.</p><h3>Setting Up the Blog</h3><p>Start by creating a new page and adding the Posts Block. Configure the display options to match your design.</p>',
                'categories' => ['Tutorials', 'Laravel'],
            ],
            [
                'slug' => 'optimizing-laravel-performance',
                'title' => 'Optimizing Laravel Performance',
                'excerpt' => 'Tips and techniques for making your Laravel applications faster.',
                'content' => '<p>Performance matters. Here are proven techniques to speed up your Laravel applications.</p><h3>Caching Strategies</h3><p>Use route caching, config caching, and view caching in production. Implement Redis for session and cache drivers.</p>',
                'categories' => ['Tips & Tricks', 'Laravel'],
            ],
            [
                'slug' => 'authentication-best-practices',
                'title' => 'Authentication Best Practices in Laravel',
                'excerpt' => 'Secure your Laravel applications with these authentication best practices.',
                'content' => '<p>Security should never be an afterthought. Learn how to implement robust authentication in your Laravel applications.</p><h3>Use Laravel Sanctum</h3><p>For SPA and API authentication, Laravel Sanctum provides a simple, secure solution.</p>',
                'categories' => ['Tutorials', 'Laravel'],
            ],
            [
                'slug' => 'livewire-vs-inertia',
                'title' => 'Livewire vs Inertia: Which to Choose?',
                'excerpt' => 'A comprehensive comparison of Livewire and Inertia.js for Laravel developers.',
                'content' => '<p>Both Livewire and Inertia.js are excellent choices for building modern Laravel applications. Here\'s how to decide which one to use.</p><h3>When to Use Livewire</h3><p>Choose Livewire when you want to stay in PHP and don\'t need complex client-side interactions.</p>',
                'categories' => ['Tips & Tricks', 'Livewire'],
            ],
            [
                'slug' => 'creating-custom-filament-widgets',
                'title' => 'Creating Custom Filament Widgets',
                'excerpt' => 'Learn how to build custom dashboard widgets for Filament.',
                'content' => '<p>Filament\'s widget system is powerful and flexible. This guide shows you how to create custom widgets for your admin dashboard.</p><h3>Widget Types</h3><p>Filament supports stats widgets, chart widgets, and custom widgets that can display any content.</p>',
                'categories' => ['Tutorials', 'Filament'],
            ],
            [
                'slug' => 'responsive-design-with-tailwind',
                'title' => 'Responsive Design with Tailwind CSS',
                'excerpt' => 'Master responsive design using Tailwind\'s mobile-first breakpoints.',
                'content' => '<p>Tailwind makes responsive design intuitive with its mobile-first approach. Learn how to create layouts that look great on all devices.</p><h3>Mobile-First Approach</h3><p>Start with mobile styles and add breakpoint prefixes for larger screens: sm:, md:, lg:, xl:, 2xl:.</p>',
                'categories' => ['Tutorials', 'Tailwind CSS'],
            ],
            [
                'slug' => 'database-optimization-tips',
                'title' => 'Database Optimization Tips for Laravel',
                'excerpt' => 'Improve your Laravel application\'s database performance with these tips.',
                'content' => '<p>Database queries are often the bottleneck in web applications. Here\'s how to optimize them.</p><h3>Use Eager Loading</h3><p>Prevent N+1 queries by eager loading relationships with the with() method.</p>',
                'categories' => ['Tips & Tricks', 'Laravel'],
            ],
            [
                'slug' => 'building-apis-with-laravel',
                'title' => 'Building RESTful APIs with Laravel',
                'excerpt' => 'A complete guide to building robust APIs with Laravel.',
                'content' => '<p>Laravel provides excellent tools for building APIs. This guide covers everything from routing to authentication.</p><h3>API Routes</h3><p>Define your API routes in routes/api.php and use resource controllers for RESTful endpoints.</p>',
                'categories' => ['Tutorials', 'Laravel'],
            ],
            [
                'slug' => 'dark-mode-with-daisyui',
                'title' => 'Implementing Dark Mode with DaisyUI',
                'excerpt' => 'Add dark mode support to your TallCMS site using DaisyUI themes.',
                'content' => '<p>DaisyUI makes dark mode implementation trivial. Learn how to add theme switching to your site.</p><h3>Theme Switching</h3><p>DaisyUI themes can be switched by changing the data-theme attribute on the html element.</p>',
                'categories' => ['Tutorials', 'Tailwind CSS'],
            ],
            [
                'slug' => 'form-validation-in-livewire',
                'title' => 'Form Validation in Livewire',
                'excerpt' => 'Master form validation techniques in Livewire components.',
                'content' => '<p>Livewire provides powerful form validation that integrates seamlessly with Laravel\'s validation rules.</p><h3>Real-time Validation</h3><p>Use the updated lifecycle hook to validate fields as users type.</p>',
                'categories' => ['Tutorials', 'Livewire'],
            ],
            [
                'slug' => 'deploying-laravel-to-production',
                'title' => 'Deploying Laravel to Production',
                'excerpt' => 'A comprehensive guide to deploying Laravel applications.',
                'content' => '<p>Deploying Laravel correctly ensures your application runs smoothly in production. Here\'s a complete checklist.</p><h3>Optimization Commands</h3><pre><code>php artisan config:cache\nphp artisan route:cache\nphp artisan view:cache</code></pre>',
                'categories' => ['Tutorials', 'Laravel'],
            ],
            [
                'slug' => 'testing-livewire-components',
                'title' => 'Testing Livewire Components',
                'excerpt' => 'Learn how to write effective tests for your Livewire components.',
                'content' => '<p>Testing ensures your Livewire components work correctly. This guide covers unit and feature testing.</p><h3>Component Testing</h3><p>Use Livewire::test() to test component behavior, state changes, and DOM updates.</p>',
                'categories' => ['Tutorials', 'Livewire'],
            ],
            [
                'slug' => 'filament-table-customization',
                'title' => 'Customizing Filament Tables',
                'excerpt' => 'Advanced techniques for customizing Filament table components.',
                'content' => '<p>Filament tables are highly customizable. Learn how to add custom columns, filters, and actions.</p><h3>Custom Columns</h3><p>Create custom column types for specialized data display needs.</p>',
                'categories' => ['Tutorials', 'Filament'],
            ],
            [
                'slug' => 'css-grid-with-tailwind',
                'title' => 'CSS Grid Layouts with Tailwind',
                'excerpt' => 'Create complex grid layouts using Tailwind\'s grid utilities.',
                'content' => '<p>Tailwind\'s grid utilities make complex layouts simple. Learn how to create responsive grids.</p><h3>Grid Basics</h3><p>Use grid, grid-cols-*, and gap-* utilities to create flexible layouts.</p>',
                'categories' => ['Tutorials', 'Tailwind CSS'],
            ],
            [
                'slug' => 'laravel-queues-explained',
                'title' => 'Laravel Queues Explained',
                'excerpt' => 'Understanding and implementing queues in Laravel applications.',
                'content' => '<p>Queues allow you to defer time-consuming tasks for improved response times. Here\'s how to use them effectively.</p><h3>Creating Jobs</h3><pre><code>php artisan make:job ProcessPodcast</code></pre>',
                'categories' => ['Tutorials', 'Laravel'],
            ],
            [
                'slug' => 'e-commerce-case-study',
                'title' => 'Building an E-commerce Site: A Case Study',
                'excerpt' => 'How we built a complete e-commerce platform with TallCMS and Laravel.',
                'content' => '<p>This case study walks through building a full e-commerce platform using the TALL stack.</p><h3>The Challenge</h3><p>The client needed a fast, SEO-friendly e-commerce site with custom features.</p>',
                'categories' => ['Case Studies', 'Laravel'],
                'featured' => true,
            ],
            [
                'slug' => 'livewire-file-uploads',
                'title' => 'Handling File Uploads in Livewire',
                'excerpt' => 'A complete guide to file upload handling in Livewire components.',
                'content' => '<p>File uploads in Livewire are straightforward but have some nuances. Here\'s everything you need to know.</p><h3>Basic Upload</h3><p>Use the WithFileUploads trait and wire:model for file inputs.</p>',
                'categories' => ['Tutorials', 'Livewire'],
            ],
            [
                'slug' => 'filament-form-builder',
                'title' => 'Advanced Filament Form Building',
                'excerpt' => 'Master the Filament form builder with advanced techniques.',
                'content' => '<p>Filament\'s form builder is incredibly powerful. Learn advanced patterns for complex forms.</p><h3>Conditional Fields</h3><p>Use visible() and hidden() methods to show/hide fields based on other field values.</p>',
                'categories' => ['Tutorials', 'Filament'],
            ],
            [
                'slug' => 'tailwind-animations',
                'title' => 'Creating Animations with Tailwind CSS',
                'excerpt' => 'Add smooth animations to your UI using Tailwind utilities.',
                'content' => '<p>Animations enhance user experience. Tailwind provides utility classes for common animations.</p><h3>Built-in Animations</h3><p>Use animate-spin, animate-ping, animate-pulse, and animate-bounce for instant animations.</p>',
                'categories' => ['Tips & Tricks', 'Tailwind CSS'],
            ],
            [
                'slug' => 'laravel-events-listeners',
                'title' => 'Laravel Events and Listeners',
                'excerpt' => 'Decouple your code using Laravel\'s event system.',
                'content' => '<p>Events and listeners help keep your code organized and decoupled. Here\'s how to use them effectively.</p><h3>Creating Events</h3><pre><code>php artisan make:event OrderPlaced</code></pre>',
                'categories' => ['Tutorials', 'Laravel'],
            ],
            [
                'slug' => 'seo-optimization-tallcms',
                'title' => 'SEO Optimization with TallCMS',
                'excerpt' => 'Optimize your TallCMS site for search engines.',
                'content' => '<p>TallCMS includes built-in SEO features. Learn how to maximize your search engine visibility.</p><h3>Meta Tags</h3><p>Set meta titles and descriptions for each page and post. Use the SEO settings for site-wide defaults.</p>',
                'categories' => ['Tips & Tricks', 'Laravel'],
            ],
            [
                'slug' => 'livewire-polling',
                'title' => 'Real-time Updates with Livewire Polling',
                'excerpt' => 'Implement real-time features using Livewire\'s polling mechanism.',
                'content' => '<p>Livewire polling provides a simple way to update components at regular intervals.</p><h3>Basic Polling</h3><p>Add wire:poll to any element to refresh the component every 2.5 seconds.</p>',
                'categories' => ['Tutorials', 'Livewire'],
            ],
            [
                'slug' => 'filament-actions',
                'title' => 'Creating Custom Filament Actions',
                'excerpt' => 'Build custom actions for your Filament resources.',
                'content' => '<p>Actions in Filament allow users to perform operations on records. Learn to create custom actions.</p><h3>Table Actions</h3><p>Add actions to table rows for quick record operations.</p>',
                'categories' => ['Tutorials', 'Filament'],
            ],
            [
                'slug' => 'tailwind-plugins',
                'title' => 'Essential Tailwind CSS Plugins',
                'excerpt' => 'Extend Tailwind with these must-have plugins.',
                'content' => '<p>Tailwind\'s plugin ecosystem extends its capabilities. Here are the plugins we use on every project.</p><h3>Typography Plugin</h3><p>The @tailwindcss/typography plugin provides beautiful prose styling for user content.</p>',
                'categories' => ['Tips & Tricks', 'Tailwind CSS'],
            ],
            [
                'slug' => 'laravel-middleware',
                'title' => 'Understanding Laravel Middleware',
                'excerpt' => 'A deep dive into Laravel middleware and its applications.',
                'content' => '<p>Middleware filters HTTP requests entering your application. Here\'s how to use it effectively.</p><h3>Creating Middleware</h3><pre><code>php artisan make:middleware EnsureUserIsAdmin</code></pre>',
                'categories' => ['Tutorials', 'Laravel'],
            ],
            [
                'slug' => 'saas-case-study',
                'title' => 'Building a SaaS Platform: Case Study',
                'excerpt' => 'How we built a multi-tenant SaaS application with Laravel.',
                'content' => '<p>This case study covers building a complete SaaS platform with multi-tenancy, billing, and more.</p><h3>Multi-Tenancy Approach</h3><p>We chose database-per-tenant for complete data isolation.</p>',
                'categories' => ['Case Studies', 'Laravel'],
            ],
            [
                'slug' => 'livewire-lazy-loading',
                'title' => 'Lazy Loading Components in Livewire',
                'excerpt' => 'Improve performance with lazy-loaded Livewire components.',
                'content' => '<p>Lazy loading defers component rendering until they\'re visible, improving initial page load.</p><h3>Using Lazy</h3><p>Add the lazy attribute to defer component loading until it enters the viewport.</p>',
                'categories' => ['Tips & Tricks', 'Livewire'],
            ],
            [
                'slug' => 'filament-relationships',
                'title' => 'Managing Relationships in Filament',
                'excerpt' => 'Handle Eloquent relationships effectively in Filament resources.',
                'content' => '<p>Filament provides excellent support for managing model relationships. Here\'s how to use it.</p><h3>Relation Managers</h3><p>Use relation managers to manage hasMany and belongsToMany relationships.</p>',
                'categories' => ['Tutorials', 'Filament'],
            ],
            [
                'slug' => 'tailwind-components',
                'title' => 'Building Reusable Tailwind Components',
                'excerpt' => 'Create maintainable, reusable components with Tailwind.',
                'content' => '<p>Reusable components keep your code DRY. Here\'s how to build them with Tailwind and Blade.</p><h3>Blade Components</h3><p>Create Blade components that accept classes as props for maximum flexibility.</p>',
                'categories' => ['Tutorials', 'Tailwind CSS'],
            ],
            [
                'slug' => 'laravel-notifications',
                'title' => 'Laravel Notifications Deep Dive',
                'excerpt' => 'Send notifications via email, SMS, Slack, and more.',
                'content' => '<p>Laravel\'s notification system is incredibly versatile. Learn how to notify users across multiple channels.</p><h3>Creating Notifications</h3><pre><code>php artisan make:notification InvoicePaid</code></pre>',
                'categories' => ['Tutorials', 'Laravel'],
            ],
            [
                'slug' => 'livewire-wire-navigate',
                'title' => 'SPA-like Navigation with wire:navigate',
                'excerpt' => 'Create smooth page transitions with Livewire\'s wire:navigate.',
                'content' => '<p>wire:navigate enables SPA-like navigation without the complexity of a full SPA framework.</p><h3>Basic Usage</h3><p>Add wire:navigate to any anchor tag for smooth, JavaScript-powered navigation.</p>',
                'categories' => ['Tips & Tricks', 'Livewire'],
            ],
            [
                'slug' => 'filament-notifications',
                'title' => 'Filament Notifications System',
                'excerpt' => 'Display beautiful notifications in your Filament admin panel.',
                'content' => '<p>Filament\'s notification system provides feedback to users with customizable toasts and alerts.</p><h3>Sending Notifications</h3><p>Use Notification::make() to create and send notifications from anywhere in your application.</p>',
                'categories' => ['Tutorials', 'Filament'],
            ],
            [
                'slug' => 'tailwind-dark-mode',
                'title' => 'Implementing Dark Mode with Tailwind',
                'excerpt' => 'Add dark mode support using Tailwind\'s dark variant.',
                'content' => '<p>Dark mode is expected in modern applications. Tailwind makes it easy to implement.</p><h3>Configuration</h3><p>Enable dark mode in tailwind.config.js and use the dark: variant for dark mode styles.</p>',
                'categories' => ['Tutorials', 'Tailwind CSS'],
            ],
            [
                'slug' => 'laravel-scheduling',
                'title' => 'Task Scheduling in Laravel',
                'excerpt' => 'Automate tasks with Laravel\'s powerful scheduler.',
                'content' => '<p>Laravel\'s scheduler lets you define command schedules within Laravel itself. No need for multiple cron entries.</p><h3>Defining Schedules</h3><p>Define scheduled tasks in app/Console/Kernel.php using a fluent API.</p>',
                'categories' => ['Tutorials', 'Laravel'],
            ],
            [
                'slug' => 'portfolio-case-study',
                'title' => 'Building a Portfolio Site: Case Study',
                'excerpt' => 'Creating a stunning portfolio website with TallCMS.',
                'content' => '<p>This case study shows how we built a portfolio site for a creative agency using TallCMS blocks.</p><h3>The Approach</h3><p>We used the Gallery, Testimonials, and Portfolio blocks to showcase the agency\'s work.</p>',
                'categories' => ['Case Studies'],
            ],
            [
                'slug' => 'livewire-alpine-integration',
                'title' => 'Integrating Alpine.js with Livewire',
                'excerpt' => 'Combine Livewire and Alpine.js for powerful interactivity.',
                'content' => '<p>Livewire and Alpine.js work beautifully together. Here\'s how to integrate them effectively.</p><h3>Sharing State</h3><p>Use @entangle to sync Livewire properties with Alpine data.</p>',
                'categories' => ['Tutorials', 'Livewire'],
            ],
            [
                'slug' => 'filament-global-search',
                'title' => 'Implementing Global Search in Filament',
                'excerpt' => 'Add powerful global search to your Filament admin panel.',
                'content' => '<p>Filament\'s global search lets users find records across all resources quickly.</p><h3>Enabling Search</h3><p>Add the getGloballySearchableAttributes() method to your resources.</p>',
                'categories' => ['Tutorials', 'Filament'],
            ],
            [
                'slug' => 'tailwind-forms-styling',
                'title' => 'Styling Forms with Tailwind CSS',
                'excerpt' => 'Create beautiful, accessible forms using Tailwind.',
                'content' => '<p>Forms are essential to web applications. Here\'s how to style them beautifully with Tailwind.</p><h3>Form Plugin</h3><p>The @tailwindcss/forms plugin provides a better baseline for form elements.</p>',
                'categories' => ['Tutorials', 'Tailwind CSS'],
            ],
            [
                'slug' => 'laravel-caching-strategies',
                'title' => 'Caching Strategies for Laravel',
                'excerpt' => 'Implement effective caching to improve performance.',
                'content' => '<p>Caching is crucial for performance. Learn different caching strategies for Laravel applications.</p><h3>Cache Drivers</h3><p>Choose the right cache driver: file for development, Redis or Memcached for production.</p>',
                'categories' => ['Tips & Tricks', 'Laravel'],
            ],
            [
                'slug' => 'livewire-dispatch-events',
                'title' => 'Event Communication in Livewire',
                'excerpt' => 'Master component communication with Livewire events.',
                'content' => '<p>Events allow Livewire components to communicate with each other and with JavaScript.</p><h3>Dispatching Events</h3><p>Use $this->dispatch() to emit events that other components can listen for.</p>',
                'categories' => ['Tutorials', 'Livewire'],
            ],
            [
                'slug' => 'filament-custom-pages',
                'title' => 'Creating Custom Pages in Filament',
                'excerpt' => 'Build custom admin pages beyond CRUD resources.',
                'content' => '<p>Filament pages let you create custom admin interfaces for any purpose.</p><h3>Creating Pages</h3><pre><code>php artisan make:filament-page Settings</code></pre>',
                'categories' => ['Tutorials', 'Filament'],
            ],
            [
                'slug' => 'tallcms-2-0-announcement',
                'title' => 'Announcing TallCMS 2.0',
                'excerpt' => 'The next major version of TallCMS is here with exciting new features.',
                'content' => '<p>We\'re thrilled to announce TallCMS 2.0, our biggest release yet!</p><h3>What\'s New</h3><ul><li>New block editor</li><li>Improved performance</li><li>Better SEO tools</li><li>Enhanced theming</li></ul>',
                'categories' => ['News'],
                'featured' => true,
            ],
            [
                'slug' => 'tailwind-container-queries',
                'title' => 'Container Queries in Tailwind CSS',
                'excerpt' => 'Use container queries for component-based responsive design.',
                'content' => '<p>Container queries allow components to respond to their container\'s size, not the viewport.</p><h3>Enabling Container Queries</h3><p>Add @container to a parent element and use @lg:, @md:, etc. on children.</p>',
                'categories' => ['Tips & Tricks', 'Tailwind CSS'],
            ],
            [
                'slug' => 'laravel-api-resources',
                'title' => 'API Resources in Laravel',
                'excerpt' => 'Transform your data with Laravel API resources.',
                'content' => '<p>API resources provide a transformation layer between your models and JSON responses.</p><h3>Creating Resources</h3><pre><code>php artisan make:resource UserResource</code></pre>',
                'categories' => ['Tutorials', 'Laravel'],
            ],
            [
                'slug' => 'livewire-3-migration',
                'title' => 'Migrating from Livewire 2 to 3',
                'excerpt' => 'A complete guide to upgrading your Livewire components.',
                'content' => '<p>Livewire 3 brings significant changes. Here\'s how to migrate your existing components.</p><h3>Key Changes</h3><ul><li>New wire:model syntax</li><li>Alpine integration improvements</li><li>Performance enhancements</li></ul>',
                'categories' => ['Tutorials', 'Livewire'],
            ],
            [
                'slug' => 'filament-multi-tenancy',
                'title' => 'Multi-Tenancy with Filament',
                'excerpt' => 'Build multi-tenant applications using Filament panels.',
                'content' => '<p>Filament\'s panel system makes multi-tenancy straightforward. Here\'s how to implement it.</p><h3>Tenant Model</h3><p>Configure a tenant model and Filament will handle scoping automatically.</p>',
                'categories' => ['Tutorials', 'Filament'],
            ],
        ];
    }
}
