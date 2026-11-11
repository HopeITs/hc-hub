<?php

namespace Modules\Hr\Actions\Recruitment;

use Illuminate\Support\Collection;
use Modules\Hr\Enums\RecruitmentRequestStatus;
use Modules\Hr\Enums\RecruitmentRequestType;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\RecruitmentRequest;
use Modules\Hr\Models\Vacancy;

class RecruitmentPositionActionService
{
    /** @var list<int> */
    public const ACTIVE_REQUEST_STATUSES = [
        RecruitmentRequestStatus::Submitted->value,
        RecruitmentRequestStatus::UnderReview->value,
        RecruitmentRequestStatus::ChangesRequested->value,
        RecruitmentRequestStatus::Resubmitted->value,
    ];

    /** @var list<int> */
    public const DISPLAY_REQUEST_STATUSES = [
        ...self::ACTIVE_REQUEST_STATUSES,
        RecruitmentRequestStatus::Approved->value,
    ];

    /**
     * Return the actions that can be started for one position.
     *
     * The draft service remains the final authority when the user saves. An
     * action can become unavailable between rendering and clicking.
     *
     * @param  Collection<int, RecruitmentRequest>  $activeRequests
     * @param  Collection<int, Vacancy>  $openVacancies
     * @param  Collection<int, int>  $activeCampaignVacancyIds
     * @return array<string|int, array{available: bool, reason: ?string}>
     */
    public function actionsFor(
        Position $position,
        Collection $activeRequests,
        Collection $openVacancies,
        Collection $activeCampaignVacancyIds,
        bool $isRoot,
        bool $canCreatePosition,
    ): array {
        $hasPositionRequest = fn (int $type): bool => $activeRequests
            ->contains(fn ($request): bool => (int) $request->request_type === $type);

        $vacancyRequestExists = fn (int $vacancyId): bool => $activeRequests
            ->contains(fn ($request): bool => in_array((int) $request->request_type, [
                RecruitmentRequestType::FillVacancy->value,
                RecruitmentRequestType::CloseVacancy->value,
            ], true) && (($request->target_vacancy_id ?? $request->vacancy?->id) === null
                || (int) ($request->target_vacancy_id ?? $request->vacancy?->id) === $vacancyId));

        $fillConflictWithoutNamedVacancy = $activeRequests->contains(
            fn ($request): bool => in_array((int) $request->request_type, [
                RecruitmentRequestType::FillVacancy->value,
                RecruitmentRequestType::CloseVacancy->value,
            ], true) && ($request->target_vacancy_id ?? $request->vacancy?->id) === null,
        );

        $fillCanTargetSeat = ! $fillConflictWithoutNamedVacancy
            && ($openVacancies->isEmpty() || $openVacancies->contains(
                fn ($vacancy): bool => ! $vacancyRequestExists((int) $vacancy->getKey()),
            ));

        $closeVacancyCanTargetSeat = $openVacancies->contains(function ($vacancy) use ($activeCampaignVacancyIds, $vacancyRequestExists): bool {
            return ! $activeCampaignVacancyIds->contains((int) $vacancy->getKey())
                && ! $vacancyRequestExists((int) $vacancy->getKey());
        });

        $rootReason = __('hr_recruitment.team_staffing.root_not_targetable');
        $pendingReason = __('hr_recruitment.team_staffing.request_pending');

        return [
            RecruitmentRequestType::FillVacancy->value => [
                'available' => ! $isRoot && $position->is_active && $position->canAssign()
                    && $fillCanTargetSeat,
                'reason' => $isRoot ? $rootReason
                    : (! $position->is_active ? __('hr_recruitment.position_inactive')
                        : (! $position->canAssign() ? __('hr_recruitment.no_headcount_room')
                            : ($fillCanTargetSeat ? null : $pendingReason))),
            ],
            RecruitmentRequestType::IncreaseHeadcount->value => [
                'available' => ! $isRoot && $position->is_active
                    && ! $hasPositionRequest(RecruitmentRequestType::IncreaseHeadcount->value),
                'reason' => $isRoot ? $rootReason
                    : (! $position->is_active ? __('hr_recruitment.position_inactive')
                        : ($hasPositionRequest(RecruitmentRequestType::IncreaseHeadcount->value) ? $pendingReason : null)),
            ],
            RecruitmentRequestType::ClosePosition->value => [
                'available' => ! $isRoot && $position->is_active
                    && (int) $position->max_emp === 1
                    && (int) $position->exist_emp === 0
                    && ! $hasPositionRequest(RecruitmentRequestType::ClosePosition->value),
                'reason' => $isRoot ? $rootReason
                    : (! $position->is_active ? __('hr_recruitment.position_inactive')
                        : ((int) $position->max_emp !== 1
                            ? __('hr_recruitment.close_position_single_seat_only')
                            : ((int) $position->exist_emp > 0
                                ? __('hr_recruitment.position_has_contracts')
                                : ($hasPositionRequest(RecruitmentRequestType::ClosePosition->value) ? $pendingReason : null)))),
            ],
            RecruitmentRequestType::CloseVacancy->value => [
                'available' => ! $isRoot && $position->is_active
                    && (int) $position->max_emp > 1 && $closeVacancyCanTargetSeat,
                'reason' => $isRoot ? $rootReason
                    : (! $position->is_active ? __('hr_recruitment.position_inactive')
                        : ((int) $position->max_emp <= 1
                            ? __('hr_recruitment.close_vacancy_many_seats_only')
                            : ($openVacancies->isEmpty() ? __('hr_recruitment.team_staffing.no_open_vacancy')
                                : ($closeVacancyCanTargetSeat ? null : __('hr_recruitment.team_staffing.vacancy_action_blocked'))))),
            ],
            'create_position' => [
                'available' => $canCreatePosition,
                'reason' => $canCreatePosition ? null : __('hr_recruitment.team_staffing.parent_not_eligible'),
            ],
        ];
    }
}
