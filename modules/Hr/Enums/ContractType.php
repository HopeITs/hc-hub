<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum ContractType: int
{
    case Unknown = 0;

    case FullTime = 1;

    case PartTime = 2;

    case Freelance = 3;

    case Draft = 4;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::FullTime->value => ['text' => ['en' => 'Full Time', 'ar' => 'دوام كامل'], 'color' => 'secondary-dark'],
            self::PartTime->value => ['text' => ['en' => 'Part Time', 'ar' => 'دوام جزئي'], 'color' => 'secondary-medium'],
            self::Freelance->value => ['text' => ['en' => 'Freelance', 'ar' => 'دوام حر'], 'color' => 'secondary-medium'],
            self::Draft->value => ['text' => ['en' => 'Draft', 'ar' => 'مسودة'], 'color' => 'secondary-light'],
            default => ['text' => ['en' => 'Unknown', 'ar' => 'غير معروف'], 'color' => 'secondary-light'],
        };
        $result = [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];

        return $result;
    }
}
