<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum PayMode: int
{
    case Unknown = 0;

    case Fixed = 1;

    case Flexible = 2;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::Fixed->value => ['text' => ['en' => 'Fixed', 'ar' => 'ثابت'], 'color' => 'secondary-dark'],
            self::Flexible->value => ['text' => ['en' => 'Flexible', 'ar' => 'مرن'], 'color' => 'secondary-medium'],
            default => ['text' => ['en' => 'Unknown', 'ar' => 'غير معروف'], 'color' => 'secondary-light'],
        };
        $result = [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];

        return $result;
    }
}
