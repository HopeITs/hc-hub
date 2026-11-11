<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

/**
 * The lifecycle of a recruitment request.

 * Values are frozen: they are in the database and carry no ordering meaning,
 * so nothing may compare them with > or <. Draft = 0 is the default, the same
 * shape as LeaveRequestStatus::Pending->value.
 */
enum RecruitmentRequestStatus: int
{
    case Draft = 0;

    case Submitted = 1;

    case UnderReview = 2;

    case ChangesRequested = 3;

    case Resubmitted = 4;

    case Approved = 5;

    case Rejected = 6;

    case Cancelled = 7;

    case Applied = 8;

    case FailedApplication = 9;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::Submitted->value => ['text' => ['en' => 'Submitted', 'ar' => 'مُرسَل'], 'color' => 'secondary-medium'],
            self::UnderReview->value => ['text' => ['en' => 'Under review', 'ar' => 'قيد المراجعة'], 'color' => 'primary'],
            self::ChangesRequested->value => ['text' => ['en' => 'Changes requested', 'ar' => 'مطلوب تعديل'], 'color' => 'secondary-medium'],
            self::Resubmitted->value => ['text' => ['en' => 'Resubmitted', 'ar' => 'أُعيد الإرسال'], 'color' => 'secondary-medium'],
            self::Approved->value => ['text' => ['en' => 'Approved', 'ar' => 'موافَق عليه'], 'color' => 'primary'],
            self::Rejected->value => ['text' => ['en' => 'Rejected', 'ar' => 'مرفوض'], 'color' => 'secondary-medium'],
            self::Cancelled->value => ['text' => ['en' => 'Cancelled', 'ar' => 'ملغى'], 'color' => 'secondary-light'],
            self::Applied->value => ['text' => ['en' => 'Applied', 'ar' => 'مُطبَّق'], 'color' => 'primary'],
            self::FailedApplication->value => ['text' => ['en' => 'Application failed', 'ar' => 'فشل التطبيق'], 'color' => 'secondary-medium'],
            default => ['text' => ['en' => 'Draft', 'ar' => 'مسودة'], 'color' => 'secondary-light'],
        };

        return [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];
    }
}
