<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Gender extends Enum
{
    const Unknown = 0;
    const Male = 1;
    const Female = 2;

    public static function getLocalizedName(int $value): array
    {
        return match ($value) {
            self::Male => ['en' => 'Male', 'ar' => 'ذكر'],
            self::Female => ['en' => 'Female', 'ar' => 'أنثى'],
            default => ['en' => 'Unknown', 'ar' => 'غير معروف'],
        };
    }
}
