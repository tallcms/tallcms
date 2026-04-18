<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        // Reset multisite resolver state between tests to prevent
        // stale site context from leaking into subsequent test assertions.
        if (app()->bound('tallcms.multisite.resolver')) {
            app('tallcms.multisite.resolver')->reset();
        }

        parent::tearDown();
    }
}
