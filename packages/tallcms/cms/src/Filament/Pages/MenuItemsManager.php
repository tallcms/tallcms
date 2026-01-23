<?php

namespace TallCms\Cms\Filament\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\SelectAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\TallcmsMenu;
use TallCms\Cms\Models\TallcmsMenuItem;
use TallCms\Cms\Services\LocaleRegistry;
use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class MenuItemsManager extends NestedsetPage
{
    protected static ?string $model = TallcmsMenuItem::class;

    protected static string $recordTitleAttribute = 'label';

    protected static ?string $tabFieldName = 'menu_id';

    protected static ?int $level = 5; // Allow up to 5 levels of nesting

    protected static bool $shouldRegisterNavigation = false; // Hide from navigation

    /**
     * Active locale for translations
     */
    #[Url]
    public ?string $activeLocale = null;

    public function mount(): void
    {
        parent::mount();

        // Validate and set active locale
        $this->activeLocale = $this->validateLocale($this->activeLocale);
    }

    /**
     * Validate locale code, returning default if invalid
     */
    protected function validateLocale(?string $locale): string
    {
        $default = $this->getDefaultTranslatableLocale();

        if ($locale === null) {
            return $default;
        }

        // Check if locale is in the list of valid locales
        if (in_array($locale, $this->getTranslatableLocales(), true)) {
            return $locale;
        }

        return $default;
    }

    /**
     * Get available locales for translation
     */
    public function getTranslatableLocales(): array
    {
        if (! tallcms_i18n_enabled()) {
            return [config('app.locale', 'en')];
        }

        return app(LocaleRegistry::class)->getLocaleCodes();
    }

    /**
     * Get the default translatable locale
     */
    public function getDefaultTranslatableLocale(): string
    {
        if (! tallcms_i18n_enabled()) {
            return config('app.locale', 'en');
        }

        return app(LocaleRegistry::class)->getDefaultLocale();
    }

    /**
     * Get locale label for display
     */
    public function getLocaleLabel(string $locale): string
    {
        if (! tallcms_i18n_enabled()) {
            return $locale;
        }

        $registry = app(LocaleRegistry::class);
        $locales = $registry->getLocales();

        if ($locales->has($locale)) {
            $config = $locales->get($locale);
            return $config['native'] ?? $config['label'] ?? $locale;
        }

        return $locale;
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Add locale switcher if i18n is enabled
        if (tallcms_i18n_enabled()) {
            $actions[] = $this->getLocaleSwitcherAction();
        }

        // Add default actions from parent
        $actions[] = $this->createAction();
        $actions[] = $this->fixTreeAction();

        return $actions;
    }

    /**
     * Custom locale switcher action for NestedsetPage
     */
    protected function getLocaleSwitcherAction(): SelectAction
    {
        $options = [];
        foreach ($this->getTranslatableLocales() as $locale) {
            $options[$locale] = $this->getLocaleLabel($locale);
        }

        return SelectAction::make('activeLocale')
            ->label(__('Language'))
            ->options($options)
            ->action(fn (string $value) => $this->switchLocale($value));
    }

    /**
     * Switch to a different locale (with validation)
     */
    public function switchLocale(string $locale): void
    {
        $this->activeLocale = $this->validateLocale($locale);
        $this->dispatch('filament-nestedset-updated');
    }

    public function getTabs(): array
    {
        $menus = TallcmsMenu::all();
        $tabs = [];

        foreach ($menus as $menu) {
            $tabs[$menu->id] = Tab::make()
                ->label($menu->name.' ('.$menu->allItems()->count().')');
        }

        return $tabs;
    }

    /**
     * Override createAction to handle translations
     */
    public function createAction(): CreateAction
    {
        return parent::createAction()
            ->mutateFormDataUsing(function (array $data): array {
                // Store label as translation for current locale
                if (isset($data['label']) && tallcms_i18n_enabled()) {
                    $data['_translatable_label'] = $data['label'];
                    unset($data['label']);
                }

                return $data;
            })
            ->using(function (array $data, string $model): Model {
                $translatableLabel = $data['_translatable_label'] ?? null;
                unset($data['_translatable_label']);

                $record = new $model($data);

                if ($translatableLabel !== null && tallcms_i18n_enabled()) {
                    $activeLocale = $this->validateLocale($this->activeLocale);
                    $defaultLocale = $this->getDefaultTranslatableLocale();

                    // Always set the active locale translation
                    $record->setTranslation('label', $activeLocale, $translatableLabel);

                    // Also set default locale if different (ensures menu never renders blank)
                    if ($activeLocale !== $defaultLocale) {
                        $record->setTranslation('label', $defaultLocale, $translatableLabel);
                    }
                } else {
                    $record->label = $translatableLabel ?? $data['label'] ?? '';
                }

                $record->save();

                return $record;
            });
    }

    /**
     * Override editAction to handle translations
     */
    public function editAction(): EditAction
    {
        return parent::editAction()
            ->fillForm(function (Model $record): array {
                $data = $record->toArray();

                // Get label for current locale (with fallback to default)
                if (tallcms_i18n_enabled()) {
                    $activeLocale = $this->validateLocale($this->activeLocale);
                    $data['label'] = $record->getTranslation('label', $activeLocale, false)
                        ?? $record->getTranslation('label', $this->getDefaultTranslatableLocale(), false)
                        ?? '';
                }

                return $data;
            })
            ->mutateFormDataUsing(function (array $data): array {
                if (isset($data['label']) && tallcms_i18n_enabled()) {
                    $data['_translatable_label'] = $data['label'];
                    unset($data['label']);
                }

                return $data;
            })
            ->using(function (Model $record, array $data): Model {
                $translatableLabel = $data['_translatable_label'] ?? null;
                unset($data['_translatable_label']);

                $record->fill($data);

                if ($translatableLabel !== null && tallcms_i18n_enabled()) {
                    $activeLocale = $this->validateLocale($this->activeLocale);
                    $record->setTranslation('label', $activeLocale, $translatableLabel);
                } elseif ($translatableLabel !== null) {
                    $record->label = $translatableLabel;
                }

                $record->save();

                return $record;
            });
    }

    protected function schema(array $arguments): array
    {
        return [
            Hidden::make('menu_id')
                ->default(function () use ($arguments) {
                    return $arguments['tab'] ?? request()->get('activeTab');
                }),

            TextInput::make('label')
                ->label(fn () => tallcms_i18n_enabled()
                    ? 'Menu Label ('.$this->getLocaleLabel($this->activeLocale ?? $this->getDefaultTranslatableLocale()).')'
                    : 'Menu Label')
                ->required()
                ->maxLength(255)
                ->placeholder('Home')
                ->helperText(fn () => tallcms_i18n_enabled()
                    ? 'Switch language using the button in the header to translate this label.'
                    : null),

            Select::make('type')
                ->label('Link Type')
                ->options([
                    'page' => 'Page',
                    'external' => 'External URL',
                    'custom' => 'Custom URL',
                    'header' => 'Header',
                    'separator' => 'Separator',
                ])
                ->required()
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('page_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('url', null)),

            Select::make('page_id')
                ->label('Select Page')
                ->options(CmsPage::where('status', 'published')->pluck('title', 'id'))
                ->searchable()
                ->required()
                ->visible(fn (Get $get): bool => $get('type') === 'page'),

            TextInput::make('url')
                ->label('URL')
                ->required()
                ->placeholder('https://example.com or /contact')
                ->visible(fn (Get $get): bool => in_array($get('type'), ['external', 'custom'])),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ];
    }
}
