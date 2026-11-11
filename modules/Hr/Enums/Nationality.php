<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum Nationality: int
{
    case Unknown = 0;

    case Syrian = 1;

    case Armenian = 2;

    case Lebanese = 3;

    case Palestinian = 4;

    public static function getLocalizedName(int $value): string
    {
        $value = match ($value) {
            self::Syrian->value => ['en' => 'Syrian', 'ar' => 'سوري'],
            self::Armenian->value => ['en' => 'Armenian', 'ar' => 'أرمني'],
            self::Lebanese->value => ['en' => 'Lebanese', 'ar' => 'لبناني'],
            self::Palestinian->value => ['en' => 'Palestinian', 'ar' => 'فلسطيني'],
            default => ['en' => 'Unknown', 'ar' => 'غير معروف'],
        };

        return $value[app()->getLocale()] ?? '';
    }
}
