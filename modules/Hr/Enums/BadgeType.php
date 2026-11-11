<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum BadgeType: int
{
    case Unknown = 0;

    case Warning = 1;

    case Reward = 2;

    case Salary_Increase = 3;

    public static function getLocalizedName(int $value): string
    {
        $res = match ($value) {
            self::Warning->value => ['en' => 'Warning', 'ar' => 'إنذار'],
            self::Reward->value => ['en' => 'Reward', 'ar' => 'مكافأة'],
            self::Salary_Increase->value => ['en' => 'Salary Increase', 'ar' => 'زيادة راتب'],
            default => ['en' => 'Unknown', 'ar' => 'غير معروف'],
        };

        return $res[app()->getLocale()] ?? '';
    }
}
