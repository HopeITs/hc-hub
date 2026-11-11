<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum Currency: int
{
    case Unknown = 0;

    case SYP = 1;

    case USD = 2;

    case EUR = 3;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        return match ($value) {
            self::SYP->value => ['en' => 'SYP', 'ar' => 'ليرة سورية'],
            self::USD->value => ['en' => 'USD', 'ar' => 'دولار أمريكي'],
            self::EUR->value => ['en' => 'EUR', 'ar' => 'يورو'],
            default => ['en' => 'Unknown', 'ar' => 'غير معروف'],
        };
    }
}
