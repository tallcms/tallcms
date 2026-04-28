<?php

namespace TallCms\Cms\Tests\Unit;

use TallCms\Cms\Tests\TestCase;

class MediaDiskHelperTest extends TestCase
{
    public function test_media_disk_uses_explicit_package_config(): void
    {
        config(['tallcms.media.disk' => 'cms-media']);

        $this->assertSame('cms-media', cms_media_disk());
    }

    public function test_media_disk_falls_back_to_s3_detection_when_not_configured(): void
    {
        config([
            'tallcms.media.disk' => null,
            'filesystems.default' => 's3',
        ]);

        $this->assertSame('s3', cms_media_disk());
    }

    public function test_media_uses_s3_detects_custom_s3_driver(): void
    {
        config([
            'tallcms.media.disk' => 'cms-media',
            'filesystems.disks.cms-media' => [
                'driver' => 's3',
                'bucket' => 'cms-bucket',
            ],
        ]);

        $this->assertTrue(cms_uses_s3());
    }
}
