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
     */
    public function __construct(
        protected string $table,
        protected string $column,
        protected string $locale,
        protected ?int $ignoreId = null
    ) {
        // Normalize locale to lowercase
        $this->locale = strtolower($this->locale);
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
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
                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT({$this->column}, '$.\"" . $this->locale . "\"')) = ?", [$value]);
        }

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        // Scope uniqueness to current site when multisite is active.
        // Uses the same context-aware pattern as SiteSetting::resolveCurrentSiteId():
        // admin context reads session, frontend reads resolver.
        $siteId = null;
        $isAdminContext = request()?->attributes->get('tallcms.admin_context', false);

        if ($isAdminContext) {
            $sessionValue = session('multisite_admin_site_id');
            if ($sessionValue && $sessionValue !== '__all_sites__' && is_numeric($sessionValue)) {
                $siteId = (int) $sessionValue;
            }
        } elseif (app()->bound('tallcms.multisite.resolver')) {
            try {
                $resolver = app('tallcms.multisite.resolver');
                if ($resolver->isResolved() && $resolver->id()) {
                    $siteId = $resolver->id();
                }
            } catch (\Throwable) {
                // Multisite not functional
            }
        }

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        if ($query->exists()) {
            $fail("This slug is already used by another item in {$this->locale}.");
        }
    }
}
