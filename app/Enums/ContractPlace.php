<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ContractPlace extends Enum
{
    const Unknown = 0;
    const Onsite = 1;
    const Online = 2;

    public static function getLocalizedName(int $value): array
    {
        return match ($value) {
            self::Onsite => ['en' => 'Onsite', 'ar' => 'بالموقع'],
            self::Online => ['en' => 'Online', 'ar' => 'عن بعد'],
            default => ['en' => 'Unknown', 'ar' => 'غير معروف'],
        };
    }
}
