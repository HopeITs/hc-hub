<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class MaritalStatusEnum extends Enum
{
    const Unknown = 0;
    const Single = 1;
    const Married = 2;

    public static function getLocalizedName(int $value): array
    {
        return match ($value) {
            self::Single => ['en' => 'Single', 'ar' => 'أعزب'],
            self::Married => ['en' => 'Married', 'ar' => 'متزوج'],
            default => ['en' => 'Unknown', 'ar' => 'غير معروف'],
        };
    }
}
