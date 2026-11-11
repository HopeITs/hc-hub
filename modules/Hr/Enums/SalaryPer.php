<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum SalaryPer: int
{
    case Unknown = 0;

    case Month = 1;

    case Hour = 2;

    case Session = 3;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::Month->value => ['text' => ['en' => 'Month',     'ar' => 'شهر'],   'color' => 'secondary-dark'],
            self::Hour->value => ['text' => ['en' => 'Hour',      'ar' => 'ساعة'],   'color' => 'secondary-medium'],
            self::Session->value => ['text' => ['en' => 'Session', 'ar' => 'جلسة'],   'color' => 'secondary-medium'],
            default => ['text' => ['en' => 'Unknown',     'ar' => 'غير معروف'], 'color' => 'secondary-light'],
        };

        return [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];
    }
}
