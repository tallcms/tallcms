<?php

declare(strict_types=1);

namespace TallCms\Cms\Validation;

class TokenAbilityValidator
{
    /**
     * Valid token abilities.
     *
     * Only these abilities are allowed when creating tokens.
     */
    public const VALID_ABILITIES = [
        'pages:read',
        'pages:write',
        'pages:delete',
        'posts:read',
        'posts:write',
        'posts:delete',
        'categories:read',
        'categories:write',
        'categories:delete',
        'media:read',
        'media:write',
        'media:delete',
        'webhooks:manage',
    ];

    /**
     * Validate that all provided abilities are valid.
     *
     * @param  array<string>  $abilities
     * @return array{valid: bool, invalid: array<string>}
     */
    public function validate(array $abilities): array
    {
        $invalid = array_diff($abilities, self::VALID_ABILITIES);

        return [
            'valid' => empty($invalid),
            'invalid' => array_values($invalid),
        ];
    }

    /**
     * Filter abilities to only include valid ones.
     *
     * @param  array<string>  $abilities
     * @return array<string>
     */
    public function filter(array $abilities): array
    {
        return array_values(array_intersect($abilities, self::VALID_ABILITIES));
    }

    /**
     * Get all valid abilities.
     *
     * @return array<string>
     */
    public function getValidAbilities(): array
    {
        return self::VALID_ABILITIES;
    }

    /**
     * Check if an ability is valid.
     */
    public function isValid(string $ability): bool
    {
        return in_array($ability, self::VALID_ABILITIES, true);
    }
}
