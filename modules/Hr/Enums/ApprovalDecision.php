<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

/**
 * What an approver decided on a step.
 */
enum ApprovalDecision: int
{
    case Approve = 1;

    case Reject = 2;

    case RequestChanges = 3;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::Approve->value => ['text' => ['en' => 'Approved', 'ar' => 'موافقة'], 'color' => 'primary'],
            self::Reject->value => ['text' => ['en' => 'Rejected', 'ar' => 'رفض'], 'color' => 'secondary-medium'],
            self::RequestChanges->value => ['text' => ['en' => 'Changes requested', 'ar' => 'طلب تعديل'], 'color' => 'secondary-medium'],
            default => ['text' => ['en' => 'Unknown', 'ar' => 'غير معروف'], 'color' => 'secondary-light'],
        };

        return [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];
    }
}
