<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Inbox;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentApproverEligibility;
use Modules\Hr\Actions\Recruitment\RecruitmentPendingApprovalQuery;
use Modules\Hr\Support\Recruitment\Access;
use Modules\Hr\Support\Recruitment\Labels;

/**
 * What waits on the viewer's decision in HR, shaped for the Desk's "Needs you":
 * leave requests to decide or confirm and profile changes to approve (read
 * from the HR work inbox, with its own permission and reach rules), and
 * recruitment requests whose current step is assigned to the viewer. Nothing
 * is stored; a person with no HR right gets nothing.
 */
final class DeskDecisions
{
    public function __construct(
        private readonly WorkInbox $inbox,
        private readonly RecruitmentPendingApprovalQuery $pending,
        private readonly RecruitmentApproverEligibility $eligibility,
    ) {}

    /**
     * @return Collection<int, array{key: string, kind: string, title: string, context: string, who: string, since: CarbonInterface|null, href: string}>
     */
    public function for(User $user): Collection
    {
        $rows = new Collection;

        if ($this->inbox->hasSources($user)) {
            $kinds = ['leave:decide' => 'leave', 'leave:review' => 'leave_review', 'profile:approve' => 'profile_change'];

            foreach ($this->inbox->for($user)['rows'] as $row) {
                $kind = $kinds[$row['source'].':'.$row['action']] ?? null;

                if ($kind === null || $row['href'] === '') {
                    continue;
                }

                $rows->push([
                    'key' => 'hr-'.$row['key'],
                    'kind' => $kind,
                    'title' => $row['name'],
                    'context' => (string) __('desk.decision.'.$kind, ['what' => $row['what']]),
                    'who' => '',
                    'since' => $row['since'],
                    'href' => $row['href'],
                ]);
            }
        }

        return $rows->concat($this->recruitment($user))->values();
    }

    /**
     * @return Collection<int, array{key: string, kind: string, title: string, context: string, who: string, since: CarbonInterface|null, href: string}>
     */
    private function recruitment(User $user): Collection
    {
        if (! Access::hasActivePermission($user, 'recruitment-requests.approve') || ! $this->eligibility->mayReceiveApprovals($user)) {
            return new Collection;
        }

        return $this->pending->forUser($user)
            ->addSelect(['waiting_since' => $this->pending->waitingSince()])
            ->limit(WorkInbox::PerSource)
            ->get()
            ->map(function ($step): array {
                $request = $step->workflowInstance->request;

                return [
                    'key' => 'hr-recruitment-'.$step->id,
                    'kind' => 'recruitment',
                    'title' => (string) ($request->targetPosition?->title ?? $request->request_number),
                    'context' => (string) __('desk.decision.recruitment', ['what' => Labels::enum('WorkflowStepKey', (int) $step->workflowStep?->step_key)]),
                    'who' => (string) ($request->requester?->name ?? ''),
                    'since' => $step->waiting_since ? CarbonImmutable::parse($step->waiting_since) : null,
                    'href' => route('hr.recruitment.requests.show', ['requestId' => $request->id]),
                ];
            })
            ->values();
    }
}
