<?php

namespace App\Observers;

use App\Models\RecruitmentApplication;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Phase 2 Task 2's "Create audit/submission log" step, for every path that
 * creates an application — Team 1's PublicRecruitmentApplicationService (the
 * real /careers form) included, without modifying it.
 *
 * Runs inside the submitting transaction, so a submission that rolls back
 * takes its audit row with it: the log never records an application that
 * does not exist.
 *
 * Identifiers only — no name, email or answers: the activity log is read by
 * administrators outside recruitment scope, and applicant PII stays behind
 * the applicant policies.
 *
 * D8: an audit write that fails is reported and must not roll back the
 * submission it describes, hence the savepoint and the catch.
 */
class RecruitmentApplicationObserver
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function created(RecruitmentApplication $application): void
    {
        try {
            DB::transaction(fn () => $this->activityLogger->log('recruitment_application_submitted', $application, null, [
                'campaign_id' => $application->campaign_id,
                'public_link_id' => $application->public_link_id,
                'form_version_id' => $application->form_version_id,
                'campaign_invite_id' => $application->campaign_invite_id,
            ], 'recruitment'));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
