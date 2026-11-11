<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment;

/**
 * The few text helpers the old HR app kept as global functions.
 */
final class Text
{
    public static function arDigits(mixed $value): string
    {
        return strtr((string) ($value ?? ''), [
            '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
            '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
        ]);
    }

    public static function enDigits(mixed $value): string
    {
        return strtr((string) ($value ?? ''), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    /** The column holding a translated field in the interface language (title_en / title_ar). */
    public static function transColumn(string $base, ?string $locale = null): string
    {
        return $base.'_'.($locale ?? app()->getLocale());
    }

    /** City-scoped HR roles keep their city code ("HR-ALP" becomes "HR · ALP"). */
    public static function roleLabel(?string $role): string
    {
        if ($role === null || $role === '') {
            return '';
        }

        return preg_match('/^HR-([A-Z]+)$/', $role, $m) === 1 ? 'HR · '.$m[1] : $role;
    }

    /**
     * @param  array<int, string>  $parts
     */
    public static function joinCounts(array $parts): string
    {
        $parts = array_values(array_filter($parts, fn (string $part): bool => $part !== ''));

        return implode(app()->getLocale() === 'ar' ? ' و' : ' ', $parts);
    }
}
