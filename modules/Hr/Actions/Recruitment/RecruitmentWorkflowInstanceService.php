<?php

namespace Modules\Hr\Actions\Recruitment;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Enums\RecruitmentEventType;
use Modules\Hr\Enums\RecruitmentRequestMode;
use Modules\Hr\Enums\RecruitmentRequestStatus;
use Modules\Hr\Enums\RecruitmentScopeType;
use Modules\Hr\Enums\WorkflowInstanceStatus;
use Modules\Hr\Enums\WorkflowSkipReason;
use Modules\Hr\Enums\WorkflowStepKey;
use Modules\Hr\Models\RecruitmentRequest;
use Modules\Hr\Models\RecruitmentRequestVersion;
use Modules\Hr\Models\RecruitmentWorkflowDefinition;
use Modules\Hr\Models\RecruitmentWorkflowDefinitionVersion;
use Modules\Hr\Models\RecruitmentWorkflowInstance;
use Modules\Hr\Models\RecruitmentWorkflowInstanceStep;
use Modules\Hr\Models\RecruitmentWorkflowStep;
use Modules\Hr\Support\Recruitment\Access;
use Modules\Hr\Support\Recruitment\Notifications\MissingRecruitmentApprover;

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
        private readonly RecruitmentRequestStateMachine $states,
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
            if ((int) $request->mode === RecruitmentRequestMode::DirectHr->value
                && Access::activeRoleIs($requester, 'HR-SYR')) {
                if ($request->events()
                    ->where('request_version_id', $requestVersion->getKey())
                    ->where('event_type', RecruitmentEventType::DirectHrOpened->value)
                    ->exists()) {
                    return null;
                }

                $from = (int) $request->status;
                // T19 through the table, so a request that is no longer open
                // cannot be approved by a stray second call.
                $this->states->apply($request, 'direct_open');

                $request->events()->create([
                    'request_version_id' => $requestVersion->getKey(),
                    'event_type' => RecruitmentEventType::DirectHrOpened->value,
                    'from_status' => $from,
                    'to_status' => RecruitmentRequestStatus::Approved->value,
                    'actor_user_id' => $requester->getKey(),
                    'actor_role' => Access::myRole($requester) ?? 'HR-SYR',
                    'scope_type' => RecruitmentScopeType::Organization->value,
                ]);

                return null;
            }

            $definitionVersion = $this->activeDefinitionVersion((int) $request->request_type);
            $steps = $this->applicableSteps($definitionVersion, (int) $request->mode, (int) $request->request_type);

            if ($steps->isEmpty()) {
                throw new RecruitmentWorkflowConfigurationException('The active workflow contains no steps for this request mode.');
            }

            $skipManager = (int) $request->mode === RecruitmentRequestMode::Normal->value
                && $this->scopes->mayRequestWithoutManagerApproval($requester);

            // The organisation has one financial approver. When they are the
            // one asking, there is nobody else to ask, so the step is skipped
            // and said to be skipped rather than waiting on a self-approval
            // the module refuses anyway.
            $skipFinance = (int) $request->mode === RecruitmentRequestMode::Normal->value
                && $this->scopes->isFinanceApprover($requester);

            if ((int) $request->mode === RecruitmentRequestMode::Normal->value
                && ! $skipManager
                && $this->scopes->approvingManagerPosition($requester) === null) {
                throw ValidationException::withMessages([
                    'request' => __('hr_recruitment.workflow_ui.missing_manager'),
                ]);
            }

            $skipped = array_values(array_filter([
                $skipManager ? WorkflowStepKey::ManagerApproval->value : null,
                $skipFinance ? WorkflowStepKey::FinanceApproval->value : null,
            ], fn (?int $key): bool => $key !== null));

            $currentOrder = (int) ($steps
                ->first(fn (RecruitmentWorkflowStep $step): bool => ! in_array((int) $step->step_key, $skipped, true))
                ?->step_order ?? (int) $steps->first()->step_order);

            $instance = RecruitmentWorkflowInstance::query()->create([
                'request_id' => $request->getKey(),
                'request_version_id' => $requestVersion->getKey(),
                'definition_version_id' => $definitionVersion->getKey(),
                'status' => WorkflowInstanceStatus::Running->value,
                'current_step_order' => $currentOrder,
                'started_at' => now(),
            ]);

            $resolutionMeta = [];
            $missingApprovals = [];

            foreach ($steps as $step) {
                if (in_array((int) $step->step_key, $skipped, true)) {
                    $isManager = (int) $step->step_key === WorkflowStepKey::ManagerApproval->value;
                    $reason = $isManager
                        ? WorkflowSkipReason::TopLevelRequester->value
                        : WorkflowSkipReason::RequesterIsApprover->value;

                    $this->createSkippedStep($instance, $step, $reason, $isManager ? 'manager' : 'finance-approver',
                        $isManager ? RecruitmentScopeType::DirectReports->value : RecruitmentScopeType::Organization->value);
                    $this->recordSkippedStepEvent($request, $requestVersion, $instance, $step, $reason);

                    $resolutionMeta[(string) $step->step_key] = [
                        'skipped' => true,
                        'reason' => $isManager ? 'top_level_requester' : 'requester_is_approver',
                    ];

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
                if (! $assignment->isAssigned() && isset($assignment->meta['reason'])) {
                    $missingApprovals[] = WorkflowStepKey::getLocalizedName((int) $step->step_key)['value'] ?? (string) $step->step_key;
                }
            }

            $from = (int) $request->status;
            // T4 for a first submission, T13 for a resubmission — through the
            // table rather than straight onto the column, so a rejected or
            // cancelled request cannot be revived by starting a workflow over it.
            $this->states->apply(
                $request,
                $from === RecruitmentRequestStatus::Resubmitted->value ? 'workflow_restarted' : 'workflow_started',
            );

            $request->events()->create([
                'request_version_id' => $requestVersion->getKey(),
                'event_type' => RecruitmentEventType::WorkflowStarted->value,
                'workflow_instance_id' => $instance->getKey(),
                'from_status' => $from,
                'to_status' => RecruitmentRequestStatus::UnderReview->value,
                'actor_user_id' => $requester->getKey(),
                'actor_role' => Access::myRole($requester) ?? 'system',
                'scope_type' => $this->scopes->scopeType($requester),
                'scope_id' => $this->requesterScopeId($requester, $request),
                'meta' => ['approver_resolution' => $resolutionMeta],
            ]);

            if ($missingApprovals !== []) {
                $this->notifyHrOfMissingApprovers($request, $missingApprovals);
            }

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

    /** @param array<int, string> $steps */
    private function notifyHrOfMissingApprovers(RecruitmentRequest $request, array $steps): void
    {
        Access::broadcastRecipientsQuery()
            ->get()
            ->filter(fn (User $user): bool => Access::activeRoleIs($user, 'HR')
                && Access::hasActivePermission($user, 'recruitment-requests.manage'))
            ->each(fn (User $user) => $user->notify(new MissingRecruitmentApprover($request, $steps)));
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

    /**
     * The steps this particular request actually walks.
     *
     * A step names the modes it belongs to, and may name request types it does
     * not apply to. Closing a vacancy or a position spends no money, so neither
     * is held up waiting on financial review (D56).
     *
     * @return Collection<int, RecruitmentWorkflowStep>
     */
    private function applicableSteps(
        RecruitmentWorkflowDefinitionVersion $version,
        int $mode,
        int $requestType,
    ): Collection {
        return $version->steps()
            ->get()
            ->filter(function (RecruitmentWorkflowStep $step) use ($mode, $requestType): bool {
                $condition = $step->required_condition_json ?? [];

                $modes = $condition['modes'] ?? null;
                if ($modes !== null && ! in_array($mode, array_map('intval', $modes), true)) {
                    return false;
                }

                $except = $condition['except_types'] ?? null;

                return $except === null || ! in_array($requestType, array_map('intval', $except), true);
            })
            ->values();
    }

    /**
     * A step nobody can decide, written down as skipped rather than left open.
     *
     * D57: there is no column for a skip, so the marker lives in
     * `decision_reason` and `decision` stays null. `acted_at` is set because
     * the step is finished with, even though nobody decided it.
     */
    private function createSkippedStep(
        RecruitmentWorkflowInstance $instance,
        RecruitmentWorkflowStep $step,
        int $reason,
        string $role,
        int $scopeType,
    ): void {
        $instance->steps()->create([
            'workflow_step_id' => $step->getKey(),
            'assigned_role' => $role,
            'assigned_scope_type' => $scopeType,
            'decision_reason' => 'skip:'.$reason,
            'acted_at' => now(),
        ]);
    }

    private function recordSkippedStepEvent(
        RecruitmentRequest $request,
        RecruitmentRequestVersion $version,
        RecruitmentWorkflowInstance $instance,
        RecruitmentWorkflowStep $step,
        int $reason,
    ): void {
        $request->events()->create([
            'request_version_id' => $version->getKey(),
            'event_type' => RecruitmentEventType::StepSkipped->value,
            'workflow_instance_id' => $instance->getKey(),
            'workflow_step_id' => $step->getKey(),
            'actor_role' => 'system',
            'comment' => __($reason === WorkflowSkipReason::TopLevelRequester->value
                ? 'hr_recruitment.workflow_ui.top_level_manager_skipped'
                : 'hr_recruitment.workflow_ui.requester_is_approver_skipped'),
            'meta' => ['skip_reason' => $reason],
        ]);
    }

    private function requesterScopeId(User $requester, RecruitmentRequest $request): ?int
    {
        return match ($this->scopes->scopeType($requester)) {
            RecruitmentScopeType::DirectReports->value => $this->scopes->position($requester)?->getKey(),
            RecruitmentScopeType::City->value => $request->scope_city_id,
            default => null,
        };
    }
}
