<?php

namespace Modules\Hr\Actions\Recruitment;

use Illuminate\Validation\ValidationException;
use Modules\Hr\Enums\HiringCampaignStatus;
use Modules\Hr\Enums\RecruitmentRequestType as Type;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\RecruitmentRequest;
use Modules\Hr\Models\Vacancy;

/**
 * Re-runs the request type's rules against live data (RV-2 to RV-6).
 *
 * The world moves while a request waits for its approvers. The position can be
 * deactivated, the establishment filled, somebody hired into the seat being
 * given up, a campaign opened on the vacancy being closed. So the same checks
 * run twice: once when the request is submitted, and again inside the
 * transaction that approves it — on the values as they are at that moment,
 * never on the snapshot the requester saw weeks earlier.
 *
 * Rows are locked when read, so a check cannot pass against a value another
 * transaction is in the middle of changing.
 */
class RecruitmentRequestRevalidator
{
    /**
     * @throws ValidationException naming the field that no longer holds
     */
    public function assert(RecruitmentRequest $request, array $payload): void
    {
        $type = (int) $request->request_type;

        // A position that does not exist yet has nothing live to check.
        if ($type === Type::CreatePosition->value) {
            return;
        }

        $position = $request->target_position_id === null ? null
            : Position::query()->whereKey($request->target_position_id)->lockForUpdate()->first();

        // RV-6 — the subject stopped being a subject.
        if ($position !== null && ! $position->is_active) {
            $this->refuse('data.position_id', 'hr_recruitment.position_inactive');
        }

        if ($type === Type::FillVacancy->value && $position !== null && (int) $position->exist_emp >= (int) $position->max_emp) {
            $this->refuse('data.position_id', 'hr_recruitment.no_headcount_room');
        }

        // RV-4 — the reduced establishment may not fall below the people in it.
        if ($type === Type::DecreaseHeadcount->value && $position !== null) {
            $remaining = (int) $position->max_emp - (int) ($payload['reduce_by'] ?? 0);

            if ($remaining < (int) $position->exist_emp) {
                $this->refuse('data.reduce_by', 'hr_recruitment.below_existing_employees');
            }
        }

        if ($type === Type::CloseVacancy->value && $position !== null) {
            if ((int) $position->max_emp <= 1) {
                $this->refuse('data.position_id', 'hr_recruitment.close_vacancy_many_seats_only');
            }

            if ((int) $position->exist_emp >= (int) $position->max_emp) {
                $this->refuse('data.position_id', 'hr_recruitment.no_vacant_position');
            }

            $hasActiveCampaign = HiringCampaign::query()
                ->whereIn('status', [HiringCampaignStatus::Published->value, HiringCampaignStatus::Open->value])
                ->whereHas('vacancy', fn ($vacancies) => $vacancies
                    ->where('position_id', $position->getKey())
                    ->available())
                ->exists();

            if ($hasActiveCampaign) {
                $this->refuse('data.position_id', 'hr_recruitment.campaign_still_active');
            }
        }

        // RV-5 — a post with somebody in it is not closed out from under them.
        if ($type === Type::ClosePosition->value && $position !== null && (int) $position->max_emp !== 1) {
            $this->refuse('data.position_id', 'hr_recruitment.close_position_single_seat_only');
        }

        if ($type === Type::ClosePosition->value && $position !== null
            && ((int) $position->exist_emp > 0 || $position->activeContracts()->exists())) {
            $this->refuse('data.position_id', 'hr_recruitment.position_has_contracts');
        }

        $this->assertVacancyRules($request, $type);
    }

    /** True when every rule still holds, for callers that only need to ask. */
    public function passes(RecruitmentRequest $request, array $payload): bool
    {
        try {
            $this->assert($request, $payload);

            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    private function assertVacancyRules(RecruitmentRequest $request, int $type): void
    {
        if ($request->target_vacancy_id === null) {
            return;
        }

        // RV-6 again — a vacancy that closed, filled or expired while the
        // request waited. Locked as it is read, so it cannot change underneath.
        $stillOpen = Vacancy::query()
            ->available()
            ->whereKey($request->target_vacancy_id)
            ->lockForUpdate()
            ->exists();

        if (! $stillOpen) {
            $this->refuse('data.vacancy_id', 'hr_recruitment.invalid_vacancy');
        }

    }

    private function refuse(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => __($message)]);
    }
}
