<?php

namespace App\Services;

use App\Enums\RecruitmentEventType;
use App\Enums\RecruitmentRequestMode;
use App\Enums\RecruitmentRequestStatus as Status;
use App\Models\RecruitmentRequest;

/**
 * Which fields the requester is allowed to touch, and when.
 *
 * A request is freely editable until it is first submitted. After that it is
 * closed: the approvers are deciding on a fixed document, and a requester who
 * can quietly reword it underneath them makes every approval mean something
 * other than what it said.
 *
 * The one way back in is an approver asking for changes, and that ask names
 * the fields. Reopening the whole form would invite edits nobody asked for and
 * force the workflow to re-examine them; reopening only what was asked keeps
 * the rest of the document exactly as it was approved.
 *
 * This is the authority for that rule. The edit screen disables what this says
 * is locked, but disabling a field in a browser is a courtesy, not a control —
 * every write goes through here as well.
 */
class RecruitmentEditableFields
{
    /** Everything is editable; the request has not been submitted yet. */
    public const ALL = null;

    /**
     * The fields this request currently accepts edits to.
     *
     * Returns null when everything is open. Returns a list — possibly empty —
     * when the request has been submitted at least once. An empty list means
     * locked outright, which is the normal state of a request under review.
     *
     * @return array<int, string>|null
     */
    public function for(RecruitmentRequest $request): ?array
    {
        // Never submitted: still the requester's own document.
        if ($request->submitted_at === null && ! $request->versions()->exists()) {
            return self::ALL;
        }

        // A direct opening that is still waiting for HR Syria is the same as a
        // draft: the opener may freely correct anything before the single step
        // is decided. Once the step is acted on, the request is frozen.
        if ((int) $request->mode === RecruitmentRequestMode::DirectHr
            && (int) $request->status === Status::UnderReview
            && $this->directOpenStepIsUndecided($request)) {
            return self::ALL;
        }

        if ((int) $request->status !== Status::ChangesRequested) {
            return [];
        }

        return $this->requestedByApprover($request);
    }

    /** May this request be edited at all right now? */
    public function anyEditable(RecruitmentRequest $request): bool
    {
        $fields = $this->for($request);

        return $fields === self::ALL || $fields !== [];
    }

    /** Is this one field open for editing? */
    public function allows(RecruitmentRequest $request, string $field): bool
    {
        $fields = $this->for($request);

        return $fields === self::ALL || in_array($field, $fields, true);
    }

    /**
     * The fields an approver may choose from when asking for changes.
     *
     * Taken from the request's own payload rather than a list written here,
     * because the fields differ by request type and a hard-coded list would
     * drift from the form the moment either changed. `request_type` is left
     * out: it is fixed once the request exists, since changing it would make
     * the rest of the document meaningless.
     *
     * @return array<int, string>
     */
    public function selectable(RecruitmentRequest $request): array
    {
        $payload = (array) ($request->draft_payload_json
            ?? $request->versions()->orderByDesc('version_no')->first()?->snapshot_json
            ?? []);

        return array_values(array_diff(array_keys($payload), ['request_type']));
    }

    /**
     * A direct opening's single workflow step has not been decided yet.
     *
     * Governorate HR's direct openings get a one-step workflow (HR Syria
     * approval). Until that step is acted on the opener may still edit.
     */
    private function directOpenStepIsUndecided(RecruitmentRequest $request): bool
    {
        $instance = $request->workflowInstances()->latest('id')->first();

        if (! $instance) {
            return true; // no workflow yet — still editable
        }

        return $instance->steps()->whereNull('decision')->exists();
    }

    /**
     * The fields named by the approver who last asked for changes.
     *
     * Read from the event rather than a column on the request: the ask belongs
     * to the decision that made it, and keeping it there means the audit trail
     * and the lock can never disagree about what was asked for.
     *
     * @return array<int, string>
     */
    public function requestedByApprover(RecruitmentRequest $request): array
    {
        $event = $request->events()
            ->where('event_type', RecruitmentEventType::ChangesRequested)
            ->latest('id')
            ->first();

        $fields = $event?->meta['requested_fields'] ?? [];

        return array_values(array_filter(
            is_array($fields) ? $fields : [],
            fn ($field): bool => is_string($field) && $field !== '',
        ));
    }
}
