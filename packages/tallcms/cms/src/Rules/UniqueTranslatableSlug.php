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

        if ($query->exists()) {
            $fail("This slug is already used by another item in {$this->locale}.");
        }
    }
}
