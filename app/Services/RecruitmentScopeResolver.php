<?php

namespace App\Services;

use App\Enums\RecruitmentScopeType;
use App\Enums\WorkflowInstanceStatus;
use App\Enums\WorkflowStepKey;
use App\Models\City;
use App\Models\Contract;
use App\Models\Position;
use App\Models\RecruitmentRequest;
use App\Models\RecruitmentWorkflowInstanceStep;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Answers "where does this user reach, and who approves what" for recruitment.
 *
 * Authorization in this module is three separate questions, and all three are
 * asked every time:
 *
 *   permission  — from the user's active role: may they approve at all?
 *   eligibility — from their job level: may they raise a request at all?
 *   scope       — from their position: which requests are theirs to see?
 *
 * Nothing here reads a role name to decide *where* someone reaches, except for
 * the HR-{CITY} roles, which are themselves a statement of governorate. The
 * rest comes off the org chart, so a promotion or a transfer moves someone's
 * reach the same day without an administrator touching anything.
 *
 * The resolver deliberately fails closed: a user it cannot place gets
 * RecruitmentScopeType::None, and a query it cannot place returns nothing.
 */
class RecruitmentScopeResolver
{
    // --------------------------------------------
    // Breadth of access
    // --------------------------------------------

    /**
     * The breadth of this user's access.
     *
     * Order matters: organization-wide roles and the person appointed as the
     * central finance approver win outright, then a governorate HR role, then
     * the org chart. Anyone left over sees nothing.
     */
    public function scopeType(User $user): int
    {
        if ($this->isOrganizationWide($user)
            || $this->hasDirectPermission(
                $user,
                config('recruitment.user_level_permissions.finance_approver'),
            )) {
            return RecruitmentScopeType::Organization;
        }

        if ($this->governorateCityIds($user) !== []) {
            return RecruitmentScopeType::City;
        }

        if ($this->position($user) !== null) {
            return RecruitmentScopeType::DirectReports;
        }

        return RecruitmentScopeType::None;
    }

    /**
     * HR Syria, the general management and super-admin watch the whole
     * organization without being a step in any workflow.
     */
    public function isOrganizationWide(User $user): bool
    {
        return $user->activeRoleIs(...config('recruitment.organization_wide_roles', []));
    }

    /**
     * Every governorate an HR-{CITY} role reviews — its own and any assigned.
     *
     * A role's own city is not the whole answer. Governorates without an HR of
     * their own are assigned to another role, and the reviewer has to be able
     * to open and decide those requests, not merely be named on the step. HR
     * Latakia reviews Tartous, so Tartous is inside its scope.
     *
     * HR-SYR is excluded on purpose: it is organization-wide, and
     * User::activeCityId() already returns null for it.
     *
     * @return array<int, int>
     */
    public function governorateCityIds(User $user): array
    {
        $role = strtoupper(trim($user->myRole() ?? ''));

        if (! Str::startsWith($role, 'HR-') || $role === 'HR-SYR') {
            return [];
        }

        $ids = [];

        if (($own = $user->activeCityId()) !== null) {
            $ids[] = $own;
        }

        // Cities this role reviews on another governorate's behalf.
        $assigned = array_keys(array_filter(
            config('recruitment.governorate_hr.map', []),
            fn (string $reviewer): bool => strtoupper($reviewer) === $role,
        ));

        if ($assigned !== []) {
            $ids = array_merge($ids, City::query()
                ->whereIn('slug', $assigned)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all());
        }

        return array_values(array_unique($ids));
    }

    /**
     * The position this user currently occupies, through their active contract.
     *
     * Everything about where a manager reaches hangs off this. An employee
     * between contracts has no position and therefore no scope.
     */
    public function position(User $user): ?Position
    {
        return $user->profile?->latestActiveContract()?->position;
    }

    /**
     * Positions reporting directly to this user's position — one level only.
     *
     * A manager sees exactly what he is answerable for: the requests he is the
     * approver of. Going deeper would show him requests he never decides.
     *
     * @return array<int, int>
     */
    public function directReportPositionIds(User $user): array
    {
        $position = $this->position($user);

        if ($position === null) {
            return [];
        }

        return Position::query()
            ->where('direct_manager_id', $position->getKey())
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    // --------------------------------------------
    // Visibility
    // --------------------------------------------

    /**
     * The visibility boundary for requests — the single source of truth for
     * lists, exports, selectors and record lookup alike.
     *
     * Every caller goes through this. Applying scope to the list and forgetting
     * it on the detail screen is the classic way this kind of module leaks, so
     * canAccessRequest() below is this same query with a key on it rather than
     * a second set of rules that can drift.
     */
    public function scopeRequests(Builder $query, User $user): Builder
    {
        return match ($this->scopeType($user)) {
            RecruitmentScopeType::Organization => $query,

            // A governorate HR sees every request raised for the cities it
            // reviews, whoever raised them.
            RecruitmentScopeType::City => $query->whereIn(
                'scope_city_id',
                $this->governorateCityIds($user),
            ),

            // A manager sees his own requests and those of the people who
            // report to him — nothing wider, because nothing wider is his to
            // decide.
            RecruitmentScopeType::DirectReports => $query->whereIn(
                'requester_user_id',
                $this->visibleRequesterUserIds($user),
            ),

            // Fail closed. A user the resolver cannot place sees nothing, and
            // whereRaw('0 = 1') keeps that an empty result rather than an
            // unscoped one.
            default => $query->whereRaw('0 = 1'),
        };
    }

    public function canAccessRequest(User $user, RecruitmentRequest $request): bool
    {
        return $this->scopeRequests(RecruitmentRequest::query(), $user)
            ->whereKey($request->getKey())
            ->exists();
    }

    /**
     * Requests are the root of visibility; everything downstream inherits it.
     *
     * A vacancy exists because a request was approved, and a campaign exists
     * because of a vacancy. Re-deriving scope at each level would let the two
     * answers drift apart, so each one reaches back to the request instead.
     */
    public function scopeVacancies(Builder $query, User $user): Builder
    {
        return $query->whereHas(
            'createdFromRequest',
            fn (Builder $requests): Builder => $this->scopeRequests($requests, $user),
        );
    }

    public function scopeCampaigns(Builder $query, User $user): Builder
    {
        return $query->whereHas(
            'vacancy.createdFromRequest',
            fn (Builder $requests): Builder => $this->scopeRequests($requests, $user),
        );
    }

    public function scopeJobPosts(Builder $query, User $user): Builder
    {
        return $query->whereHas(
            'campaign.vacancy.createdFromRequest',
            fn (Builder $requests): Builder => $this->scopeRequests($requests, $user),
        );
    }

    public function scopeApplicationForms(Builder $query, User $user): Builder
    {
        return $query->whereHas(
            'campaign.vacancy.createdFromRequest',
            fn (Builder $requests): Builder => $this->scopeRequests($requests, $user),
        );
    }

    public function scopePublicLinks(Builder $query, User $user): Builder
    {
        return $query->whereHas(
            'campaign.vacancy.createdFromRequest',
            fn (Builder $requests): Builder => $this->scopeRequests($requests, $user),
        );
    }

    public function scopeRequestEvents(Builder $query, User $user): Builder
    {
        return $query->whereHas(
            'request',
            fn (Builder $requests): Builder => $this->scopeRequests($requests, $user),
        );
    }

    /**
     * This user plus whoever currently occupies a position reporting to them.
     *
     * Occupancy is read live rather than stored on the request, so someone who
     * transfers away takes their requests out of their old manager's list on
     * the day they move — the same rule that governs every other scope here.
     *
     * @return array<int, int>
     */
    public function visibleRequesterUserIds(User $user): array
    {
        $ids = [(int) $user->getKey()];

        $positionIds = $this->directReportPositionIds($user);

        if ($positionIds !== []) {
            $ids = array_merge($ids, User::query()
                ->whereIn('employee_id', Contract::query()
                    ->activeNow()
                    ->whereIn('position_id', $positionIds)
                    ->select('employee_id'))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all());
        }

        return array_values(array_unique($ids));
    }

    // --------------------------------------------
    // Raising a request
    // --------------------------------------------

    /**
     * May this user raise a recruitment request?
     *
     * Eligibility is the job level of the position they hold, never a role.
     * This is separate from the recruitment-requests.submit permission, and
     * both are required.
     */
    public function isEligibleRequester(User $user): bool
    {
        $position = $this->position($user);

        if ($position === null || ! $position->is_active) {
            return false;
        }

        $grades = config('recruitment.requester_job_level_grades', []);

        return in_array((int) $position->jobLevel?->grade, $grades, true);
    }

    /**
     * The position that approves this user's own requests.
     *
     * Returns null both for the top of the org chart and for a position whose
     * manager was never recorded — the two are told apart by
     * mayRequestWithoutManagerApproval(), never by this method alone. Treating
     * a missing manager as "no approval needed" would let a data-entry gap
     * skip a mandatory step.
     */
    public function approvingManagerPosition(User $user): ?Position
    {
        return $this->position($user)?->directManager;
    }

    /**
     * Is this user legitimately at the top of the org chart?
     *
     * Granted per user, never through a role — see
     * config/recruitment.php:user_level_permissions for why.
     */
    public function mayRequestWithoutManagerApproval(User $user): bool
    {
        return $this->hasDirectPermission(
            $user,
            config('recruitment.user_level_permissions.top_level_requester'),
        );
    }

    // --------------------------------------------
    // Resolving and checking approvers
    // --------------------------------------------

    /**
     * Users who may decide the financial step.
     *
     * The financial approver is a person, not a role: today whoever holds the
     * Senior Financial Manager position. Holding this permission *is* the
     * authorization for that step, which is why it is read from direct grants
     * only — a role carrying it would turn all its holders into approvers.
     *
     * @return array<int, int>
     */
    public function financeApproverUserIds(): array
    {
        return $this->userIdsWithDirectPermission(
            config('recruitment.user_level_permissions.finance_approver'),
        );
    }

    /**
     * The HR role that reviews requests for a governorate.
     *
     * Every city resolves to something: an explicit mapping first, then the
     * fallback. A request is never stranded because its governorate has no HR
     * of its own.
     */
    public function governorateHrRole(?int $cityId): string
    {
        $map = config('recruitment.governorate_hr.map', []);
        $default = config('recruitment.governorate_hr.default', 'HR-SYR');

        if ($cityId === null) {
            return $default;
        }

        $slug = strtoupper((string) City::query()->whereKey($cityId)->value('slug'));

        if ($slug === '') {
            return $default;
        }

        if (isset($map[$slug])) {
            return $map[$slug];
        }

        // A governorate with its own HR role reviews its own requests.
        $own = "HR-{$slug}";

        return $this->roleHasHolders($own) ? $own : $default;
    }

    /**
     * May this user decide this step, right now?
     *
     * Holding the permission is only the first of four questions, and it is
     * the only one an administrator role bypasses (D10). The other three are
     * asked of everyone, which is what stops HR Syria — who holds every
     * permission in the system — from deciding a step that is somebody else's,
     * or approving a request they raised themselves.
     *
     * The permission itself is checked by the policy, not here.
     */
    public function canActOnStep(User $user, RecruitmentWorkflowInstanceStep $step): bool
    {
        $step->loadMissing(['workflowInstance', 'workflowStep']);

        // 2. Is the step still open?
        if ($step->decision !== null || $step->assigned_user_id === null) {
            return false;
        }

        if ((int) $step->workflowInstance?->status !== WorkflowInstanceStatus::Running
            || (int) $step->workflowStep?->step_order !== (int) $step->workflowInstance?->current_step_order) {
            return false;
        }

        // 3. Is this the assigned approver?
        if ((int) $step->assigned_user_id !== (int) $user->getKey()) {
            return false;
        }

        // The finance approver is a directly granted person, not an
        // organization-wide role. Re-resolve the direct grant at action time
        // so losing it immediately removes the ability to decide.
        if ((int) $step->workflowStep?->step_key === WorkflowStepKey::FinanceApproval) {
            return in_array((int) $user->getKey(), $this->financeApproverUserIds(), true);
        }

        // 4. Do they still hold the scope they were assigned under? Someone who
        //    transferred out of a governorate stops being able to decide its
        //    requests, even though the step still carries their name.
        return $this->holdsScope(
            $user,
            $step->assigned_scope_type === null ? null : (int) $step->assigned_scope_type,
            $step->assigned_scope_id === null ? null : (int) $step->assigned_scope_id,
        );
    }

    /**
     * Does this user still sit inside the scope a step was assigned under?
     *
     * Unknown or missing scope is a no. The absence of a scope is not a
     * complete one.
     */
    public function holdsScope(User $user, ?int $scopeType, ?int $scopeId): bool
    {
        return match ($scopeType) {
            RecruitmentScopeType::Organization => $this->isOrganizationWide($user),

            RecruitmentScopeType::City => $scopeId !== null
                && ($this->isOrganizationWide($user)
                    || in_array($scopeId, $this->governorateCityIds($user), true)),

            // The scope of a manager step is the manager's own position.
            RecruitmentScopeType::DirectReports => $scopeId !== null
                && (int) ($this->position($user)?->getKey() ?? 0) === $scopeId,

            default => false,
        };
    }

    // --------------------------------------------
    // Internals
    // --------------------------------------------

    /**
     * Direct grants only — Spatie's hasPermissionTo() would also match
     * permissions inherited from a role, which is exactly what these two
     * person-level permissions must not do.
     */
    private function hasDirectPermission(User $user, ?string $permission): bool
    {
        if ($permission === null) {
            return false;
        }

        return $user->permissions()->where('name', $permission)->exists();
    }

    /**
     * @return array<int, int>
     */
    private function userIdsWithDirectPermission(?string $permission): array
    {
        if ($permission === null) {
            return [];
        }

        return DB::table('model_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
            ->where('permissions.name', $permission)
            ->where('model_has_permissions.model_type', User::class)
            ->pluck('model_has_permissions.model_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function roleHasHolders(string $roleName): bool
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', $roleName)
            ->exists();
    }
}
