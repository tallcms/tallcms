<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kalnoy\Nestedset\NodeTrait;
use TallCms\Cms\Models\Concerns\HasTranslatableContent;

class TallcmsMenuItem extends Model
{
    use HasTranslatableContent;
    use NodeTrait;

    protected $table = 'tallcms_menu_items';

    /**
     * Translatable attributes for Spatie Laravel Translatable.
     *
     * @var array<string>
     */
    public array $translatable = [
        'label',
    ];

    protected $fillable = [
        'menu_id',
        'label',
        'type',
        'page_id',
        'url',
        'meta',
        'is_active',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(TallcmsMenu::class, 'menu_id');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'page_id');
    }

    public function getResolvedUrl(): ?string
    {
        return app('menu.url.resolver')->resolve($this);
    }

    public function getIconAttribute(): ?string
    {
        return $this->meta['icon'] ?? null;
    }

    public function getCssClassAttribute(): ?string
    {
        return $this->meta['css_class'] ?? null;
    }

    public function getOpenInNewTabAttribute(): bool
    {
        return $this->meta['open_in_new_tab'] ?? false;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Define scope attributes for nested set operations
    public function getScopeAttributes()
    {
        return ['menu_id'];
    }
}
