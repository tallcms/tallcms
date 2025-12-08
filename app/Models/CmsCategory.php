<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CmsCategory extends Model
{
    use SoftDeletes;
    
    protected $table = 'tallcms_categories';
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'parent_id',
        'sort_order',
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
        
        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
    
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CmsCategory::class, 'parent_id');
    }
    
    public function children(): HasMany
    {
        return $this->hasMany(CmsCategory::class, 'parent_id')->orderBy('sort_order');
    }
    
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(CmsPost::class, 'tallcms_post_category', 'category_id', 'post_id');
    }
    
    public function scopeWithSlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }
    
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
