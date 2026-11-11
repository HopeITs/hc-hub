<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

/**
 * The state of one step inside a workflow run.
 */
enum WorkflowStepStatus: int
{
    case Pending = 0;

    case Active = 1;

    case Approved = 2;

    case Rejected = 3;

    case Skipped = 4;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::Active->value => ['text' => ['en' => 'Active', 'ar' => 'نشطة'], 'color' => 'primary'],
            self::Approved->value => ['text' => ['en' => 'Approved', 'ar' => 'موافَق عليها'], 'color' => 'primary'],
            self::Rejected->value => ['text' => ['en' => 'Rejected', 'ar' => 'مرفوضة'], 'color' => 'secondary-medium'],
            self::Skipped->value => ['text' => ['en' => 'Skipped', 'ar' => 'متجاوَزة'], 'color' => 'secondary-light'],
            default => ['text' => ['en' => 'Pending', 'ar' => 'بانتظار الدور'], 'color' => 'secondary-light'],
        };

        return [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];
    }
}
