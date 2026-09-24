<?php

namespace App\Services;

use App\Enums\RecruitmentApplicationStatus as Status;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentApplicationDecision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Screening moves a reviewer makes by hand, each recorded as a decision.
 *
 * Only the screening stage is here. Interview, offer and hiring statuses are
 * set by the steps that produce them, so an application in one of those
 * states offers no manual move at all.
 *
 * A closed or expired campaign does not block screening: reviewing what came
 * in is exactly what happens after the window shuts.
 */
class RecruitmentApplicationStatusService
{
    public const AUDIT_LOG = 'recruitment-application-status';

    public const REASON_CODE = 'manual_review';

    public const MIN_REASON = 10;

    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        Status::Submitted => [Status::UnderReview, Status::Shortlisted, Status::Rejected],
        Status::UnderReview => [Status::Shortlisted, Status::Rejected],
        Status::Shortlisted => [Status::UnderReview, Status::Rejected],
        Status::Rejected => [Status::UnderReview],
    ];

    public function __construct(private readonly ActivityLogger $activity) {}

    /** @return array<int, string> */
    public function targets(RecruitmentApplication $application): array
    {
        return self::TRANSITIONS[$application->status] ?? [];
    }

    /** Rejecting someone, or taking a rejection back, has to say why. */
    public function requiresReason(string $from, string $to): bool
    {
        return $to === Status::Rejected || $from === Status::Rejected;
    }

    /**
     * $expectedFrom is the status the reviewer was looking at. If someone else
     * moved the application in the meantime, this refuses rather than
     * overwriting a decision the reviewer never saw.
     */
    public function change(
        User $actor,
        RecruitmentApplication $application,
        string $expectedFrom,
        string $to,
        ?string $reason,
    ): RecruitmentApplicationDecision {
        $reason = trim((string) $reason) ?: null;

        return DB::transaction(function () use ($actor, $application, $expectedFrom, $to, $reason): RecruitmentApplicationDecision {
            $locked = RecruitmentApplication::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();
            $from = (string) $locked->status;

            if ($from !== $expectedFrom) {
                throw ValidationException::withMessages(['statusTarget' => __('recruitment-applicant.status_change.stale')]);
            }

            if (! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
                throw ValidationException::withMessages(['statusTarget' => __('recruitment-applicant.status_change.not_allowed')]);
            }

            if ($this->requiresReason($from, $to) && ($reason === null || mb_strlen($reason) < self::MIN_REASON)) {
                throw ValidationException::withMessages([
                    'statusReason' => __('recruitment-applicant.status_change.reason_required', ['min' => self::MIN_REASON]),
                ]);
            }

            $locked->forceFill(['status' => $to])->save();

            $decision = RecruitmentApplicationDecision::query()->forceCreate([
                'application_id' => $locked->getKey(),
                'from_status' => $from,
                'to_status' => $to,
                'reason_code' => self::REASON_CODE,
                'reason_text' => $reason,
                'decided_by_user_id' => $actor->getKey(),
                'decided_at' => now(),
            ]);

            $this->activity->log(
                'recruitment_application.status_changed',
                subject: $locked,
                causer: $actor,
                properties: ['from' => $from, 'to' => $to, 'decision_id' => $decision->getKey(), 'role' => $actor->myRole()],
                logName: self::AUDIT_LOG,
            );

            return $decision;
        });
    }
}
