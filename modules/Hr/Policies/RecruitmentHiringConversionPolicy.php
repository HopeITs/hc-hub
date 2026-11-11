<?php

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentHiringConversionService;
use Modules\Hr\Models\RecruitmentHiringConversion;
use Modules\Hr\Models\RecruitmentJobOffer;
use Modules\Hr\Support\Recruitment\Access;

/**
 * Phase 2 Task 10.
 *
 * - view: whoever may see the offer (view-offers, in recruitment scope).
 * - convert: the permission of its own (convert-hires), plus what HR needs to
 *   create a contract by hand (contracts.create) and to write a salary into
 *   one (User::canViewSalary() — ContractForm refuses a new contract to a
 *   role that may not see pay, and super-admin is salary-blind by design),
 *   the offer in scope, and the position in the city HR manages employees in
 *   (as EmployeePolicy::create). Creating the employee or linking an existing
 *   one is authorized again by the service, against EmployeePolicy.
 */
class RecruitmentHiringConversionPolicy
{
    public function __construct(private readonly RecruitmentHiringConversionService $conversions) {}

    public function view(User $user, RecruitmentHiringConversion $conversion): bool
    {
        $offer = $this->offer($conversion);

        return $offer !== null && $user->can('view', $offer);
    }

    public function convert(User $user, RecruitmentHiringConversion $conversion): bool
    {
        $offer = $this->offer($conversion);

        if ($offer === null
            || ! Access::hasActivePermission($user, 'recruitment-applicants.convert-hires')
            || ! Access::hasActivePermission($user, 'contracts.create')
            || ! Access::canViewSalary($user)
            || ! $user->can('view', $offer)) {
            return false;
        }

        $cityId = Access::activeCityId($user);

        return $cityId === null
            || ($offer->position !== null && $this->conversions->workCityId($offer->position) === $cityId);
    }

    private function offer(RecruitmentHiringConversion $conversion): ?RecruitmentJobOffer
    {
        return $conversion->relationLoaded('jobOffer')
            ? $conversion->jobOffer
            : $conversion->jobOffer()->with('position')->first();
    }
}
