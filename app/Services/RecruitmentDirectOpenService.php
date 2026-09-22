<?php

namespace App\Services;

use App\Enums\ExternalConfirmationDecision;
use App\Enums\ExternalConfirmationMethod;
use App\Enums\RecruitmentEventType;
use App\Enums\RecruitmentRequestMode;
use App\Enums\RecruitmentRequestStatus as Status;
use App\Enums\RecruitmentRequestType as Type;
use App\Enums\RecruitmentScopeType;
use App\Enums\WorkflowStepKey;
use App\Models\Position;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Opening a request that was already agreed outside the system.
 *
 * Sometimes the manager, the financial approver and HR have settled a hire in
 * a meeting or over the phone before anybody opened a screen. Making them
 * repeat those decisions as electronic approvals would be theatre; leaving the
 * hire undocumented would be worse. This is the third way: the request is
 * recorded as already decided, and *how* it was decided is written down.
 *
 * Two rules keep this from becoming a hole in the workflow.
 *
 *   The confirmations are recorded as external, never as approvals. The
 *   timeline must never show that somebody pressed a button they did not
 *   press. Each of the three normal steps gets a line saying who confirmed it,
 *   by what means and when — or that it did not apply.
 *
 *   Who opens it decides whether it still needs a decision. HR Syria's own
 *   direct opening is final, because there is nobody above them to ask. A
 *   governorate's goes to HR Syria through a one-step workflow, so it shows up
 *   in their pending list like everything else and leaves the same trail.
 */
class RecruitmentDirectOpenService
{
    /** The three steps a normal request would have gone through. */
    private const CONFIRMABLE = [
        WorkflowStepKey::ManagerApproval,
        WorkflowStepKey::FinanceApproval,
        WorkflowStepKey::GovernorateHrApproval,
    ];

    public function __construct(
        private readonly RecruitmentRequestDraftService $drafts,
        private readonly RecruitmentRequestRevalidator $revalidator,
        private readonly RecruitmentWorkflowInstanceService $workflows,
        private readonly RecruitmentRequestStateMachine $states,
        private readonly RecruitmentScopeResolver $scopes,
    ) {}

    /**
     * Record an already-agreed hire and carry it to approved.
     *
     * The payload is the ordinary request form; `$documentation` is what makes
     * this a direct opening rather than a submission — who really asked for it,
     * why it bypassed the workflow, and the three confirmations.
     *
     * @param  array<string, mixed>  $payload  the request form
     * @param  array<string, mixed>  $documentation  actual requester, reason, confirmations
     */
    public function open(User $actor, array $payload, array $documentation): RecruitmentRequest
    {
        Gate::forUser($actor)->authorize('directOpen', RecruitmentRequest::class);

        $this->assertDocumented($documentation);

        $data = $this->drafts->validate($payload);

        $request = DB::transaction(function () use ($actor, $data, $documentation): RecruitmentRequest {
            $position = $this->targetWithinScope($actor, $data);

            $request = RecruitmentRequest::query()->create([
                'request_number' => 'RR-'.Str::ulid(),
                'requester_user_id' => $actor->getKey(),
                'request_type' => $data['request_type'],
                'mode' => RecruitmentRequestMode::DirectHr,
                'status' => Status::Submitted,
                'scope_city_id' => $this->cityOf($position, $data),
                'target_position_id' => $data['request_type'] === Type::CreatePosition ? null : $position->getKey(),
                'target_vacancy_id' => $data['vacancy_id'] ?? null,
                'draft_payload_json' => $data,
                'actual_requester_name' => $documentation['actual_requester_name'],
                'actual_requester_position_id' => $documentation['actual_requester_position_id'] ?? null,
                'direct_open_reason' => $documentation['direct_open_reason'],
                'direct_opened_by_user_id' => $actor->getKey(),
                'direct_opened_at' => now(),
                'submitted_at' => now(),
            ]);

            $request->events()->create([
                'event_type' => RecruitmentEventType::RequestCreated,
                'to_status' => Status::Submitted,
                'actor_user_id' => $actor->getKey(),
                'actor_role' => $actor->myRole() ?? 'system',
                'scope_type' => $this->scopes->scopeType($actor),
            ]);

            // The rules of the request type still hold: a direct opening may
            // shorten the approvals, never the facts.
            $this->revalidator->assert($request, $data);

            $version = $request->versions()->create([
                'version_no' => 1,
                'snapshot_json' => $data,
                'created_by_user_id' => $actor->getKey(),
                'submitted_at' => now(),
            ]);

            $this->recordConfirmations($actor, $request, $documentation['confirmations'] ?? []);

            // HR Syria's own opening completes here; a governorate's gets a
            // one-step workflow instance and waits on HR Syria (T19 · T20).
            $this->workflows->start($request->fresh(), $version);

            return $request->fresh();
        });

        return $request;
    }

    /**
     * The generated lines an opener is asked to fill in.
     *
     * Three, reflecting the path the request would otherwise have taken. HR
     * may mark any of them as not applicable — a closure has no financial
     * review to confirm — but may not leave one unanswered.
     *
     * @return array<int, int> step keys
     */
    public function confirmableSteps(): array
    {
        return self::CONFIRMABLE;
    }

    /**
     * The post being opened for, inside the opener's own governorate.
     *
     * Deliberately not the management tree that a normal request is scoped by:
     * HR does not manage the posts they recruit for, and the whole point of
     * this path is that somebody else already decided. What bounds it instead
     * is the governorate whose hiring this HR answers for — and for HR Syria,
     * nothing, because their scope is the organisation.
     *
     * @param  array<string, mixed>  $data
     */
    private function targetWithinScope(User $actor, array $data): Position
    {
        $isNewPosition = $data['request_type'] === Type::CreatePosition;
        $targetId = (int) ($isNewPosition ? $data['direct_manager_id'] : $data['position_id']);
        $field = 'data.'.($isNewPosition ? 'direct_manager_id' : 'position_id');

        $positions = Position::query()->whereKey($targetId)->lockForUpdate();

        if ($this->scopes->scopeType($actor) !== RecruitmentScopeType::Organization) {
            $cityIds = $this->scopes->governorateCityIds($actor);

            // scopeInCity is the single source of truth for city scoping, so
            // the governorate's several cities are ORed through it rather than
            // compared against city_id directly.
            $positions->where(function ($query) use ($cityIds): void {
                foreach ($cityIds as $cityId) {
                    $query->orWhere(fn ($inner) => $inner->inCity((int) $cityId));
                }
            });
        }

        $position = $positions->first();

        if ($position === null) {
            throw ValidationException::withMessages([
                $field => __('recruitment.direct_open.outside_scope'),
            ]);
        }

        return $position;
    }

    /**
     * The governorate the request belongs to.
     *
     * Taken from the form for a new post, since that post has no city yet, and
     * from the existing post otherwise.
     *
     * @param  array<string, mixed>  $data
     */
    private function cityOf(Position $position, array $data): int
    {
        return $data['request_type'] === Type::CreatePosition
            ? (int) $data['city_id']
            : (int) $position->city_id;
    }

    /**
     * Every generated line is answered, and the reasons are given.
     *
     * @param  array<string, mixed>  $documentation
     */
    private function assertDocumented(array $documentation): void
    {
        $errors = [];

        if (blank($documentation['actual_requester_name'] ?? null)) {
            $errors['actual_requester_name'] = __('recruitment.direct_open.requester_required');
        }

        if (blank($documentation['direct_open_reason'] ?? null)) {
            $errors['direct_open_reason'] = __('recruitment.direct_open.reason_required');
        }

        $confirmations = $documentation['confirmations'] ?? [];

        foreach (self::CONFIRMABLE as $stepKey) {
            $line = $confirmations[$stepKey] ?? [];
            $decision = (int) ($line['decision'] ?? 0);

            if (! in_array($decision, [ExternalConfirmationDecision::Approved, ExternalConfirmationDecision::NotApplicable], true)) {
                $errors["confirmations.{$stepKey}.decision"] = __('recruitment.direct_open.decision_required');

                continue;
            }

            if ($decision === ExternalConfirmationDecision::NotApplicable) {
                // Saying a step did not apply is a claim, and a claim needs a
                // reason: this is the one line nobody confirmed.
                if (blank($line['not_applicable_reason'] ?? null)) {
                    $errors["confirmations.{$stepKey}.not_applicable_reason"] = __('recruitment.direct_open.not_applicable_reason_required');
                }

                continue;
            }

            if (blank($line['confirmer_name'] ?? null)) {
                $errors["confirmations.{$stepKey}.confirmer_name"] = __('recruitment.direct_open.confirmer_required');
            }

            if (! in_array((int) ($line['method'] ?? 0), [
                ExternalConfirmationMethod::Phone, ExternalConfirmationMethod::Email,
                ExternalConfirmationMethod::Meeting, ExternalConfirmationMethod::Other,
            ], true)) {
                $errors["confirmations.{$stepKey}.method"] = __('recruitment.direct_open.method_required');
            }

            if (blank($line['confirmed_at'] ?? null)) {
                $errors["confirmations.{$stepKey}.confirmed_at"] = __('recruitment.direct_open.date_required');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Write the three lines, and say plainly what they are.
     *
     * `is_generated` marks them as the system's own three rather than
     * something HR added, and the event they raise is
     * ExternalConfirmationRecorded — never StepApproved. A reader of the
     * timeline must be able to tell a phone call from a click.
     *
     * @param  array<int, array<string, mixed>>  $confirmations
     */
    private function recordConfirmations(User $actor, RecruitmentRequest $request, array $confirmations): void
    {
        foreach (self::CONFIRMABLE as $stepKey) {
            $line = $confirmations[$stepKey] ?? [];
            $decision = (int) ($line['decision'] ?? 0);
            $notApplicable = $decision === ExternalConfirmationDecision::NotApplicable;

            $request->externalConfirmations()->create([
                'step_key' => $stepKey,
                'is_generated' => true,
                'confirmer_name' => $notApplicable ? null : $line['confirmer_name'],
                'confirmer_position_id' => $line['confirmer_position_id'] ?? null,
                'decision' => $decision,
                'method' => $notApplicable ? null : (int) $line['method'],
                'confirmed_at' => $notApplicable ? null : $line['confirmed_at'],
                'not_applicable_reason' => $notApplicable ? $line['not_applicable_reason'] : null,
                'notes' => $line['notes'] ?? null,
                'recorded_by_user_id' => $actor->getKey(),
            ]);

            $request->events()->create([
                'event_type' => RecruitmentEventType::ExternalConfirmationRecorded,
                'actor_user_id' => $actor->getKey(),
                // The role as it was, never looked up when the event is read.
                'actor_role' => $actor->myRole() ?? 'system',
                'scope_type' => $this->scopes->scopeType($actor),
                'meta' => array_filter([
                    'step_key' => $stepKey,
                    'decision' => $decision,
                    'confirmer_name' => $notApplicable ? null : $line['confirmer_name'],
                    'method' => $notApplicable ? null : (int) $line['method'],
                    'confirmed_at' => $notApplicable ? null : $line['confirmed_at'],
                    'not_applicable_reason' => $notApplicable ? $line['not_applicable_reason'] : null,
                ], fn ($value): bool => $value !== null),
            ]);
        }
    }
}
