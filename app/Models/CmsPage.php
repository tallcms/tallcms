<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CmsPage extends Model
{
    use SoftDeletes;
    
    protected $table = 'tallcms_pages';
    
    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'featured_image',
        'status',
        'published_at',
        'parent_id',
        'sort_order',
        'template',
    ];
    
    protected $casts = [
        'content' => 'array',
        'published_at' => 'datetime',
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
        
        static::updating(function ($page) {
            if ($page->isDirty('title') && empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }
    
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'parent_id');
    }
    
    public function children(): HasMany
    {
        return $this->hasMany(CmsPage::class, 'parent_id')->orderBy('sort_order');
    }
    
    
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now());
    }
    
    public function scopeWithSlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }
    
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
    
    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at?->isPast();
    }
}
