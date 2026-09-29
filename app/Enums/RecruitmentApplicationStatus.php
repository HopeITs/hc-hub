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

    /**
     * Badge classes, one meaning per hue as in the interview and offer
     * badges: blue in progress, green moving to a hire, red closed against
     * the candidate, neutral waiting or set aside. Never a tint of the brand
     * red, which reads as pink.
     */
    public static function classes(?string $status): string
    {
        return match ($status) {
            self::Shortlisted, self::InterviewScheduled, self::Interviewed => 'bg-blue-50 text-blue-800',
            self::Selected, self::OfferSent, self::OfferAccepted, self::Hired => 'bg-green-50 text-green-800',
            self::Rejected, self::OfferRejected => 'bg-red-50 text-red-800',
            default => 'bg-neutral-100 text-secondary-dark',
        };
    }
}
