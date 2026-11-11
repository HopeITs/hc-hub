<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

/**
 * What a recruitment request asks for.

 * Unknown = 0 is reserved and never stored; it exists so that an unset column
 * cannot pass for a real request type.
 */
enum RecruitmentRequestType: int
{
    case Unknown = 0;

    case FillVacancy = 1;

    case IncreaseHeadcount = 2;

    case DecreaseHeadcount = 3;

    case CreatePosition = 4;

    case ClosePosition = 5;

    case CloseVacancy = 6;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::FillVacancy->value => ['text' => ['en' => 'Fill vacancy', 'ar' => 'ملء شاغر'], 'color' => 'primary'],
            self::IncreaseHeadcount->value => ['text' => ['en' => 'Increase headcount', 'ar' => 'زيادة الملاك'], 'color' => 'primary'],
            self::DecreaseHeadcount->value => ['text' => ['en' => 'Reduce approved headcount', 'ar' => 'تخفيض الملاك المعتمد'], 'color' => 'secondary-medium'],
            self::CreatePosition->value => ['text' => ['en' => 'Create position', 'ar' => 'إنشاء منصب'], 'color' => 'primary'],
            self::ClosePosition->value => ['text' => ['en' => 'Close position', 'ar' => 'إغلاق منصب'], 'color' => 'secondary-medium'],
            self::CloseVacancy->value => ['text' => ['en' => 'Close vacancy', 'ar' => 'إغلاق شاغر'], 'color' => 'secondary-medium'],
            default => ['text' => ['en' => 'Unknown', 'ar' => 'غير محدد'], 'color' => 'secondary-light'],
        };

        return [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];
    }
}
