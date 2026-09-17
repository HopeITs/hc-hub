<?php

namespace App\Services;

use App\Enums\RecruitmentScopeType;
use App\Models\City;
use App\Models\Position;
use App\Models\User;
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
 * RecruitmentScopeType::None, never "everything".
 */
class RecruitmentScopeResolver
{
    /**
     * The breadth of this user's access.
     *
     * Order matters: organization-wide roles win outright, then a governorate
     * HR role, then the org chart. Anyone left over sees nothing.
     */
    public function scopeType(User $user): int
    {
        if ($this->isOrganizationWide($user)) {
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
            ->all();
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
    // Resolving approvers
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
