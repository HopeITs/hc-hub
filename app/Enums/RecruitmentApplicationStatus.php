<?php

namespace App\Enums;

final class RecruitmentApplicationStatus
{
    public const Submitted = 'submitted';

    public const UnderReview = 'under_review';

    public const Shortlisted = 'shortlisted';

    public const Rejected = 'rejected';

    public const InterviewScheduled = 'interview_scheduled';

    public const Interviewed = 'interviewed';

    public const Selected = 'selected';

    public const OfferSent = 'offer_sent';

    public const OfferAccepted = 'offer_accepted';

    public const OfferRejected = 'offer_rejected';

    public const Hired = 'hired';

    public const Withdrawn = 'withdrawn';

    public const Archived = 'archived';

    /** @return array<int, string> */
    public static function values(): array
    {
        return [
            self::Submitted,
            self::UnderReview,
            self::Shortlisted,
            self::Rejected,
            self::InterviewScheduled,
            self::Interviewed,
            self::Selected,
            self::OfferSent,
            self::OfferAccepted,
            self::OfferRejected,
            self::Hired,
            self::Withdrawn,
            self::Archived,
        ];
    }
}
