<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Filament\Resources\CmsPages\CmsPageResource;
use TallCms\Cms\Filament\Resources\Concerns\ScopesQueryToOwnedSites;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Tests\TestCase;

/**
 * Covers the tenant-isolation predicate that all site-owned Filament resources
 * use to filter their list queries. The trait is the single chokepoint for
 * CmsPage, TallcmsMenu, CmsPost, CmsCategory, MediaCollection, TallcmsMedia,
 * CmsComment, and TallcmsContactSubmission — testing it directly covers the
 * cross-resource invariant.
 *
 * Plus one integration-flavored case on CmsPageResource itself to catch
 * regressions where the resource forgets to delegate to the trait.
 */
class ScopesQueryToOwnedSitesTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('tallcms_sites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->timestamps();
        });

        // Multisite-shape table (has site_id).
        Schema::create('multisite_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->string('label');
            $table->timestamps();
        });

        // Standalone-shape table (only user_id).
        Schema::create('single_site_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('label');
            $table->timestamps();
        });

        // No tenancy column at all.
        Schema::create('global_records', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->timestamps();
        });

        // Real CmsPage table with site_id (mimics post-multisite-migration shape).
        Schema::create('tallcms_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->json('title');
            $table->json('slug');
            $table->json('content')->nullable();
            $table->text('search_content')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_homepage')->default(false);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Make tallcms_multisite_active() return true for tests that exercise
     * the multisite scoping path. The helper checks
     *   class_exists('Tallcms\Multisite\Scopes\SiteScope')
     *     && CmsPage::hasGlobalScope('Tallcms\Multisite\Scopes\SiteScope')
     * — we satisfy both by loading a fixture file that declares the class
     * at the expected namespace and registering it on CmsPage.
     */
    protected function activateFakeMultisite(): void
    {
        require_once __DIR__.'/../Fixtures/FakeMultisiteSiteScope.php';
        CmsPage::addGlobalScope(new \Tallcms\Multisite\Scopes\SiteScope);
    }

    protected function tearDown(): void
    {
        // Strip the fake scope so other tests in the suite don't inherit
        // multisite-active state via Eloquent's static scopes registry.
        if (class_exists(\Tallcms\Multisite\Scopes\SiteScope::class, false)) {
            $ref = new \ReflectionProperty(\Illuminate\Database\Eloquent\Model::class, 'globalScopes');
            $scopes = $ref->getValue();
            unset($scopes[CmsPage::class][\Tallcms\Multisite\Scopes\SiteScope::class]);
            $ref->setValue(null, $scopes);
        }

        parent::tearDown();
    }

    private function makeUser(string $name, bool $superAdmin = false): ScopingTestUser
    {
        $user = new ScopingTestUser;
        $user->name = $name;
        $user->email = "{$name}@test.local";
        $user->password = 'x';
        $user->isSuperAdmin = $superAdmin;
        $user->save();

        return $user;
    }

    public function test_unauthenticated_query_is_unfiltered(): void
    {
        DB::table('multisite_records')->insert([
            ['site_id' => 1, 'label' => 'a', 'created_at' => now(), 'updated_at' => now()],
            ['site_id' => 2, 'label' => 'b', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertCount(2, MultisiteScopedHost::scope(MultisiteRecord::query())->get());
    }

    public function test_super_admin_sees_every_record(): void
    {
        $this->activateFakeMultisite();
        $admin = $this->makeUser('admin', superAdmin: true);
        $this->actingAs($admin);

        DB::table('tallcms_sites')->insert([
            ['id' => 10, 'user_id' => 999, 'name' => 'Other'],
        ]);
        DB::table('multisite_records')->insert([
            ['site_id' => 10, 'label' => 'a', 'created_at' => now(), 'updated_at' => now()],
            ['site_id' => 11, 'label' => 'b', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertCount(2, MultisiteScopedHost::scope(MultisiteRecord::query())->get());
    }

    public function test_owner_only_sees_records_on_their_sites(): void
    {
        $this->activateFakeMultisite();
        $owner = $this->makeUser('owner');
        $other = $this->makeUser('other');
        $this->actingAs($owner);

        DB::table('tallcms_sites')->insert([
            ['id' => 10, 'user_id' => $owner->id, 'name' => 'Owner Site'],
            ['id' => 20, 'user_id' => $other->id, 'name' => 'Other Site'],
        ]);
        DB::table('multisite_records')->insert([
            ['site_id' => 10, 'label' => 'mine-1', 'created_at' => now(), 'updated_at' => now()],
            ['site_id' => 10, 'label' => 'mine-2', 'created_at' => now(), 'updated_at' => now()],
            ['site_id' => 20, 'label' => 'theirs', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $labels = MultisiteScopedHost::scope(MultisiteRecord::query())->pluck('label')->all();

        $this->assertEqualsCanonicalizing(['mine-1', 'mine-2'], $labels);
    }

    public function test_user_with_no_owned_sites_gets_zero_rows(): void
    {
        $this->activateFakeMultisite();
        $loner = $this->makeUser('loner');
        $this->actingAs($loner);

        DB::table('tallcms_sites')->insert([
            ['id' => 10, 'user_id' => 999, 'name' => 'Other'],
        ]);
        DB::table('multisite_records')->insert([
            ['site_id' => 10, 'label' => 'theirs', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertCount(0, MultisiteScopedHost::scope(MultisiteRecord::query())->get());
    }

    public function test_passes_through_when_site_id_column_exists_but_multisite_not_active(): void
    {
        // No activateFakeMultisite() call: multisite plugin is NOT booted.
        // This is the regression guard for the case the reviewer flagged —
        // a host with leftover site_id columns but multisite uninstalled
        // must not filter normal admins to zero rows.
        $user = $this->makeUser('alice');
        $this->actingAs($user);

        DB::table('tallcms_sites')->insert([
            ['id' => 10, 'user_id' => 999, 'name' => 'Other'],
        ]);
        DB::table('multisite_records')->insert([
            ['site_id' => 10, 'label' => 'x', 'created_at' => now(), 'updated_at' => now()],
            ['site_id' => 20, 'label' => 'y', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertCount(2, MultisiteScopedHost::scope(MultisiteRecord::query())->get());
    }

    public function test_scope_query_to_owned_sites_passes_through_when_site_id_absent(): void
    {
        $user = $this->makeUser('alice');
        $this->actingAs($user);

        DB::table('global_records')->insert([
            ['label' => 'a', 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'b', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // scopeQueryToOwnedSites is a no-op without site_id — single-site
        // installs of resources like CmsPage/Menu/Comment retain their
        // pre-multisite "all rows visible to permitted users" behavior.
        $this->assertCount(2, GlobalScopedHost::scope(GlobalRecord::query())->get());
    }

    public function test_owned_tenants_falls_back_to_user_id_when_site_id_absent(): void
    {
        $user = $this->makeUser('alice');
        $this->actingAs($user);

        DB::table('single_site_records')->insert([
            ['user_id' => $user->id, 'label' => 'mine', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 999, 'label' => 'theirs', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $labels = SingleSiteTenantHost::scope(SingleSiteRecord::query())->pluck('label')->all();

        $this->assertSame(['mine'], $labels);
    }

    public function test_owned_tenants_uses_site_ownership_when_site_id_present(): void
    {
        $this->activateFakeMultisite();
        $owner = $this->makeUser('owner');
        $other = $this->makeUser('other');
        $this->actingAs($owner);

        DB::table('tallcms_sites')->insert([
            ['id' => 10, 'user_id' => $owner->id, 'name' => 'Owner Site'],
            ['id' => 20, 'user_id' => $other->id, 'name' => 'Other Site'],
        ]);
        DB::table('multisite_records')->insert([
            ['site_id' => 10, 'label' => 'mine', 'created_at' => now(), 'updated_at' => now()],
            ['site_id' => 20, 'label' => 'theirs', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $labels = MultisiteTenantHost::scope(MultisiteRecord::query())->pluck('label')->all();

        $this->assertSame(['mine'], $labels);
    }

    public function test_owned_tenants_passes_through_when_neither_column_present(): void
    {
        $user = $this->makeUser('alice');
        $this->actingAs($user);

        DB::table('global_records')->insert([
            ['label' => 'a', 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'b', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertCount(2, GlobalTenantHost::scope(GlobalRecord::query())->get());
    }

    public function test_owned_tenants_falls_back_to_user_id_when_site_id_present_but_multisite_inactive(): void
    {
        // Same regression guard for the OwnedTenants helper: site_id column
        // exists but multisite plugin isn't booted → fall back to user_id.
        $user = $this->makeUser('alice');
        $this->actingAs($user);

        // multisite_records has site_id but no user_id, so the fallback
        // can't find user_id and should pass the query through.
        DB::table('multisite_records')->insert([
            ['site_id' => 10, 'label' => 'x', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertCount(1, MultisiteTenantHost::scope(MultisiteRecord::query())->get());
    }

    public function test_cms_page_resource_filters_to_owned_sites(): void
    {
        $this->activateFakeMultisite();
        $owner = $this->makeUser('owner');
        $other = $this->makeUser('other');
        $this->actingAs($owner);

        DB::table('tallcms_sites')->insert([
            ['id' => 10, 'user_id' => $owner->id, 'name' => 'Owner Site'],
            ['id' => 20, 'user_id' => $other->id, 'name' => 'Other Site'],
        ]);

        DB::table('tallcms_pages')->insert([
            [
                'site_id' => 10,
                'title' => json_encode(['en' => 'Mine']),
                'slug' => json_encode(['en' => 'mine']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'site_id' => 20,
                'title' => json_encode(['en' => 'Theirs']),
                'slug' => json_encode(['en' => 'theirs']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $rows = CmsPageResource::getEloquentQuery()->get();
        $this->assertCount(1, $rows);
        $this->assertSame(10, (int) $rows->first()->site_id);
    }
}

/**
 * Local fixture: avoids the host TestCase's Spatie HasRoles dependency by
 * defining its own hasRole() that the trait can probe via method_exists.
 */
class ScopingTestUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public bool $isSuperAdmin = false;

    public function hasRole($role): bool
    {
        return $this->isSuperAdmin && $role === 'super_admin';
    }
}

class MultisiteRecord extends Model
{
    protected $table = 'multisite_records';

    protected $guarded = [];

    public $timestamps = false;
}

class SingleSiteRecord extends Model
{
    protected $table = 'single_site_records';

    protected $guarded = [];

    public $timestamps = false;
}

class GlobalRecord extends Model
{
    protected $table = 'global_records';

    protected $guarded = [];

    public $timestamps = false;
}

class MultisiteScopedHost
{
    use ScopesQueryToOwnedSites;

    public static function scope(Builder $q): Builder
    {
        return static::scopeQueryToOwnedSites($q);
    }
}

class GlobalScopedHost
{
    use ScopesQueryToOwnedSites;

    public static function scope(Builder $q): Builder
    {
        return static::scopeQueryToOwnedSites($q);
    }
}

class MultisiteTenantHost
{
    use ScopesQueryToOwnedSites;

    public static function scope(Builder $q): Builder
    {
        return static::scopeQueryToOwnedTenants($q);
    }
}

class SingleSiteTenantHost
{
    use ScopesQueryToOwnedSites;

    public static function scope(Builder $q): Builder
    {
        return static::scopeQueryToOwnedTenants($q);
    }
}

class GlobalTenantHost
{
    use ScopesQueryToOwnedSites;

    public static function scope(Builder $q): Builder
    {
        return static::scopeQueryToOwnedTenants($q);
    }
}
