<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EmployeeProfile extends Component
{
    public Employee $employee;

    #[Locked]
    public bool $isSelf = false;

    #[Locked]
    public bool $canViewPrivate = false;

    #[Locked]
    public bool $canViewFinancial = false;

    #[Locked]
    public bool $canEdit = false;

    #[Locked]
    public bool $canViewBadges = false;

    public function mount(?Employee $employee = null): void
    {
        $this->employee = $employee?->exists
            ? $employee
            : Auth::user()?->profile ?? abort(404);

        /** @var User|null $user */
        $user = Auth::user();
        abort_unless($user, 403);

        $this->isSelf = $user->employee_id === $this->employee->id;
        $isHr = $user->activeRoleIs('HR', 'super-admin');
        $isManagement = $user->activeRoleIs('general-manager');
        $activeCityId = $user->activeCityId();
        $isHrInScope = $isHr
            && ($activeCityId === null || $this->employee->city_id === $activeCityId);

        $this->canViewPrivate = $this->isSelf || $isHrInScope;

        // Narrower than $isHrInScope on purpose: $isHr above counts
        // super-admin as HR so a developer can open any profile, and pay is
        // the one thing that must not come with that. See User::canViewSalary.
        $this->canViewFinancial = $isHrInScope && $user->canViewSalary();
        $this->canEdit = $isHrInScope || $isManagement;
        $this->canViewBadges = $this->isSelf || $isHrInScope;
    }

    public function render()
    {
        $contractColumns = [
            'id', 'employee_id', 'position_id', 'contract_type_id',
            'termination_reason_id', 'start_date', 'end_date', 'terminated_at',
            'weekly_hours', 'salary_per', 'is_active',
        ];

        if ($this->canViewFinancial) {
            array_push($contractColumns, 'salary', 'currency');
        }

        $relations = [
            'account',
            'educations',
            'experiences',
            'courses.category',
            'allContracts' => fn ($query) => $query
                ->select($contractColumns)
                ->with([
                    'contractType',
                    'terminationReason',
                    'position.center',
                    'position.program',
                    'position.city',
                    'position.jobLevel',
                    'position.department',
                    'position.directManager.activeContracts.employee',
                    'position.techManager.activeContracts.employee',
                ])
                ->latest('start_date'),
        ];

        if ($this->canViewBadges) {
            $relations['badges'] = fn ($query) => $query
                ->select($this->canViewFinancial
                    ? ['id', 'employee_id', 'evaluation_id', 'currency', 'type', 'date', 'reason', 'current_salary', 'new_salary', 'notes']
                    : ['id', 'employee_id', 'evaluation_id', 'type', 'date', 'reason', 'notes'])
                ->latest('date');
        }

        $employee = $this->employee->load($relations);

        if ($this->canViewPrivate) {
            $employee->load('relatives');
        }

        $contracts = $employee->allContracts;
        $currentContract = $contracts->first(fn ($contract) => $contract->is_active
            && $contract->start_date?->lte(today())
            && $contract->end_date?->gte(today())
            && ! $contract->terminated_at);
        $currentPosition = $currentContract?->position;

        return view('livewire.employee.employee-profile', [
            'employee' => $employee,
            'contracts' => $contracts,
            'currentContract' => $currentContract,
            'currentPosition' => $currentPosition,
            'directManager' => $currentPosition?->directManager?->activeContracts?->first()?->employee,
            'techManager' => $currentPosition?->techManager?->activeContracts?->first()?->employee,
            'skills' => $this->parseTags($employee->skills),
            'interests' => $this->parseTags($employee->interests),
            'canViewPrivate' => $this->canViewPrivate,
            'canViewFinancial' => $this->canViewFinancial,
            'canEdit' => $this->canEdit,
            'canViewBadges' => $this->canViewBadges,
        ]);
    }

    private function parseTags(?string $value): array
    {
        if (! $value) {
            return [];
        }

        $decoded = json_decode($value, true);
        $tags = is_array($decoded) ? $decoded : explode(',', $value);

        return array_values(array_filter(array_map(
            fn ($tag) => trim((string) $tag),
            $tags,
        )));
    }
}
