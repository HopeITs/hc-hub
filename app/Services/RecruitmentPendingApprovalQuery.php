<?php

namespace App\Services;

use App\Enums\RecruitmentRequestStatus;
use App\Enums\WorkflowInstanceStatus;
use App\Models\RecruitmentWorkflowInstanceStep;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RecruitmentPendingApprovalQuery
{
    /**
     * Only the current, undecided step assigned to this exact user is pending.
     * Authorization is checked again by policy when Task 8 adds decisions.
     */
    public function forUser(User $user): Builder
    {
        return RecruitmentWorkflowInstanceStep::query()
            ->select('recruitment_workflow_instance_steps.*')
            ->join(
                'recruitment_workflow_instances as pending_instances',
                'pending_instances.id',
                '=',
                'recruitment_workflow_instance_steps.workflow_instance_id',
            )
            ->join(
                'recruitment_workflow_steps as pending_definitions',
                'pending_definitions.id',
                '=',
                'recruitment_workflow_instance_steps.workflow_step_id',
            )
            ->join(
                'recruitment_requests as pending_requests',
                'pending_requests.id',
                '=',
                'pending_instances.request_id',
            )
            ->where('recruitment_workflow_instance_steps.assigned_user_id', $user->getKey())
            ->whereNull('recruitment_workflow_instance_steps.decision')
            ->whereNull('recruitment_workflow_instance_steps.acted_at')
            ->where('pending_instances.status', WorkflowInstanceStatus::Running)
            ->where('pending_requests.status', RecruitmentRequestStatus::UnderReview)
            ->whereColumn('pending_definitions.step_order', 'pending_instances.current_step_order')
            ->with([
                'workflowStep',
                'workflowInstance.request.requester.profile',
                'workflowInstance.request.scopeCity',
                'workflowInstance.request.targetPosition',
            ])
            ->orderBy('pending_instances.started_at')
            ->orderBy('recruitment_workflow_instance_steps.id');
    }
}
