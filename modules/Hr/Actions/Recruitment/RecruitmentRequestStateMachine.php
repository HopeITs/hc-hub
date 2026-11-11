<?php

namespace Modules\Hr\Actions\Recruitment;

use Illuminate\Validation\ValidationException;
use Modules\Hr\Enums\RecruitmentRequestMode as Mode;
use Modules\Hr\Enums\RecruitmentRequestStatus as Status;
use Modules\Hr\Models\RecruitmentRequest;

/**
 * The only place a request's status is allowed to change.
 *
 * Every move a request can make is named in the transition table of
 * 02-state-machine.md, and anything not named there is refused. Writing
 * `status` straight onto the model bypasses that table, which is how a request
 * ends up approved without ever having been reviewed, or revived after it was
 * rejected — so nothing outside this class writes the column.
 *
 * The transitions are keyed by their trigger rather than by the target status,
 * because more than one trigger can reach the same status from different
 * places and for different reasons: a request reaches `cancelled` from three
 * states, and only the requester's own act may take it there.
 */
class RecruitmentRequestStateMachine
{
    /**
     * The transition table, verbatim from the state machine document.
     *
     * Each entry is trigger => [allowed source statuses, target status]. T1 is
     * absent on purpose: it creates the row rather than moving one. T5 is
     * retired and its number is never reused.
     *
     * @var array<string, array{0: array<int, int>, 1: int}>
     */
    private const TRANSITIONS = [
        // T2 · T10 — the requester reworks what is still his to rework.
        'update' => [[Status::Draft->value, Status::ChangesRequested->value], null],

        // T3 · T11 — submit and resubmit are the same act on two states.
        'submit' => [[Status::Draft->value], Status::Submitted->value],
        'resubmit' => [[Status::ChangesRequested->value], Status::Resubmitted->value],

        // T4 · T13 — the system, once a workflow exists to review it.
        'workflow_started' => [[Status::Submitted->value], Status::UnderReview->value],
        'workflow_restarted' => [[Status::Resubmitted->value], Status::UnderReview->value],

        // T6 · T7 — the approver of the active step.
        'approve_step' => [[Status::UnderReview->value], Status::UnderReview->value],
        'approve_final' => [[Status::UnderReview->value], Status::Approved->value],

        // T8 · T9 — the two ways an approver hands it back (D60).
        'reject' => [[Status::UnderReview->value], Status::Rejected->value],
        'request_changes' => [[Status::UnderReview->value], Status::ChangesRequested->value],

        // T12 · T14 · T15 — the requester withdraws, never after approval.
        'cancel' => [[Status::Draft->value, Status::ChangesRequested->value, Status::UnderReview->value], Status::Cancelled->value],

        // T16 · T17 · T18 — applying the effect, and retrying a failure.
        // A retry that fails again stays failed rather than refusing the move:
        // the second failure is the same event as the first, and a request
        // that cannot record it would look like one that never failed.
        'apply_succeeded' => [[Status::Approved->value, Status::FailedApplication->value], Status::Applied->value],
        'apply_failed' => [[Status::Approved->value, Status::FailedApplication->value], Status::FailedApplication->value],

        // T19 · T20 — a direct opening skips draft, not submission. The
        // document draws both as coming from nowhere; in practice the request
        // is written submitted and the workflow service then decides which of
        // the two paths it takes, so Submitted is where they start from.
        'direct_open' => [[Status::Submitted->value], Status::Approved->value],
        'direct_open_pending' => [[Status::Submitted->value], Status::UnderReview->value],

        // T21 — HR Syria decides a governorate's direct opening.
        'direct_open_approved' => [[Status::UnderReview->value], Status::Approved->value],

        // T22 — revalidation failed on a direct opening: refused, not returned (D39).
        'direct_open_voided' => [[Status::UnderReview->value], Status::Rejected->value],
    ];

    /** Nothing leaves these. A new need is a new request. */
    private const FINAL = [Status::Rejected->value, Status::Cancelled->value, Status::Applied->value];

    /** Changes requested and resubmitted have no meaning on a direct opening. */
    private const NOT_FOR_DIRECT_HR = [Status::ChangesRequested->value, Status::Resubmitted->value];

    /**
     * Move the request, or refuse and leave it exactly as it was.
     *
     * The model is saved here rather than returned unsaved, so a caller cannot
     * take the new status and forget to persist it — or persist it without
     * having asked whether the move was allowed in the first place.
     */
    public function apply(RecruitmentRequest $request, string $trigger): RecruitmentRequest
    {
        $target = $this->resolve($request, $trigger);

        // An update keeps the request where it is; there is nothing to write.
        if ($target === null || $target === (int) $request->status) {
            return $request;
        }

        $request->forceFill(['status' => $target])->save();

        return $request;
    }

    /** May this request take this trigger right now? */
    public function can(RecruitmentRequest $request, string $trigger): bool
    {
        try {
            $this->resolve($request, $trigger);

            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    /**
     * Every trigger this request could take right now.
     *
     * The screens ask this rather than deciding for themselves which buttons a
     * status deserves, so the table stays the single answer.
     *
     * @return array<int, string>
     */
    public function available(RecruitmentRequest $request): array
    {
        return array_values(array_filter(
            array_keys(self::TRANSITIONS),
            fn (string $trigger): bool => $this->can($request, $trigger),
        ));
    }

    /**
     * The status this trigger leads to, or null when it stays put.
     *
     * @throws ValidationException when the move is not in the table
     */
    private function resolve(RecruitmentRequest $request, string $trigger): ?int
    {
        if (! array_key_exists($trigger, self::TRANSITIONS)) {
            throw $this->refuse('hr_recruitment.unknown_transition', ['trigger' => $trigger]);
        }

        [$from, $target] = self::TRANSITIONS[$trigger];
        $current = (int) $request->status;

        if (in_array($current, self::FINAL, true)) {
            throw $this->refuse('hr_recruitment.request_is_final');
        }

        if (! in_array($current, $from, true)) {
            throw $this->refuse('hr_recruitment.transition_not_allowed');
        }

        if ((int) $request->mode === Mode::DirectHr->value && in_array($target, self::NOT_FOR_DIRECT_HR, true)) {
            throw $this->refuse('hr_recruitment.not_for_direct_opening');
        }

        return $target;
    }

    private function refuse(string $message, array $replace = []): ValidationException
    {
        return ValidationException::withMessages(['request' => __($message, $replace)]);
    }
}
