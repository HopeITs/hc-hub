<?php

namespace App\Services;

use App\Enums\RecruitmentScopeType;
use App\Models\Position;
use App\Models\RecruitmentRequest;
use App\Models\RecruitmentWorkflowInstanceStep;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class RecruitmentScopeResolver
{
    /**
     * Apply the complete request visibility boundary. This method is the
     * single source of truth for lists, exports, selectors, and record lookup.
     */
    public function scopeRequests(Builder $query, User $user): Builder
    {
        if ($this->isOrganizationWide($user)) {
            return $query;
        }

        $context = $this->context($user);

        return $query->where(function (Builder $visible) use ($context, $user): void {
            // Ownership never implies approval rights; it only permits a
            // requester to see their own record.
            $visible->where('requester_user_id', $user->getKey())
                ->orWhereHas('workflowInstances.steps', function (Builder $steps) use ($context, $user): void {
                    $steps->where('assigned_user_id', $user->getKey())
                        ->whereNull('decision')
                        ->where(function (Builder $assignedScope) use ($context, $user): void {
                            $assignedScope->where(function (Builder $userScope) use ($user): void {
                                $userScope->where('assigned_scope_type', RecruitmentScopeType::User->value)
                                    ->where('assigned_scope_id', $user->getKey());
                            });

                            $this->addAssignedStepScopeConditions($assignedScope, $context);
                        });
                });

            if ($context['city']->isNotEmpty()) {
                $visible->orWhereIn('scope_city_id', $context['city']->all());
            }

            if ($this->hasStructuralScope($context)) {
                $visible->orWhereHas('latestVersion.positionDetail', function (Builder $detail) use ($context): void {
                    $detail->where(function (Builder $scope) use ($context): void {
                        $this->addStructuralConditions($scope, $context);
                    });
                });
            }
        });
    }

    public function canAccessRequest(User $user, RecruitmentRequest $request): bool
    {
        return $this->scopeRequests(RecruitmentRequest::query(), $user)
            ->whereKey($request->getKey())
            ->exists();
    }

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
     * A null city is allowed only while opening an empty create screen. Every
     * persistence path must call this again with the submitted scope city.
     */
    public function canCreateInCity(User $user, ?int $cityId): bool
    {
        if ($cityId === null || $this->isOrganizationWide($user)) {
            return true;
        }

        $activeCityId = $user->activeCityId();

        if ($activeCityId !== null) {
            return $activeCityId === $cityId;
        }

        $context = $this->context($user);

        if ($context['city']->contains($cityId)) {
            return true;
        }

        // Ordinary requesters have no structural read scope, but may create a
        // request for the city recorded on their own employee profile.
        return (int) ($user->profile?->city_id ?? 0) === $cityId;
    }

    /**
     * Request creation is restricted by role as well as by permission. This
     * prevents a direct permission grant to an ordinary role from expanding
     * the set of people allowed to open recruitment requests.
     */
    public function canCreateRequests(User $user): bool
    {
        $activeRole = $this->normalizeRole($user->myRole());
        $allowedRoles = collect(config('recruitment.request_creator_roles', []))
            ->map(fn (string $role): string => $this->normalizeRole($role));

        if ($allowedRoles->contains($activeRole)) {
            return true;
        }

        // Only a regional HR role that resolves to a configured city is valid.
        return Str::startsWith($activeRole, 'hr-')
            && $user->activeCityId() !== null;
    }

    /**
     * Approval is intentionally stricter than visibility. The actor must still
     * be the resolved assignee, still operate under the assigned role, and
     * still belong to the stored scope at the moment the action is submitted.
     */
    public function canActOnStep(User $user, RecruitmentWorkflowInstanceStep $step): bool
    {
        if ($step->decision !== null || $step->assigned_user_id === null) {
            return false;
        }

        if ((int) $step->assigned_user_id !== (int) $user->getKey()) {
            return false;
        }

        if (! $this->roleMatches($user, (string) $step->assigned_role)) {
            return false;
        }

        return $this->allowsScope(
            $user,
            $step->assigned_scope_type,
            $step->assigned_scope_id === null ? null : (int) $step->assigned_scope_id,
        );
    }

    public function allowsScope(User $user, ?string $scopeType, ?int $scopeId): bool
    {
        $type = RecruitmentScopeType::normalize($scopeType);

        if ($type === null) {
            return false;
        }

        if ($type === RecruitmentScopeType::Organization) {
            return $this->isOrganizationWide($user);
        }

        if ($scopeId === null) {
            return false;
        }

        if ($type === RecruitmentScopeType::User) {
            return $scopeId === (int) $user->getKey();
        }

        if ($this->isOrganizationWide($user)) {
            return true;
        }

        $context = $this->context($user);
        $key = match ($type) {
            RecruitmentScopeType::City, RecruitmentScopeType::Governorate => 'city',
            RecruitmentScopeType::Program, RecruitmentScopeType::Project => 'program',
            RecruitmentScopeType::Department => 'department',
            RecruitmentScopeType::Center => 'center',
            RecruitmentScopeType::Position => 'position',
            default => null,
        };

        return $key !== null && $context[$key]->contains($scopeId);
    }

    public function isOrganizationWide(User $user): bool
    {
        $activeRole = $this->normalizeRole($user->myRole());

        return collect(config('recruitment.organization_roles', []))
            ->map(fn (string $role): string => $this->normalizeRole($role))
            ->contains($activeRole);
    }

    private function roleMatches(User $user, string $assignedRole): bool
    {
        $active = $this->normalizeRole($user->myRole());
        $assigned = $this->normalizeRole($assignedRole);

        if ($active === '' || $assigned === '') {
            return false;
        }

        if ($active === $assigned) {
            return true;
        }

        if ($assigned === 'hr' && Str::startsWith($active, 'hr-')) {
            return true;
        }

        return $assigned === 'hr-director'
            && in_array($active, ['hr-syr', 'hr-director'], true);
    }

    /**
     * @return array{city: Collection<int, int>, program: Collection<int, int>, department: Collection<int, int>, center: Collection<int, int>, position: Collection<int, int>}
     */
    private function context(User $user): array
    {
        $context = $this->emptyContext();
        $activeRole = $this->normalizeRole($user->myRole());

        // Regional HR scope is encoded in the active role and resolved through
        // User::activeCityId(). A missing/unknown suffix produces no scope.
        if (Str::startsWith($activeRole, 'hr-')) {
            $cityId = $user->activeCityId();

            if ($cityId !== null) {
                $context['city']->push($cityId);
            }

            return $context;
        }

        $roleScopes = collect(config('recruitment.structural_role_scopes', []))
            ->first(fn (array $scopes, string $role): bool => $this->normalizeRole($role) === $activeRole);

        if ($roleScopes === null || $user->employee_id === null) {
            return $context;
        }

        $positions = Position::query()
            ->select(['id', 'program_id', 'department_id', 'center_id', 'city_id'])
            ->with('center:id,city_id')
            ->whereHas('contracts', fn (Builder $contracts): Builder => $contracts
                ->activeNow()
                ->where('employee_id', $user->employee_id))
            ->get();

        $allowed = collect($roleScopes);

        if ($allowed->contains(fn (string $scope): bool => in_array($scope, ['program', 'project'], true))) {
            $context['program'] = $positions->pluck('program_id')->filter()->map(fn ($id): int => (int) $id)->unique()->values();
        }

        if ($allowed->contains('department')) {
            $context['department'] = $positions->pluck('department_id')->filter()->map(fn ($id): int => (int) $id)->unique()->values();
        }

        if ($allowed->contains('center')) {
            $context['center'] = $positions->pluck('center_id')->filter()->map(fn ($id): int => (int) $id)->unique()->values();
        }

        if ($allowed->contains('position')) {
            $context['position'] = $positions->pluck('id')->map(fn ($id): int => (int) $id)->unique()->values();
        }

        if ($allowed->contains('city')) {
            $context['city'] = $positions
                ->flatMap(fn (Position $position): array => [$position->city_id, $position->center?->city_id])
                ->filter()
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values();
        }

        return $context;
    }

    /** @return array{city: Collection<int, int>, program: Collection<int, int>, department: Collection<int, int>, center: Collection<int, int>, position: Collection<int, int>} */
    private function emptyContext(): array
    {
        return [
            'city' => collect(),
            'program' => collect(),
            'department' => collect(),
            'center' => collect(),
            'position' => collect(),
        ];
    }

    /** @param array{city: Collection, program: Collection, department: Collection, center: Collection, position: Collection} $context */
    private function hasStructuralScope(array $context): bool
    {
        return $context['program']->isNotEmpty()
            || $context['department']->isNotEmpty()
            || $context['center']->isNotEmpty()
            || $context['position']->isNotEmpty();
    }

    /** @param array{city: Collection, program: Collection, department: Collection, center: Collection, position: Collection} $context */
    private function addStructuralConditions(Builder $scope, array $context): void
    {
        $first = true;

        foreach (['program', 'department', 'center', 'position'] as $type) {
            if ($context[$type]->isEmpty()) {
                continue;
            }

            $method = $first ? 'whereIn' : 'orWhereIn';
            $scope->{$method}("{$type}_id", $context[$type]->all());
            $first = false;
        }
    }

    /** @param array{city: Collection, program: Collection, department: Collection, center: Collection, position: Collection} $context */
    private function addAssignedStepScopeConditions(Builder $scope, array $context): void
    {
        $scopeTypes = [
            'city' => [RecruitmentScopeType::City->value, RecruitmentScopeType::Governorate->value, 'area', 'region'],
            'program' => [RecruitmentScopeType::Program->value, RecruitmentScopeType::Project->value],
            'department' => [RecruitmentScopeType::Department->value],
            'center' => [RecruitmentScopeType::Center->value, 'centre'],
            'position' => [RecruitmentScopeType::Position->value],
        ];

        foreach ($scopeTypes as $contextKey => $types) {
            if ($context[$contextKey]->isEmpty()) {
                continue;
            }

            $scope->orWhere(function (Builder $assigned) use ($context, $contextKey, $types): void {
                $assigned->whereIn('assigned_scope_type', $types)
                    ->whereIn('assigned_scope_id', $context[$contextKey]->all());
            });
        }
    }

    private function normalizeRole(?string $role): string
    {
        return Str::of((string) $role)
            ->trim()
            ->lower()
            ->replace('_', '-')
            ->toString();
    }
}
