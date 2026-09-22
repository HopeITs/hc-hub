<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * The lifecycle of a single applicant's submission to one campaign.
 *
 * Values are frozen strings, matching the `status` column default
 * ('submitted') set in the recruitment_applications migration. Transitions
 * between them belong to a decision service, not to this class: an enum only
 * names the states, it never enforces which move to which.
 */
final class RecruitmentApplicationStatus extends Enum
{
    const Submitted = 'submitted';

    const UnderReview = 'under_review';

    const Shortlisted = 'shortlisted';

    const Rejected = 'rejected';

    const InterviewScheduled = 'interview_scheduled';

    const Interviewed = 'interviewed';

    const Selected = 'selected';

    const OfferSent = 'offer_sent';

    const OfferAccepted = 'offer_accepted';

    const OfferRejected = 'offer_rejected';

    const Hired = 'hired';

    const Withdrawn = 'withdrawn';

    const Archived = 'archived';

    public static function getLocalizedName(?string $value): array
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
