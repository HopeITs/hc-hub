<?php

namespace App\Services;

use App\Enums\ApprovalDecision;
use App\Enums\RecruitmentRequestMode;
use App\Enums\RecruitmentRequestStatus as Status;
use App\Enums\WorkflowStepKey;
use App\Models\RecruitmentRequest;

/**
 * The road this particular request travels, and how far along it is.
 *
 * Not every request takes the same road, which is the whole point of building
 * this from the workflow instance rather than from a fixed list of three. A
 * requester at the top of the chart has no manager to approve them, so that
 * stop is not on their map at all. A direct opening was settled outside the
 * system and has one stop or none. Drawing three circles and greying two of
 * them would describe a journey nobody is making.
 *
 * Each stop reports its own state so the screen only has to draw it:
 *
 *   done     decided, and the request moved past it
 *   current  where the request is waiting now
 *   skipped  on the definition, but not on this request's road
 *   pending  still ahead
 */
class RecruitmentApprovalTrack
{
    public const DONE = 'done';

    public const CURRENT = 'current';

    public const SKIPPED = 'skipped';

    public const PENDING = 'pending';

    /**
     * The stops on this request's road, in order, with the end state.
     *
     * The first and last are not approval steps: a request begins by being
     * submitted and ends by taking effect, and a track that started at the
     * first approver would not show the requester their own submission.
     *
     * @return array<int, array{key: string, label: string, state: string, at: ?string, by: ?string}>
     */
    public function for(RecruitmentRequest $request): array
    {
        $status = (int) $request->status;

        $track = [$this->submission($request)];

        foreach ($this->steps($request) as $step) {
            $track[] = $step;
        }

        $track[] = $this->outcome($request, $status);

        return $track;
    }

    /** Submitting is a stop too: it is where the requester's part ends. */
    private function submission(RecruitmentRequest $request): array
    {
        return [
            'key' => 'submitted',
            'label' => __('recruitment.track.submitted'),
            'state' => $request->submitted_at === null ? self::CURRENT : self::DONE,
            'at' => $request->submitted_at?->format('Y-m-d H:i'),
            'by' => $request->requester?->profile?->name ?? $request->requester?->name,
        ];
    }

    /**
     * The approval stops, taken from the instance actually running.
     *
     * A request that has not been submitted has no instance yet, so the road
     * ahead is read from the workflow definition instead — otherwise a draft
     * would show a submission and an outcome with nothing in between.
     *
     * @return array<int, array<string, mixed>>
     */
    private function steps(RecruitmentRequest $request): array
    {
        $instance = $request->workflowInstances()->latest('id')->first();

        if ($instance === null) {
            return $this->expectedSteps($request);
        }

        $instance->load(['steps.workflowStep', 'steps.actedBy.profile']);
        $current = (int) $instance->current_step_order;

        return $instance->steps
            ->sortBy(fn ($step) => (int) $step->workflowStep?->step_order)
            ->map(function ($step) use ($current): array {
                $order = (int) $step->workflowStep?->step_order;
                $decision = $step->decision === null ? null : (int) $step->decision;

                return [
                    'key' => 'step-'.$step->getKey(),
                    'label' => WorkflowStepKey::getLocalizedName((int) $step->workflowStep?->step_key)['value'],
                    'state' => $this->stepState($step, $decision, $order, $current),
                    'at' => $step->acted_at?->format('Y-m-d H:i'),
                    'by' => $step->actedBy?->profile?->name ?? $step->actedBy?->name,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * A skipped step says so rather than pretending to be pending.
     *
     * D57: a skip is marked in `decision_reason` because there is no column
     * for it, so that is what is read here.
     */
    private function stepState($step, ?int $decision, int $order, int $current): string
    {
        if ($decision === null && str_starts_with((string) $step->decision_reason, 'skip:')) {
            return self::SKIPPED;
        }

        if ($decision !== null) {
            return $decision === ApprovalDecision::Approve ? self::DONE : self::DONE;
        }

        return $order === $current ? self::CURRENT : self::PENDING;
    }

    /**
     * The road a draft is about to take, before any instance exists.
     *
     * Shown so a requester can see what they are submitting into. A direct
     * opening shows its single stop; everything else shows the definition's,
     * with the manager left out when this requester has nobody above them.
     *
     * @return array<int, array<string, mixed>>
     */
    private function expectedSteps(RecruitmentRequest $request): array
    {
        if ((int) $request->mode === RecruitmentRequestMode::DirectHr) {
            return [$this->pendingStop(WorkflowStepKey::HrSyriaDirectOpenApproval)];
        }

        $keys = [WorkflowStepKey::ManagerApproval, WorkflowStepKey::FinanceApproval, WorkflowStepKey::GovernorateHrApproval];

        $requester = $request->requester;
        $skipsManager = $requester !== null
            && app(RecruitmentScopeResolver::class)->mayRequestWithoutManagerApproval($requester);

        return collect($keys)
            ->reject(fn (int $key): bool => $skipsManager && $key === WorkflowStepKey::ManagerApproval)
            ->map(fn (int $key): array => $this->pendingStop($key))
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function pendingStop(int $stepKey): array
    {
        return [
            'key' => 'expected-'.$stepKey,
            'label' => WorkflowStepKey::getLocalizedName($stepKey)['value'],
            'state' => self::PENDING,
            'at' => null,
            'by' => null,
        ];
    }

    /**
     * Where the road ends, which is not always approval.
     *
     * A rejected or cancelled request still has an end, and showing it as a
     * pending "applied" would be a road that never finishes.
     *
     * @return array<string, mixed>
     */
    private function outcome(RecruitmentRequest $request, int $status): array
    {
        [$label, $state] = match ($status) {
            Status::Applied => [__('recruitment.track.applied'), self::DONE],
            Status::Approved, Status::FailedApplication => [__('recruitment.track.approved'), self::CURRENT],
            Status::Rejected => [__('recruitment.track.rejected'), self::DONE],
            Status::Cancelled => [__('recruitment.track.cancelled'), self::DONE],
            default => [__('recruitment.track.applied'), self::PENDING],
        };

        return [
            'key' => 'outcome',
            'label' => $label,
            'state' => $state,
            'at' => $request->applied_at?->format('Y-m-d H:i'),
            'by' => null,
        ];
    }
}
