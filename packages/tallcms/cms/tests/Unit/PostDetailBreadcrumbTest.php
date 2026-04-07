<?php

namespace TallCms\Cms\Tests\Unit;

use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Tests\TestCase;

class PostDetailBreadcrumbTest extends TestCase
{
    public function test_parent_page_with_breadcrumbs_disabled_returns_false(): void
    {
        $page = new CmsPage;
        $page->is_homepage = false;
        $page->show_breadcrumbs = false;

        $this->assertFalse($page->shouldShowBreadcrumbs());
    }

    public function test_parent_page_with_breadcrumbs_enabled_returns_true(): void
    {
        $page = new CmsPage;
        $page->is_homepage = false;
        $page->show_breadcrumbs = true;

        $this->assertTrue($page->shouldShowBreadcrumbs());
    }

    public function test_parent_page_with_breadcrumbs_null_defaults_to_true(): void
    {
        $page = new CmsPage;
        $page->is_homepage = false;
        $page->show_breadcrumbs = null;

        $this->assertTrue($page->shouldShowBreadcrumbs());
    }

    public function test_homepage_parent_always_returns_false(): void
    {
        $page = new CmsPage;
        $page->is_homepage = true;
        $page->show_breadcrumbs = true;

        $this->assertFalse($page->shouldShowBreadcrumbs());
    }
}
