<?php

namespace TallCms\Cms\Tests\Feature\Filament;

use PHPUnit\Framework\Attributes\DataProvider;
use TallCms\Cms\Tests\TestCase;

/**
 * Regression tests for missing ->disk(cms_media_disk()) on the featured_image
 * ImageColumn in CmsPagesTable and CmsPostsTable.
 *
 * Failure mode: Filament defaults ImageColumn to the 'public' disk when no disk
 * is specified. Plugin-mode installs with TALLCMS_MEDIA_DISK set to s3 or another
 * custom disk showed blank thumbnails in the pages and posts admin list.
 */
class ImageColumnDiskTest extends TestCase
{
    #[DataProvider('tableSourceFiles')]
    public function test_featured_image_column_calls_cms_media_disk(string $path): void
    {
        $source = file_get_contents($path);

        $this->assertStringContainsString(
            '->disk(cms_media_disk())',
            $source,
            basename($path).' must pass cms_media_disk() to ImageColumn::disk() so custom storage disks are honoured.',
        );
    }

    public static function tableSourceFiles(): array
    {
        $base = dirname(__DIR__, 3).'/src/Filament/Resources';

        return [
            'CmsPagesTable' => [$base.'/CmsPages/Tables/CmsPagesTable.php'],
            'CmsPostsTable' => [$base.'/CmsPosts/Tables/CmsPostsTable.php'],
        ];
    }

    public function test_explicit_tallcms_media_disk_config_is_returned(): void
    {
        // Covers plugin-mode installs with TALLCMS_MEDIA_DISK=do-spaces (or any
        // S3-compatible provider). Without the ->disk() call on ImageColumn, this
        // config would be ignored and thumbnails would always load from 'public'.
        config(['tallcms.media.disk' => 'do-spaces']);

        $this->assertSame('do-spaces', cms_media_disk());
    }

    public function test_s3_is_detected_from_bucket_config_when_no_disk_is_set(): void
    {
        // Covers IAM-role setups where S3_ACCESS_KEY_ID is not set but a bucket is
        // configured, so filesystems.default stays 'local' but S3 should be used.
        config([
            'tallcms.media.disk' => null,
            'filesystems.default' => 'local',
            'filesystems.disks.s3.bucket' => 'my-cms-bucket',
        ]);

        $this->assertSame('s3', cms_media_disk());
    }

    public function test_s3_is_detected_from_filesystems_default(): void
    {
        config([
            'tallcms.media.disk' => null,
            'filesystems.default' => 's3',
        ]);

        $this->assertSame('s3', cms_media_disk());
    }

    public function test_falls_back_to_public_when_no_cloud_storage_is_configured(): void
    {
        config([
            'tallcms.media.disk' => null,
            'filesystems.default' => 'local',
            'filesystems.disks.s3.bucket' => null,
        ]);

        $this->assertSame('public', cms_media_disk());
    }
}
