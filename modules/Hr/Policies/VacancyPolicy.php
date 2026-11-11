<?php

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Models\RecruitmentRequest;
use Modules\Hr\Models\Vacancy;
use Modules\Hr\Support\Recruitment\Access;

class VacancyPolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function viewAny(User $user): bool
    {
        return Access::hasActivePermission($user, 'recruitment-vacancies.view');
    }

    public function view(User $user, Vacancy $vacancy): bool
    {
        return Access::hasActivePermission($user, 'recruitment-vacancies.view') && $this->inScope($user, $vacancy);
    }

    public function create(User $user, RecruitmentRequest $request): bool
    {
        return Access::hasActivePermission($user, 'recruitment-vacancies.manage')
            && $this->scopes->canAccessRequest($user, $request);
    }

    public function manage(User $user, Vacancy $vacancy): bool
    {
        return Access::hasActivePermission($user, 'recruitment-vacancies.manage') && $this->inScope($user, $vacancy);
    }

    public function update(User $user, Vacancy $vacancy): bool
    {
        return $this->manage($user, $vacancy);
    }

    public function delete(User $user, Vacancy $vacancy): bool
    {
        return $this->manage($user, $vacancy);
    }

    public function close(User $user, Vacancy $vacancy): bool
    {
        return Access::hasActivePermission($user, 'recruitment-vacancies.manage') && $this->inScope($user, $vacancy);
    }

    private function inScope(User $user, Vacancy $vacancy): bool
    {
        $request = $vacancy->createdFromRequest()->first();

        return $request !== null && $this->scopes->canAccessRequest($user, $request);
    }
}
