<?php

namespace App\Services\Recruitment\Handlers;

use App\Enums\RecruitmentRequestType;
use App\Models\RecruitmentRequest;
use App\Models\Vacancy;
use Illuminate\Validation\ValidationException;

/**
 * Saying "not now" without saying "not ever".
 *
 * The narrowest of the six: one seat closes and nothing else moves. `max_emp`
 * is untouched and the post stays active, because the establishment still
 * approves this post — it simply is not being recruited for at the moment. A
 * later request can open the seat again without going back for the post.
 */
class CloseVacancyHandler implements AppliesRecruitmentRequest
{
    use WritesVacancies;

    public function type(): int
    {
        return RecruitmentRequestType::CloseVacancy;
    }

    public function apply(RecruitmentRequest $request, array $snapshot): array
    {
        $vacancy = Vacancy::query()
            ->whereKey($snapshot['vacancy_id'] ?? $request->target_vacancy_id)
            ->lockForUpdate()
            ->first();

        if ($vacancy === null) {
            throw ValidationException::withMessages([
                'request' => __('recruitment.vacancy_not_available'),
            ]);
        }

        $reason = (string) ($snapshot['reason'] ?? __('recruitment.vacancy_closed'));

        // Already closed is success, not failure: this is what makes running
        // the application twice harmless rather than an error to explain.
        if (! $this->closeVacancy($vacancy, $reason)) {
            return [];
        }

        return [$this->vacancyClosed($vacancy, $reason)];
    }
}
