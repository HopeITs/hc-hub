<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum SessionDuration: int
{
    case Unknown = 0;

    case Hour = 1;

    case HourAndHalf = 2;

    case TwoHours = 3;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::Hour->value => ['text' => ['en' => 'Hour', 'ar' => 'ساعة'], 'color' => 'secondary-dark'],
            self::HourAndHalf->value => ['text' => ['en' => 'Hour and a Half', 'ar' => 'ساعة ونصف'], 'color' => 'secondary-medium'],
            self::TwoHours->value => ['text' => ['en' => 'Two Hours', 'ar' => 'ساعتان'], 'color' => 'secondary-medium'],
            default => ['text' => ['en' => 'Unknown', 'ar' => 'غير معروف'], 'color' => 'secondary-light'],
        };
        $result = [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];

        return $result;
    }
}
