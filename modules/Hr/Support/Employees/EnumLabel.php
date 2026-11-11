<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Employees;

use BackedEnum;
use ReflectionEnum;

/**
 * The display name of an HR enum value. HR's enums carry their own English
 * and Arabic names (getLocalizedName), in three shapes: a string, an
 * ['en' => ..., 'ar' => ...] pair, or ['value' => ..., 'color' => ...].
 */
final class EnumLabel
{
    /**
     * @param  class-string<BackedEnum>  $enum
     */
    public static function of(string $enum, mixed $value, string $empty = '—'): string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if ($value === null || $value === '') {
            return $empty;
        }

        $backing = (string) (new ReflectionEnum($enum))->getBackingType();
        $name = $enum::getLocalizedName($backing === 'int' ? (int) $value : (string) $value);

        if (is_array($name)) {
            $name = $name['value'] ?? $name[app()->getLocale()] ?? $name['en'] ?? '';
        }

        return (string) $name !== '' ? (string) $name : $empty;
    }

    /**
     * Every case but Unknown, as value => name.
     *
     * @param  class-string<BackedEnum>  $enum
     * @return array<int|string, string>
     */
    public static function options(string $enum, bool $withUnknown = false): array
    {
        $options = [];

        foreach ($enum::cases() as $case) {
            if (! $withUnknown && $case->name === 'Unknown') {
                continue;
            }

            $options[$case->value] = self::of($enum, $case->value);
        }

        return $options;
    }
}
