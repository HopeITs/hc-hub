<?php

namespace Modules\Hr\Actions\Recruitment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Enums\RecruitmentApplicationStatus as Status;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\RecruitmentApplication;

class RecruitmentFinalistService
{
    public function __construct(private readonly RecruitmentApplicationStatusService $statuses) {}

    /**
     * Finalize a campaign that has exactly one finalist. Every application in
     * the campaign is locked before counting so two reviewers cannot finalize
     * different snapshots of the list at the same time.
     */
    public function selectSingle(User $actor, HiringCampaign $campaign): RecruitmentApplication
    {
        Gate::forUser($actor)->authorize('view', $campaign);

        return DB::transaction(function () use ($actor, $campaign): RecruitmentApplication {
            $applications = RecruitmentApplication::query()
                ->where('campaign_id', $campaign->getKey())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $finalists = $applications->where('status', Status::Finalist)->values();

            if ($finalists->count() !== 1) {
                throw ValidationException::withMessages([
                    'finalists' => $finalists->isEmpty()
                        ? __('hr_recruitment.applicant_ui.finalists.none')
                        : __('hr_recruitment.applicant_ui.finalists.multiple', ['count' => $finalists->count()]),
                ]);
            }

            /** @var RecruitmentApplication $finalist */
            $finalist = $finalists->first();
            $this->statuses->change($actor, $finalist, Status::Finalist, Status::Selected, null);

            return $finalist->fresh();
        });
    }
}
