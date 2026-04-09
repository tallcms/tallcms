<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Tallcms\Multisite\Models\Site;
use Tallcms\Multisite\Services\CurrentSiteResolver;

class SiteSwitcher extends Component
{
    public string $search = '';

    public bool $isOpen = false;

    protected const MAX_RESULTS = 20;

    protected const MAX_RECENT = 5;

    /**
     * Base query filtered by ownership for non-super-admins.
     */
    protected function siteQuery(): Builder
    {
        $query = Site::where('is_active', true);

        if (auth()->check() && ! auth()->user()->hasRole('super_admin')) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    /**
     * Whether the current user can use "All Sites" mode.
     */
    #[Computed]
    public function canUseAllSites(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function switchSite(?int $siteId): void
    {
        $resolver = app(CurrentSiteResolver::class);
        $resolver->setAdminSite($siteId);

        // Track recent sites
        if ($siteId) {
            $recent = session('multisite_recent_sites', []);
            $recent = array_filter($recent, fn ($id) => $id !== $siteId);
            array_unshift($recent, $siteId);
            session(['multisite_recent_sites' => array_slice($recent, 0, self::MAX_RECENT)]);
        }

        // Notification
        $siteName = $siteId ? (Site::find($siteId)?->name ?? 'Unknown Site') : 'All Sites';
        Notification::make()
            ->title("Switched to {$siteName}")
            ->success()
            ->send();

        $this->isOpen = false;
        $this->search = '';

        $this->redirect(request()->header('Referer', url()->current()));
    }

    #[Computed]
    public function currentSite(): ?object
    {
        $sessionValue = session('multisite_admin_site_id');

        if (! $sessionValue || $sessionValue === CurrentSiteResolver::ALL_SITES_SENTINEL) {
            return null;
        }

        return Site::where('id', $sessionValue)->where('is_active', true)->first();
    }

    #[Computed]
    public function isAllSitesMode(): bool
    {
        return session('multisite_admin_site_id') === CurrentSiteResolver::ALL_SITES_SENTINEL;
    }

    #[Computed]
    public function recentSites(): Collection
    {
        $recentIds = session('multisite_recent_sites', []);

        if (empty($recentIds)) {
            return collect();
        }

        return $this->siteQuery()
            ->whereIn('id', $recentIds)
            ->get()
            ->sortBy(fn ($site) => array_search($site->id, $recentIds));
    }

    #[Computed]
    public function filteredSites(): Collection
    {
        $query = $this->siteQuery();

        if (strlen($this->search) > 0) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "{$search}%")
                    ->orWhere('domain', 'like', "{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%");
            });

            $query->orderByRaw('CASE
                WHEN name LIKE ? THEN 0
                WHEN domain LIKE ? THEN 1
                ELSE 2
            END', ["{$search}%", "{$search}%"]);
        }

        $query->orderBy('name');

        $currentId = $this->currentSite?->id;
        $recentIds = session('multisite_recent_sites', []);

        return $query->limit(self::MAX_RESULTS)->get()
            ->sortBy(function ($site) use ($currentId, $recentIds) {
                if ($site->id === $currentId) {
                    return 0;
                }
                $recentPos = array_search($site->id, $recentIds);

                return $recentPos !== false ? $recentPos + 1 : 100;
            });
    }

    #[Computed]
    public function totalSites(): int
    {
        return $this->siteQuery()->count();
    }

    public function render()
    {
        return view('tallcms-multisite::livewire.site-switcher');
    }
}
