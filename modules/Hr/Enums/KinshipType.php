<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum KinshipType: int
{
    case Other = 0;

    case Parents = 1;

    case Spouse = 2;

    case Siblings = 3;

    case Children = 4;

    public static function getLocalizedName(int $value): string
    {
        $res = match ($value) {
            self::Parents->value => ['en' => 'Parents', 'ar' => 'الوالدان'],
            self::Spouse->value => ['en' => 'Spouse', 'ar' => 'الزوج/الزوجة'],
            self::Siblings->value => ['en' => 'Siblings', 'ar' => 'الإخوة'],
            self::Children->value => ['en' => 'Children', 'ar' => 'الأولاد'],
            default => ['en' => 'Other', 'ar' => 'آخر'],
        };

        return $res[app()->getLocale()] ?? '';
    }
}
