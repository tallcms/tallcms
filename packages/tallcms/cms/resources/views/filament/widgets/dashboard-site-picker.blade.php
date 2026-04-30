<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Dashboard scope
        </x-slot>

        <x-slot name="description">
            Pick which site's data to show in the widgets below. This is separate from the site switcher in the navbar — that one takes you into a site's edit page.
        </x-slot>

        <select
            wire:model.live="selected"
            class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-base-content"
        >
            @if ($this->isSuperAdmin())
                <option value="__all_sites__">All Sites</option>
            @endif

            @foreach ($this->sitesForUser as $site)
                <option value="{{ $site->id }}">{{ $site->name }}</option>
            @endforeach
        </select>
    </x-filament::section>
</x-filament-widgets::widget>
