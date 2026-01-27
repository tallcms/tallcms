<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use TallCms\Cms\Models\CmsPost;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'email_verified_at',
        'slug',
        'bio',
        'twitter_handle',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'app_authentication_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'app_authentication_secret' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug on create (if not provided)
        static::creating(function ($user) {
            if (empty($user->slug)) {
                $slug = Str::slug($user->name);
                if (! empty($slug)) {
                    $user->slug = static::generateUniqueSlug($slug);
                }
                // Leave slug NULL if empty - will be set in created event
                // This avoids unique constraint violations with concurrent creates
            }
        });

        // Set ID-based slug if still null (empty/non-Latin names)
        static::created(function ($user) {
            if (empty($user->slug)) {
                $user->slug = static::generateUniqueSlug("user-{$user->getKey()}");
                $user->saveQuietly();
            }
        });
    }

    /**
     * Generate a unique slug with collision handling.
     */
    public static function generateUniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get the posts authored by this user.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(CmsPost::class, 'author_id');
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        // Check if user is active
        if (! ($this->is_active ?? true)) {
            return false;
        }

        // First-time setup: Allow access if this is the first user created
        if ($this->isFirstUser()) {
            return true;
        }

        // Allow access if user has any role (Shield will handle specific permissions)
        return $this->hasAnyRole(['super_admin', 'administrator', 'editor', 'author']);
    }

    /**
     * Check if this is the first user in the system
     */
    public function isFirstUser(): bool
    {
        return static::count() === 1 && $this->id === static::first()?->id;
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }
}
