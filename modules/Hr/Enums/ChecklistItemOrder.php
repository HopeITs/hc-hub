<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

enum ChecklistItemOrder: int
{
    case Unknown = 0;

    case ProbationContract = 1;

    case AnnualContract = 2;

    case Resignation = 3;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {

        $val = match ($value) {
            self::ProbationContract->value => ['text' => ['en' => 'Probation Contract', 'ar' => 'عقد تجريبي'], 'color' => 'secondary-dark'],
            self::AnnualContract->value => ['text' => ['en' => 'Annual Contract', 'ar' => 'عقد سنوي (بعد التثبيت)'], 'color' => 'secondary-medium'],
            self::Resignation->value => ['text' => ['en' => 'Resignation / End of Service', 'ar' => 'استقالة / إنهاء خدمة'], 'color' => 'primary'],
            default => ['text' => ['en' => 'Unknown', 'ar' => 'غير معروف'], 'color' => 'secondary-light'],
        };
        $result = [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];

        return $result;
    }
}
