<?php

namespace Modules\Hr\Actions\Recruitment\Handlers;

use Illuminate\Validation\ValidationException;
use Modules\Hr\Enums\RecruitmentEventType;
use Modules\Hr\Enums\RecruitmentRequestType;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\RecruitmentRequest;

/**
 * Remove one unoccupied seat from the approved establishment.
 *
 * The post stays active and every occupied seat stays intact. Reducing
 * `max_emp` is what removes the vacant card from the organisation chart; an
 * open vacancy row is reduced or closed with it so the seat cannot still be
 * advertised after it no longer exists.
 */
class CloseVacancyHandler implements AppliesRecruitmentRequest
{
    use WritesVacancies;

    public function type(): int
    {
        return RecruitmentRequestType::CloseVacancy->value;
    }

    public function apply(RecruitmentRequest $request, array $snapshot): array
    {
        $position = Position::query()
            ->whereKey($request->target_position_id)
            ->lockForUpdate()
            ->firstOrFail();

        $before = (int) $position->max_emp;
        $after = $before - 1;

        if ($after < (int) $position->exist_emp) {
            throw ValidationException::withMessages([
                'request' => __('hr_recruitment.headcount_below_staff'),
            ]);
        }

        $position->forceFill(['max_emp' => $after])->save();

        $reason = (string) ($snapshot['reason'] ?? __('hr_recruitment.vacancy_closed'));
        $events = [[
            'event_type' => RecruitmentEventType::HeadcountChanged->value,
            'meta' => [
                'position_id' => $position->getKey(),
                'max_emp' => ['from' => $before, 'to' => $after],
                'reduced_by' => 1,
            ],
        ]];

        return array_merge($events, $this->reduceOpenSeats(
            (int) $position->getKey(),
            1,
            $reason,
        ));
    }
}
