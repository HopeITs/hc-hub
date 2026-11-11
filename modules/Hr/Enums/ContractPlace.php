<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum ContractPlace: int
{
    case Unknown = 0;

    case Onsite = 1;

    case Online = 2;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::Onsite->value => ['text' => ['en' => 'Onsite', 'ar' => 'بالموقع'], 'color' => 'secondary-dark'],
            self::Online->value => ['text' => ['en' => 'Online', 'ar' => 'عن بعد'], 'color' => 'secondary-medium'],
            default => ['text' => ['en' => 'Unknown', 'ar' => 'غير معروف'], 'color' => 'secondary-medium'],
        };
        $result = [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];

        return $result;
    }
}
