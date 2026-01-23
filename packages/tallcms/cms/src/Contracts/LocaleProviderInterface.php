<?php

declare(strict_types=1);

namespace TallCms\Cms\Contracts;

interface LocaleProviderInterface
{
    /**
     * Return locales this provider offers.
     *
     * Keys should use normalized format (lowercase with underscores): es_mx
     * These keys must match lang/ folder names exactly.
     *
     * @return array<string, array{label: string, native?: string, rtl?: bool}>
     */
    public function getLocales(): array;

    /**
     * Path to lang/ directory with Laravel translation files.
     *
     * Folder names should use normalized format: lang/es/, lang/es_mx/
     * Files are loaded into the 'tallcms' namespace to override core translations.
     */
    public function getLangPath(): ?string;
}
