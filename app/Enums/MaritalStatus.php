<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class MaritalStatus extends Enum
{
    const Unknown = 0;
    const Single = 1;
    const Engaged = 2;
    const Married = 3;

    const Divorced = 4;
    const Widowed = 5;
    const Separated = 6;

    public static function getLocalizedName(int $value): array
    {
        return match ($value) {
            self::Single => ['en' => 'Single', 'ar' => 'أعزب'],
            self::Engaged => ['en' => 'Engaged', 'ar' => 'خاطب'],
            self::Married => ['en' => 'Married', 'ar' => 'متزوج'],
            self::Divorced => ['en' => 'Divorced', 'ar' => 'مطلق'],
            self::Widowed => ['en' => 'Widowed', 'ar' => 'أرمل'],
            self::Separated => ['en' => 'Separated', 'ar' => 'منفصل'],
            default => ['en' => 'Unknown', 'ar' => 'غير معروف'],
        };
    }
}
