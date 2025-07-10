<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Currency extends Enum
{
    const Unknown = 0;
    const SYP = 1;
    const USD = 2;
    const EUP = 3;

    public static function getLocalizedName(int $value): array
    {
        return match ($value) {
            self::SYP => ['en' => 'SYP', 'ar' => 'ليرة سورية'],
            self::USD => ['en' => 'USD', 'ar' => 'دولار أمريكي'],
            self::EUP => ['en' => 'EUP', 'ar' => 'يورو'],
            default => ['en' => 'Unknown', 'ar' => 'غير معروف'],
        };
    }
}
