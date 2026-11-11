<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Recruitment;

use Illuminate\Validation\ValidationException;
use Modules\Hr\Actions\Recruitment\RecruitmentRequestSubmissionService;
use Modules\Hr\Actions\Recruitment\RecruitmentWorkflowConfigurationException;
use Modules\Hr\Models\RecruitmentRequest;

/**
 * Submit (T3) or resubmit (T11) a request and say what came back. Shared by
 * the request page and the form's "save and submit", so the two report the
 * same thing: the service owns every rule, this only turns its answer into
 * words. Authorization is the caller's.
 *
 * Expects the host to use AuthorizesRecruitment (viewer(), toast()).
 */
trait SubmitsRecruitmentRequests
{
    /** Submit and toast the outcome; true when it went through. */
    protected function submitRecruitmentRequest(RecruitmentRequest $request, ?RecruitmentRequestSubmissionService $submissions = null): bool
    {
        $refusal = $this->attemptSubmission($request, $submissions);

        $this->toast($refusal ?? __('hr_recruitment.submitted'), $refusal === null);

        return $refusal === null;
    }

    /**
     * Null on success, otherwise every refusal as one sentence, so the
     * requester is not sent round the loop finding them one at a time.
     */
    protected function attemptSubmission(RecruitmentRequest $request, ?RecruitmentRequestSubmissionService $submissions = null): ?string
    {
        $submissions ??= app(RecruitmentRequestSubmissionService::class);

        try {
            $submissions->submit($this->viewer(), $request);
        } catch (ValidationException $e) {
            return collect($e->errors())->flatten()->unique()->values()->implode(' ');
        } catch (RecruitmentWorkflowConfigurationException $e) {
            // A set-up fault, not the requester's; the draft is untouched.
            report($e);

            return __('hr_recruitment.workflow_not_configured');
        }

        return null;
    }
}
