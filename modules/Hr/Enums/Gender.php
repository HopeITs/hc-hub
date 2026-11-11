<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum Gender: int
{
    case Unknown = 0;

    case Male = 1;

    case Female = 2;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        return match ($value) {
            self::Male->value => ['en' => 'Male', 'ar' => 'ذكر'],
            self::Female->value => ['en' => 'Female', 'ar' => 'أنثى'],
            default => ['en' => 'Unknown', 'ar' => 'غير معروف'],
        };
    }
}
