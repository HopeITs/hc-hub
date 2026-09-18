<?php

namespace App\Services;

use App\Enums\RecruitmentEventType;
use App\Enums\RecruitmentRequestMode;
use App\Enums\RecruitmentRequestStatus;
use App\Enums\RecruitmentScopeType;
use App\Enums\WorkflowInstanceStatus;
use App\Enums\WorkflowSkipReason;
use App\Enums\WorkflowStepKey;
use App\Models\RecruitmentRequest;
use App\Models\RecruitmentRequestVersion;
use App\Models\RecruitmentWorkflowDefinition;
use App\Models\RecruitmentWorkflowDefinitionVersion;
use App\Models\RecruitmentWorkflowInstance;
use App\Models\RecruitmentWorkflowInstanceStep;
use App\Models\RecruitmentWorkflowStep;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Freezes the active workflow definition into an executable instance.
 *
 * Call start() with the exact immutable request version created by submission,
 * inside the same transaction. The unique request_version_id constraint and
 * the idempotent lookup make retries safe.
 */
class RecruitmentWorkflowInstanceService
{
    public function __construct(
        private readonly RecruitmentApproverResolver $approvers,
        private readonly RecruitmentScopeResolver $scopes,
    ) {}

    public function start(
        RecruitmentRequest $request,
        RecruitmentRequestVersion $requestVersion,
    ): ?RecruitmentWorkflowInstance {
        if ((int) $requestVersion->request_id !== (int) $request->getKey()) {
            throw new RecruitmentWorkflowConfigurationException('The request version does not belong to the request.');
        }

        if ($existing = RecruitmentWorkflowInstance::query()
            ->where('request_version_id', $requestVersion->getKey())
            ->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($request, $requestVersion): ?RecruitmentWorkflowInstance {
            $request = RecruitmentRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($existing = RecruitmentWorkflowInstance::query()
                ->where('request_version_id', $requestVersion->getKey())
                ->first()) {
                return $existing;
            }

            $requester = $request->requester()->firstOrFail();

            // HR Syria's own direct opening has no electronic approval path.
            if ((int) $request->mode === RecruitmentRequestMode::DirectHr
                && $requester->activeRoleIs('HR-SYR')) {
                if ($request->events()
                    ->where('request_version_id', $requestVersion->getKey())
                    ->where('event_type', RecruitmentEventType::DirectHrOpened)
                    ->exists()) {
                    return null;
                }

                $from = (int) $request->status;
                $request->forceFill(['status' => RecruitmentRequestStatus::Approved])->save();

                $request->events()->create([
                    'request_version_id' => $requestVersion->getKey(),
                    'event_type' => RecruitmentEventType::DirectHrOpened,
                    'from_status' => $from,
                    'to_status' => RecruitmentRequestStatus::Approved,
                    'actor_user_id' => $requester->getKey(),
                    'actor_role' => $requester->myRole() ?? 'HR-SYR',
                    'scope_type' => RecruitmentScopeType::Organization,
                ]);

                return null;
            }

            $definitionVersion = $this->activeDefinitionVersion((int) $request->request_type);
            $steps = $this->applicableSteps($definitionVersion, (int) $request->mode);

            if ($steps->isEmpty()) {
                throw new RecruitmentWorkflowConfigurationException('The active workflow contains no steps for this request mode.');
            }

            $skipManager = (int) $request->mode === RecruitmentRequestMode::Normal
                && $this->scopes->mayRequestWithoutManagerApproval($requester);

            if ((int) $request->mode === RecruitmentRequestMode::Normal
                && ! $skipManager
                && $this->scopes->approvingManagerPosition($requester) === null) {
                throw ValidationException::withMessages([
                    'request' => __('recruitment-workflow.missing_manager'),
                ]);
            }

            $currentOrder = (int) $steps->first()->step_order;
            if ($skipManager) {
                $currentOrder = (int) ($steps
                    ->first(fn (RecruitmentWorkflowStep $step): bool => (int) $step->step_key !== WorkflowStepKey::ManagerApproval)
                    ?->step_order ?? $currentOrder);
            }

            $instance = RecruitmentWorkflowInstance::query()->create([
                'request_id' => $request->getKey(),
                'request_version_id' => $requestVersion->getKey(),
                'definition_version_id' => $definitionVersion->getKey(),
                'status' => WorkflowInstanceStatus::Running,
                'current_step_order' => $currentOrder,
                'started_at' => now(),
            ]);

            $resolutionMeta = [];

            foreach ($steps as $step) {
                if ($skipManager && (int) $step->step_key === WorkflowStepKey::ManagerApproval) {
                    $this->createSkippedManagerStep($instance, $step);
                    $this->recordSkippedManagerEvent($request, $requestVersion, $instance, $step);
                    $resolutionMeta[(string) $step->step_key] = ['skipped' => true, 'reason' => 'top_level_requester'];

                    continue;
                }

                $assignment = $this->approvers->resolve($request, $step);

                $instance->steps()->create([
                    'workflow_step_id' => $step->getKey(),
                    'assigned_role' => $assignment->role,
                    'assigned_scope_type' => $assignment->scopeType,
                    'assigned_scope_id' => $assignment->scopeId,
                    'assigned_user_id' => $assignment->userId,
                ]);

                $resolutionMeta[(string) $step->step_key] = array_merge([
                    'assigned_user_id' => $assignment->userId,
                    'assigned_role' => $assignment->role,
                ], $assignment->meta);
            }

            $from = (int) $request->status;
            $request->forceFill(['status' => RecruitmentRequestStatus::UnderReview])->save();

            $request->events()->create([
                'request_version_id' => $requestVersion->getKey(),
                'event_type' => RecruitmentEventType::WorkflowStarted,
                'workflow_instance_id' => $instance->getKey(),
                'from_status' => $from,
                'to_status' => RecruitmentRequestStatus::UnderReview,
                'actor_user_id' => $requester->getKey(),
                'actor_role' => $requester->myRole() ?? 'system',
                'scope_type' => $this->scopes->scopeType($requester),
                'scope_id' => $this->requesterScopeId($requester, $request),
                'meta' => ['approver_resolution' => $resolutionMeta],
            ]);

            return $instance->load(['definitionVersion', 'steps.workflowStep', 'steps.assignedUser']);
        });
    }

    public function currentStep(RecruitmentWorkflowInstance $instance): ?RecruitmentWorkflowInstanceStep
    {
        return $instance->steps()
            ->whereHas('workflowStep', fn ($query) => $query
                ->where('step_order', $instance->current_step_order))
            ->with(['workflowStep', 'assignedUser'])
            ->first();
    }

    private function activeDefinitionVersion(int $requestType): RecruitmentWorkflowDefinitionVersion
    {
        $definitions = RecruitmentWorkflowDefinition::query()
            ->where('request_type', $requestType)
            ->where('is_active', true)
            ->pluck('id');

        if ($definitions->count() !== 1) {
            throw new RecruitmentWorkflowConfigurationException(
                "Expected one active workflow definition for request type {$requestType}; found {$definitions->count()}.",
            );
        }

        $versions = RecruitmentWorkflowDefinitionVersion::query()
            ->where('definition_id', $definitions->first())
            ->where('is_active', true)
            ->orderByDesc('version_no')
            ->get();

        if ($versions->count() !== 1) {
            throw new RecruitmentWorkflowConfigurationException(
                "Expected one active workflow version for request type {$requestType}; found {$versions->count()}.",
            );
        }

        return $versions->first();
    }

    /** @return Collection<int, RecruitmentWorkflowStep> */
    private function applicableSteps(
        RecruitmentWorkflowDefinitionVersion $version,
        int $mode,
    ): Collection {
        return $version->steps()
            ->get()
            ->filter(function (RecruitmentWorkflowStep $step) use ($mode): bool {
                $modes = $step->required_condition_json['modes'] ?? null;

                return $modes === null || in_array($mode, array_map('intval', $modes), true);
            })
            ->values();
    }

    private function createSkippedManagerStep(
        RecruitmentWorkflowInstance $instance,
        RecruitmentWorkflowStep $step,
    ): void {
        $instance->steps()->create([
            'workflow_step_id' => $step->getKey(),
            'assigned_role' => 'manager',
            'assigned_scope_type' => RecruitmentScopeType::DirectReports,
            'decision_reason' => 'skip:'.WorkflowSkipReason::TopLevelRequester,
            'acted_at' => now(),
        ]);
    }

    private function recordSkippedManagerEvent(
        RecruitmentRequest $request,
        RecruitmentRequestVersion $version,
        RecruitmentWorkflowInstance $instance,
        RecruitmentWorkflowStep $step,
    ): void {
        $request->events()->create([
            'request_version_id' => $version->getKey(),
            'event_type' => RecruitmentEventType::StepSkipped,
            'workflow_instance_id' => $instance->getKey(),
            'workflow_step_id' => $step->getKey(),
            'actor_role' => 'system',
            'comment' => __('recruitment-workflow.top_level_manager_skipped'),
            'meta' => ['skip_reason' => WorkflowSkipReason::TopLevelRequester],
        ]);
    }

    private function requesterScopeId(User $requester, RecruitmentRequest $request): ?int
    {
        return match ($this->scopes->scopeType($requester)) {
            RecruitmentScopeType::DirectReports => $this->scopes->position($requester)?->getKey(),
            RecruitmentScopeType::City => $request->scope_city_id,
            default => null,
        };
    }
}
