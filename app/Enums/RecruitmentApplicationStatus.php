<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * The lifecycle of a single applicant's submission to one campaign.
 *
 * Integer-backed to match every other Recruitment status enum
 * (RecruitmentRequestStatus, HiringCampaignStatus, ...) rather than the
 * string values this started as — the column itself was converted to match
 * in database/migrations/2026_09_23_000001_convert_recruitment_application_status_to_integer.php.
 * Submitted = 0 mirrors the same "default/first state = 0" convention used
 * throughout the module.
 */
final class RecruitmentApplicationStatus extends Enum
{
    const Submitted = 0;

    const UnderReview = 1;

    const Shortlisted = 2;

    const Rejected = 3;

    const InterviewScheduled = 4;

    const Interviewed = 5;

    const Selected = 6;

    const OfferSent = 7;

    const OfferAccepted = 8;

    const OfferRejected = 9;

    const Hired = 10;

    const Withdrawn = 11;

    const Archived = 12;

    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::UnderReview => ['text' => ['en' => 'Under review', 'ar' => 'قيد المراجعة'], 'color' => 'primary'],
            self::Shortlisted => ['text' => ['en' => 'Shortlisted', 'ar' => 'ضمن القائمة المختصرة'], 'color' => 'primary'],
            self::Rejected => ['text' => ['en' => 'Rejected', 'ar' => 'مرفوض'], 'color' => 'secondary-medium'],
            self::InterviewScheduled => ['text' => ['en' => 'Interview scheduled', 'ar' => 'مقابلة مجدولة'], 'color' => 'primary'],
            self::Interviewed => ['text' => ['en' => 'Interviewed', 'ar' => 'تمت المقابلة'], 'color' => 'primary'],
            self::Selected => ['text' => ['en' => 'Selected', 'ar' => 'مُختار'], 'color' => 'primary'],
            self::OfferSent => ['text' => ['en' => 'Offer sent', 'ar' => 'أُرسل العرض'], 'color' => 'primary'],
            self::OfferAccepted => ['text' => ['en' => 'Offer accepted', 'ar' => 'قُبل العرض'], 'color' => 'primary'],
            self::OfferRejected => ['text' => ['en' => 'Offer rejected', 'ar' => 'رُفض العرض'], 'color' => 'secondary-medium'],
            self::Hired => ['text' => ['en' => 'Hired', 'ar' => 'تم التوظيف'], 'color' => 'primary'],
            self::Withdrawn => ['text' => ['en' => 'Withdrawn', 'ar' => 'انسحب المتقدم'], 'color' => 'secondary-light'],
            self::Archived => ['text' => ['en' => 'Archived', 'ar' => 'مؤرشف'], 'color' => 'secondary-light'],
            default => ['text' => ['en' => 'Submitted', 'ar' => 'مُرسَل'], 'color' => 'secondary-medium'],
        };

        return [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];
    }
}
