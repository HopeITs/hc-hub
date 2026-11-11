<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum MaritalStatus: int
{
    case Unknown = 0;

    case Single = 1;

    case Engaged = 2;

    case Married = 3;

    case Divorced = 4;

    case Widowed = 5;

    case Separated = 6;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::Single->value => [
                'text' => ['en' => 'Single', 'ar' => 'أعزب'],
                'color' => 'secondary-dark',
            ],
            self::Engaged->value => [
                'text' => ['en' => 'Engaged', 'ar' => 'خاطب'],
                'color' => 'secondary-medium',
            ],
            self::Married->value => [
                'text' => ['en' => 'Married', 'ar' => 'متزوج'],
                'color' => 'secondary-medium',
            ],
            self::Divorced->value => [
                'text' => ['en' => 'Divorced', 'ar' => 'مطلق'],
                'color' => 'secondary-medium',
            ],
            self::Widowed->value => [
                'text' => ['en' => 'Widowed', 'ar' => 'أرمل'],
                'color' => 'secondary-medium',
            ],
            self::Separated->value => [
                'text' => ['en' => 'Separated', 'ar' => 'منفصل'],
                'color' => 'secondary-medium',
            ],
            default => [
                'text' => ['en' => 'Unknown', 'ar' => 'غير معروف'],
                'color' => 'secondary-medium',
            ],
        };

        return [
            'value' => $val['text'][app()->getLocale()] ?? $val['text']['en'],
            'color' => $val['color'],
        ];
    }
}
