<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class BadgeType extends Enum
{
    const Unknown = 0;
    const Warning = 1;
    const Reward = 2;
    const Salary_Increase = 3;

    public static function getLocalizedName(int $value): array
    {
        return match ($value) {
            self::Warning => ['en' => 'Warning', 'ar' => 'إنذار'],
            self::Reward => ['en' => 'Reward', 'ar' => 'مكافأة'],
            self::Salary_Increase => ['en' => 'Salary_Increase', 'ar' => 'زيادة راتب'],
            default => ['en' => 'Unknown', 'ar' => 'غير معروف'],
        };
    }
}
