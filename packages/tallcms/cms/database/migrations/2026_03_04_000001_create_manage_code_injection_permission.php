<?php

use Illuminate\Database\Migrations\Migration;

/**
 * No-op migration (kept under its original filename so existing installs
 * don't try to re-run it).
 *
 * History: this migration originally created a `Manage:CodeInjection`
 * permission and assigned it to the `administrator` and `super_admin` roles.
 * That permission gated the standalone /admin/code-injection page.
 *
 * In v4.4 the standalone page was removed and embed code became a tab on
 * the Site edit page (Sites → {site} → Embed Code). Authorization now
 * follows the Site edit permission via SitePolicy — there is no longer a
 * dedicated permission for embed code.
 *
 * Behaviour:
 *   - Fresh installs: no-op. The `Manage:CodeInjection` permission is never
 *     created, so role pickers don't show a dead permission.
 *   - Upgraded installs that already ran the original migration: the
 *     orphan permission row stays in the DB (harmless). It can be safely
 *     deleted by hand if desired; not removing it here to avoid yanking
 *     a permission that operators may have customised assignments for.
 */
return new class extends Migration
{
    public function up(): void
    {
        // intentionally empty — see class doc above
    }

    public function down(): void
    {
        // intentionally empty
    }
};
