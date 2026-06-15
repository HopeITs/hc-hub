<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Nationality extends Enum
{
    const Unknown = 0;

    const Syrian = 1;

    const Armenian = 2;

    const Lebanese = 3;

    const Palestinian = 4;

    public static function getLocalizedName(int $value): string
    {
        $value = match ($value) {
            self::Syrian => ['en' => 'Syrian', 'ar' => 'سوري'],
            self::Armenian => ['en' => 'Armenian', 'ar' => 'أرمني'],
            self::Lebanese => ['en' => 'Lebanese', 'ar' => 'لبناني'],
            self::Palestinian => ['en' => 'Palestinian', 'ar' => 'فلسطيني'],
            default => ['en' => 'Unknown', 'ar' => 'غير معروف'],
        };

        return $value[app()->getLocale()] ?? '';
    }
}
