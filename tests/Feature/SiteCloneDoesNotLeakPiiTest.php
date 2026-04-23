<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tallcms\Multisite\Models\Site;
use Tallcms\Multisite\Services\SiteCloneService;
use Tests\TestCase;

/**
 * Regression test for the template-clone PII leak.
 *
 * Pre-fix, SiteCloneService::cloneSettingOverrides copied every row from the
 * source site's tallcms_site_setting_overrides into the new site — including
 * contact_email, contact_phone, company_name, company_address, and every
 * social_* handle. When a SaaS user cloned a template, the template author's
 * personal identity carried over wholesale. The new site's Contact block
 * would email form submissions to the template author, and merge tags in
 * emails would render the template author's company details.
 *
 * The fix blocklists PII-sensitive keys in
 * SiteCloneService::DEFAULT_CLONE_EXCLUDE_SETTINGS, configurable via
 * `tallcms.multisite.template_clone_exclude_settings`.
 */
class SiteCloneDoesNotLeakPiiTest extends TestCase
{
    use RefreshDatabase;

    protected Site $source;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Site::class)) {
            $this->markTestSkipped('Multisite plugin not installed.');
        }

        $this->source = Site::create([
            'name' => 'Template Source',
            'domain' => 'template.test',
            'is_active' => true,
        ]);
    }

    public function test_pii_keys_are_not_copied_to_the_clone(): void
    {
        $this->setOverride($this->source->id, 'contact_email', 'template-owner@example.com');
        $this->setOverride($this->source->id, 'contact_phone', '+65 9999 0000');
        $this->setOverride($this->source->id, 'company_name', 'Template Corp');
        $this->setOverride($this->source->id, 'company_address', '1 Template Way');
        $this->setOverride($this->source->id, 'social_facebook', 'https://facebook.com/template-owner');
        $this->setOverride($this->source->id, 'social_linkedin', 'https://linkedin.com/in/template-owner');
        $this->setOverride($this->source->id, 'newsletter_signup_url', 'https://template-newsletter.com');
        $this->setOverride($this->source->id, 'logo', 'site-assets/template-logo.png');
        $this->setOverride($this->source->id, 'favicon', 'site-assets/template-favicon.ico');
        $this->setOverride($this->source->id, 'site_tagline', 'Template owner tagline');
        $this->setOverride($this->source->id, 'site_description', 'Template owner description');

        $cloned = app(SiteCloneService::class)->clone(
            $this->source,
            'Cloned Site',
            'cloned.test',
        );

        $piiKeys = [
            'contact_email', 'contact_phone', 'company_name', 'company_address',
            'social_facebook', 'social_twitter', 'social_linkedin', 'social_instagram',
            'social_youtube', 'social_tiktok', 'newsletter_signup_url',
            'logo', 'favicon', 'site_tagline', 'site_description',
        ];

        foreach ($piiKeys as $key) {
            $leaked = DB::table('tallcms_site_setting_overrides')
                ->where('site_id', $cloned->id)
                ->where('key', $key)
                ->exists();

            $this->assertFalse(
                $leaked,
                "PII key '{$key}' leaked from the source site into the clone. This is a privacy breach.",
            );
        }
    }

    public function test_non_pii_settings_are_still_copied(): void
    {
        $this->setOverride($this->source->id, 'theme_default_preset', 'coffee', 'text');
        $this->setOverride($this->source->id, 'show_powered_by', '1', 'boolean');
        $this->setOverride($this->source->id, 'site_type', 'multi-page', 'text');
        $this->setOverride($this->source->id, 'review_workflow_enabled', '1', 'boolean');

        $cloned = app(SiteCloneService::class)->clone(
            $this->source,
            'Cloned Site',
            'cloned.test',
        );

        $this->assertSame('coffee', DB::table('tallcms_site_setting_overrides')
            ->where('site_id', $cloned->id)
            ->where('key', 'theme_default_preset')
            ->value('value'));

        $this->assertSame('1', DB::table('tallcms_site_setting_overrides')
            ->where('site_id', $cloned->id)
            ->where('key', 'show_powered_by')
            ->value('value'));

        $this->assertSame('multi-page', DB::table('tallcms_site_setting_overrides')
            ->where('site_id', $cloned->id)
            ->where('key', 'site_type')
            ->value('value'));
    }

    public function test_exclude_list_is_configurable(): void
    {
        // Extend the default block list to also hide a custom key.
        config([
            'tallcms.multisite.template_clone_exclude_settings' => array_merge(
                \Tallcms\Multisite\Services\SiteCloneService::DEFAULT_CLONE_EXCLUDE_SETTINGS,
                ['custom_secret'],
            ),
        ]);

        $this->setOverride($this->source->id, 'custom_secret', 'do-not-leak');

        $cloned = app(SiteCloneService::class)->clone(
            $this->source,
            'Cloned Site',
            'cloned.test',
        );

        $leaked = DB::table('tallcms_site_setting_overrides')
            ->where('site_id', $cloned->id)
            ->where('key', 'custom_secret')
            ->exists();

        $this->assertFalse($leaked);
    }

    protected function setOverride(int $siteId, string $key, string $value, string $type = 'text'): void
    {
        DB::table('tallcms_site_setting_overrides')->insert([
            'site_id' => $siteId,
            'key' => $key,
            'value' => $value,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
