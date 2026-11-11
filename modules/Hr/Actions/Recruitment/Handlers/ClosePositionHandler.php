<?php

namespace Modules\Hr\Actions\Recruitment\Handlers;

use Illuminate\Validation\ValidationException;
use Modules\Hr\Enums\RecruitmentEventType;
use Modules\Hr\Enums\RecruitmentRequestType;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\RecruitmentRequest;

/**
 * Retiring a post from the organisation chart.
 *
 * A post with somebody in it is never closed out from under them. RV-5 says
 * so at the final approval and it is said again here, because a closure may
 * have waited weeks on its effective date and somebody may have been hired in
 * the meantime.
 */
class ClosePositionHandler implements AppliesRecruitmentRequest
{
    use WritesVacancies;

    public function type(): int
    {
        return RecruitmentRequestType::ClosePosition->value;
    }

    public function apply(RecruitmentRequest $request, array $snapshot): array
    {
        $position = Position::query()
            ->whereKey($request->target_position_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ((int) $position->exist_emp > 0 || $position->activeContracts()->exists()) {
            throw ValidationException::withMessages([
                'request' => __('hr_recruitment.position_has_active_contracts'),
            ]);
        }

        $wasActive = (bool) $position->is_active;
        $position->forceFill(['is_active' => false])->save();

        $events = [[
            'event_type' => RecruitmentEventType::PositionChanged->value,
            'meta' => [
                'position_id' => $position->getKey(),
                'is_active' => ['from' => $wasActive, 'to' => false],
                'closed' => true,
            ],
        ]];

        // Every seat goes with the post. Leaving one open would keep the post
        // advertisable after it stopped existing.
        $reason = (string) ($snapshot['reason'] ?? __('hr_recruitment.position_closed'));

        foreach ($this->openVacanciesForClosing((int) $position->getKey()) as $vacancy) {
            if ($this->closeVacancy($vacancy, $reason)) {
                $events[] = $this->vacancyClosed($vacancy, $reason);
            }
        }

        return $events;
    }
}
