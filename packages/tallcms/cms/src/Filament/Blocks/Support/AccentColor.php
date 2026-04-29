<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Blocks\Support;

/**
 * Accent-color class helper used by content blocks.
 *
 * Each method returns a literal Tailwind class string for one variant of
 * the chosen daisyUI semantic token. All class strings appear as literals
 * in the match arms so Tailwind v4's PHP source scanner picks them up via
 * the @source ".../Filament/Blocks/**\/*.php" directive.
 *
 * Interpolation like "bg-{$token}/10" is intentionally avoided — the
 * scanner is regex-based and would not see the resulting class.
 */
class AccentColor
{
    public static function text(string $token = 'primary'): string
    {
        return match ($token) {
            'secondary' => 'text-secondary',
            'accent' => 'text-accent',
            'neutral' => 'text-neutral',
            'info' => 'text-info',
            'success' => 'text-success',
            'warning' => 'text-warning',
            'error' => 'text-error',
            default => 'text-primary',
        };
    }

    public static function text20(string $token = 'primary'): string
    {
        return match ($token) {
            'secondary' => 'text-secondary/20',
            'accent' => 'text-accent/20',
            'neutral' => 'text-neutral/20',
            'info' => 'text-info/20',
            'success' => 'text-success/20',
            'warning' => 'text-warning/20',
            'error' => 'text-error/20',
            default => 'text-primary/20',
        };
    }

    public static function tint5(string $token = 'primary'): string
    {
        return match ($token) {
            'secondary' => 'bg-secondary/5',
            'accent' => 'bg-accent/5',
            'neutral' => 'bg-neutral/5',
            'info' => 'bg-info/5',
            'success' => 'bg-success/5',
            'warning' => 'bg-warning/5',
            'error' => 'bg-error/5',
            default => 'bg-primary/5',
        };
    }

    public static function tint10(string $token = 'primary'): string
    {
        return match ($token) {
            'secondary' => 'bg-secondary/10',
            'accent' => 'bg-accent/10',
            'neutral' => 'bg-neutral/10',
            'info' => 'bg-info/10',
            'success' => 'bg-success/10',
            'warning' => 'bg-warning/10',
            'error' => 'bg-error/10',
            default => 'bg-primary/10',
        };
    }

    public static function fill(string $token = 'primary'): string
    {
        return match ($token) {
            'secondary' => 'bg-secondary text-secondary-content',
            'accent' => 'bg-accent text-accent-content',
            'neutral' => 'bg-neutral text-neutral-content',
            'info' => 'bg-info text-info-content',
            'success' => 'bg-success text-success-content',
            'warning' => 'bg-warning text-warning-content',
            'error' => 'bg-error text-error-content',
            default => 'bg-primary text-primary-content',
        };
    }

    public static function bg(string $token = 'primary'): string
    {
        return match ($token) {
            'secondary' => 'bg-secondary',
            'accent' => 'bg-accent',
            'neutral' => 'bg-neutral',
            'info' => 'bg-info',
            'success' => 'bg-success',
            'warning' => 'bg-warning',
            'error' => 'bg-error',
            default => 'bg-primary',
        };
    }

    public static function border(string $token = 'primary'): string
    {
        return match ($token) {
            'secondary' => 'border-secondary',
            'accent' => 'border-accent',
            'neutral' => 'border-neutral',
            'info' => 'border-info',
            'success' => 'border-success',
            'warning' => 'border-warning',
            'error' => 'border-error',
            default => 'border-primary',
        };
    }

    public static function shadow10(string $token = 'primary'): string
    {
        return match ($token) {
            'secondary' => 'shadow-secondary/10',
            'accent' => 'shadow-accent/10',
            'neutral' => 'shadow-neutral/10',
            'info' => 'shadow-info/10',
            'success' => 'shadow-success/10',
            'warning' => 'shadow-warning/10',
            'error' => 'shadow-error/10',
            default => 'shadow-primary/10',
        };
    }

    /**
     * Dispatch by variant key. Used by the @accent Blade directive.
     * Unknown variants fall through to text() (defensive against typos).
     */
    public static function resolve(string $variant, string $token = 'primary'): string
    {
        return match ($variant) {
            'text20' => self::text20($token),
            'tint5' => self::tint5($token),
            'tint10' => self::tint10($token),
            'fill' => self::fill($token),
            'bg' => self::bg($token),
            'border' => self::border($token),
            'shadow10' => self::shadow10($token),
            default => self::text($token),
        };
    }
}
