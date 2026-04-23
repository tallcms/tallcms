<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use TallCms\Cms\Rules\UniqueTranslatableSlug;
use Tallcms\Multisite\Models\Site;
use Tests\TestCase;

/**
 * Regression test for cross-site slug uniqueness collision.
 *
 * When a site_owner cloned a template, the cloned homepage would carry the
 * same slug ("home", etc.) as the original template's homepage on another
 * site. Saving the clone triggered UniqueTranslatableSlug, which scoped
 * uniqueness via session/resolver — both empty for a site_owner who hadn't
 * explicitly switched admin site — so the check fell back to a GLOBAL
 * uniqueness query and found the template's original, blocking the save
 * with "This slug is already used by another item in en."
 *
 * The rule now accepts an explicit siteId (passed by the form from the
 * record's site_id) and uses it as the authoritative scope.
 */
class UniqueTranslatableSlugSiteScopingTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerA;

    protected User $ownerB;

    protected Site $siteA;

    protected Site $siteB;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Site::class)) {
            $this->markTestSkipped('Multisite plugin not installed.');
        }

        Role::firstOrCreate(['name' => 'site_owner', 'guard_name' => 'web']);

        $this->ownerA = User::factory()->create();
        $this->ownerA->assignRole('site_owner');
        $this->ownerB = User::factory()->create();
        $this->ownerB->assignRole('site_owner');

        $this->siteA = Site::create([
            'name' => 'Site A',
            'domain' => 'site-a.test',
            'is_active' => true,
            'user_id' => $this->ownerA->id,
        ]);
        $this->siteB = Site::create([
            'name' => 'Site B',
            'domain' => 'site-b.test',
            'is_active' => true,
            'user_id' => $this->ownerB->id,
        ]);
    }

    public function test_same_slug_on_different_sites_is_allowed(): void
    {
        // Existing page with slug "home" on Site A (like a template original)
        $this->insertPage($this->siteA->id, 'home');

        $this->actingAs($this->ownerB);

        // Validate a new page with slug "home" on Site B — should pass because
        // we're checking uniqueness within Site B only.
        $rule = new UniqueTranslatableSlug(
            table: 'tallcms_pages',
            column: 'slug',
            locale: 'en',
            ignoreId: null,
            siteId: $this->siteB->id,
        );

        $errors = $this->runRule($rule, 'home');

        $this->assertSame([], $errors, 'Site B should allow slug "home" even when Site A already has it.');
    }

    public function test_same_slug_on_same_site_is_rejected(): void
    {
        $this->insertPage($this->siteA->id, 'home');

        $this->actingAs($this->ownerA);

        // Validate another "home" on the SAME site — should fail.
        $rule = new UniqueTranslatableSlug(
            table: 'tallcms_pages',
            column: 'slug',
            locale: 'en',
            ignoreId: null,
            siteId: $this->siteA->id,
        );

        $errors = $this->runRule($rule, 'home');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('already used', $errors[0]);
    }

    public function test_ignore_id_excludes_the_edited_record_on_same_site(): void
    {
        $pageId = $this->insertPage($this->siteA->id, 'home');

        $this->actingAs($this->ownerA);

        // Re-validate the same page with its own slug — ignore_id should skip it.
        $rule = new UniqueTranslatableSlug(
            table: 'tallcms_pages',
            column: 'slug',
            locale: 'en',
            ignoreId: $pageId,
            siteId: $this->siteA->id,
        );

        $errors = $this->runRule($rule, 'home');

        $this->assertSame([], $errors);
    }

    protected function insertPage(int $siteId, string $slug): int
    {
        return DB::table('tallcms_pages')->insertGetId([
            'site_id' => $siteId,
            'title' => json_encode(['en' => ucfirst($slug)]),
            'slug' => json_encode(['en' => $slug]),
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function runRule(UniqueTranslatableSlug $rule, string $value): array
    {
        $errors = [];
        $rule->validate('slug', $value, function (string $message) use (&$errors) {
            $errors[] = $message;
        });

        return $errors;
    }
}
