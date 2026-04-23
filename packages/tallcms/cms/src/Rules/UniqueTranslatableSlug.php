<?php

declare(strict_types=1);

namespace TallCms\Cms\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Validation rule for per-locale slug uniqueness.
 *
 * Since Spatie stores all locale values in a single JSON column,
 * traditional unique constraints can't enforce per-locale uniqueness.
 * This rule queries the JSON column for the specific locale.
 */
class UniqueTranslatableSlug implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * $siteId (optional): when the caller already knows the owning site — e.g.
     * the form passes `$record?->site_id` for an edit or `$livewire->ownerSiteId`
     * for a create — pass it here. This is authoritative and bypasses the
     * session/resolver fallback, which doesn't work for site_owners who
     * haven't used the site switcher. Without an explicit siteId, the rule
     * falls back to session/resolver resolution.
     */
    public function __construct(
        protected string $table,
        protected string $column,
        protected string $locale,
        protected ?int $ignoreId = null,
        protected ?int $siteId = null,
    ) {
        // Normalize locale to lowercase
        $this->locale = strtolower($this->locale);
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $driver = DB::getDriverName();

        // Build database-agnostic JSON query
        $query = DB::table($this->table);

        switch ($driver) {
            case 'sqlite':
                $query->whereRaw("JSON_EXTRACT({$this->column}, '$.{$this->locale}') = ?", [$value]);
                break;

            case 'pgsql':
                // PostgreSQL: cast to jsonb and use ->> for text extraction
                $query->whereRaw("{$this->column}::jsonb ->> ? = ?", [$this->locale, $value]);
                break;

            default:
                // MySQL/MariaDB
                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT({$this->column}, '$.\"".$this->locale."\"')) = ?", [$value]);
        }

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        // Exclude soft-deleted records
        if (\Illuminate\Support\Facades\Schema::hasColumn($this->table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        // Scope uniqueness based on ownership model:
        // - User-owned tables (posts, categories): scope by user_id
        // - Site-owned tables (pages): scope by site_id
        $this->applyScopeFilter($query);

        if ($query->exists()) {
            $fail("This slug is already used by another item in {$this->locale}.");
        }
    }

    /**
     * Apply the appropriate ownership filter based on the table type.
     *
     * User-owned tables (posts, categories): scope by user_id or auth user.
     * Site-owned tables (pages): scope by site_id via session/resolver.
     */
    protected function applyScopeFilter($query): void
    {
        // Check if table is user-owned (has user_id column)
        $isUserOwned = \Illuminate\Support\Facades\Schema::hasColumn($this->table, 'user_id');

        if ($isUserOwned && auth()->check()) {
            $ownerColumn = $this->table === 'tallcms_posts' ? 'user_id' : 'user_id';
            $query->where($ownerColumn, auth()->id());

            return;
        }

        // Site-owned: scope by site_id. Prefer an explicit siteId passed into
        // the rule — authoritative when the form knows which site the record
        // belongs to (existing record's site_id, or create form's ownerSiteId).
        $siteId = $this->siteId;

        if (! $siteId) {
            $sessionValue = session('multisite_admin_site_id');
            if ($sessionValue && $sessionValue !== '__all_sites__' && is_numeric($sessionValue)) {
                $siteId = (int) $sessionValue;
            }
        }

        if (! $siteId && app()->bound('tallcms.multisite.resolver')) {
            try {
                $resolver = app('tallcms.multisite.resolver');
                if ($resolver->isResolved() && $resolver->id()) {
                    $siteId = $resolver->id();
                }
            } catch (\Throwable) {
            }
        }

        if ($siteId && \Illuminate\Support\Facades\Schema::hasColumn($this->table, 'site_id')) {
            $query->where('site_id', $siteId);
        }
    }
}
