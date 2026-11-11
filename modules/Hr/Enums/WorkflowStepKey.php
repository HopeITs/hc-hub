<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

/**
 * Which step of the approval path this is.

 * There is no approver slot: every step has exactly one approver (D16).
 */
enum WorkflowStepKey: int
{
    case ManagerApproval = 1;

    case FinanceApproval = 2;

    case GovernorateHrApproval = 3;

    case HrSyriaDirectOpenApproval = 4;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::ManagerApproval->value => ['text' => ['en' => 'Manager approval', 'ar' => 'موافقة المدير'], 'color' => 'primary'],
            self::FinanceApproval->value => ['text' => ['en' => 'Financial approval', 'ar' => 'الموافقة المالية'], 'color' => 'primary'],
            self::GovernorateHrApproval->value => ['text' => ['en' => 'Governorate HR approval', 'ar' => 'موافقة HR المحافظة'], 'color' => 'primary'],
            self::HrSyriaDirectOpenApproval->value => ['text' => ['en' => 'HR Syria direct-open approval', 'ar' => 'موافقة HR سوريا على الفتح المباشر'], 'color' => 'primary'],
            default => ['text' => ['en' => 'Unknown', 'ar' => 'غير معروف'], 'color' => 'secondary-light'],
        };

        return [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];
    }
}
