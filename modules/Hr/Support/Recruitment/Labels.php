<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment;

use App\Enums\StatusTone;
use Illuminate\Support\Facades\Lang;
use Modules\Hr\Enums\HiringCampaignStatus;
use Modules\Hr\Enums\RecruitmentRequestStatus;

/**
 * Display text for the stored recruitment codes. The words live in
 * lang/{en,ar}/hr_recruitment.php (hub.enums.<Enum>.<value> for the numeric
 * enums, applicant_ui.statuses.<value> and friends for the string ones), so
 * nothing on a page reads a label out of an enum class.
 */
final class Labels
{
    /** A label for a numeric enum value: Labels::enum('RecruitmentRequestStatus', 5). */
    public static function enum(string $enum, int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $key = 'hr_recruitment.hub.enums.'.$enum.'.'.$value;

        return Lang::has($key) ? (string) __($key) : (string) $value;
    }

    /** A request field's name: fields.<key>, else the form's own label for it. */
    public static function field(string $field): string
    {
        foreach (['hr_recruitment.fields.'.$field, 'hr_recruitment.'.$field] as $key) {
            if (Lang::has($key) && is_string(__($key))) {
                return (string) __($key);
            }
        }

        return str_replace('_', ' ', $field);
    }

    /** A label from one of the old app's lang groups, falling back to the raw value. */
    public static function key(string $key, int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $full = 'hr_recruitment.'.$key.'.'.$value;

        return Lang::has($full) ? (string) __($full) : str_replace('_', ' ', (string) $value);
    }

    public static function requestTone(int $status): StatusTone
    {
        return match ($status) {
            RecruitmentRequestStatus::Approved->value, RecruitmentRequestStatus::Applied->value => StatusTone::Good,
            RecruitmentRequestStatus::Submitted->value, RecruitmentRequestStatus::UnderReview->value, RecruitmentRequestStatus::Resubmitted->value => StatusTone::Active,
            RecruitmentRequestStatus::ChangesRequested->value => StatusTone::Warn,
            RecruitmentRequestStatus::Rejected->value, RecruitmentRequestStatus::FailedApplication->value => StatusTone::Critical,
            default => StatusTone::Neutral,
        };
    }

    public static function campaignTone(int $status): StatusTone
    {
        return match ($status) {
            HiringCampaignStatus::Open->value => StatusTone::Good,
            HiringCampaignStatus::Published->value => StatusTone::Active,
            HiringCampaignStatus::Draft->value => StatusTone::Warn,
            HiringCampaignStatus::Disabled->value => StatusTone::Critical,
            default => StatusTone::Neutral,
        };
    }

    /** A tone for the string statuses (applications, interviews, offers). */
    public static function tone(string $status): StatusTone
    {
        return match ($status) {
            'hired', 'offer_accepted', 'accepted', 'completed', 'selected', 'finalist' => StatusTone::Good,
            'rejected', 'offer_rejected', 'cancelled', 'no_show', 'expired', 'withdrawn' => StatusTone::Critical,
            'shortlisted', 'interview_scheduled', 'interviewed', 'offer_sent', 'sent', 'scheduled', 'rescheduled', 'under_review' => StatusTone::Active,
            'draft', 'revised' => StatusTone::Warn,
            default => StatusTone::Neutral,
        };
    }
}
