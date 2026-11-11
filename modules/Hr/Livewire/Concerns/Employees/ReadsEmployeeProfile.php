<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Employees;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Hr\Models\Badge;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Course;
use Modules\Hr\Models\Education;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Experience;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\Relative;
use Modules\Hr\Support\Employees\HrAccess;
use Modules\Hr\Support\Employees\HrMedia;

/**
 * What an employee's profile page reads, and what the viewer may see of it.
 *
 * The access flags are worked out on the server on every request (they are
 * #[Locked] and recomputed in hydrate): self, HR in the employee's city, the
 * general manager, a direct manager, or simply a colleague.
 */
trait ReadsEmployeeProfile
{
    #[Locked]
    public int $employeeId;

    #[Locked]
    public bool $isSelf = false;

    /** Relatives, personal contact details and documents. */
    #[Locked]
    public bool $canViewPrivate = false;

    /** Pay figures on contracts and badges. */
    #[Locked]
    public bool $canViewFinancial = false;

    /** Change the record (HR in the employee's city). */
    #[Locked]
    public bool $canEdit = false;

    /** Education, experience, courses and the contract history. */
    #[Locked]
    public bool $canViewRecords = false;

    #[Locked]
    public bool $canViewBadges = false;

    #[Locked]
    public bool $canViewCard = false;

    #[Locked]
    public bool $canManageCard = false;

    public function hydrateReadsEmployeeProfile(): void
    {
        $this->resolveAccess();
    }

    #[Computed]
    public function employee(): Employee
    {
        return Employee::query()
            ->with('account:id,employee_id,email')
            ->withExists(['realContracts as has_real_contract'])
            ->findOrFail($this->employeeId);
    }

    /**
     * The contracts the viewer may see, newest first: the whole history for
     * someone who reads the records, only the running one otherwise. Pay
     * columns are selected only for HR.
     *
     * @return Collection<int, Contract>
     */
    #[Computed]
    public function contracts(): Collection
    {
        $columns = ['id', 'employee_id', 'position_id', 'type', 'start_date', 'end_date', 'terminated_at', 'termination_reason_id', 'is_active'];

        if ($this->canViewRecords) {
            array_push($columns, 'contract_type_id', 'weekly_hours', 'auto_renew');
        }

        if ($this->canViewFinancial) {
            array_push($columns, 'salary', 'currency', 'salary_per', 'pay_mode');
        }

        return Contract::query()
            ->select($columns)
            ->where('employee_id', $this->employeeId)
            ->unless($this->canViewRecords, fn ($query) => $query->activeNow()->whereNull('terminated_at'))
            ->with([
                'position:id,title_en,title_ar,program_id,center_id,city_id,department_id,job_level_id,direct_manager_id',
                'position.jobLevel:id,name_en,name_ar',
                ...($this->canViewRecords ? ['contractType:id,name_en,name_ar', 'terminationReason:id,title_en,title_ar'] : []),
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();
    }

    #[Computed]
    public function currentContract(): ?Contract
    {
        $today = today();

        return $this->contracts->first(fn (Contract $contract): bool => $contract->is_active
            && $contract->terminated_at === null
            && $contract->start_date !== null && $contract->start_date->lte($today)
            && $contract->end_date !== null && $contract->end_date->gte($today));
    }

    /**
     * Who the employee reports to: the holder of their position's direct
     * manager position.
     */
    #[Computed]
    public function directManager(): ?Employee
    {
        $managerPositionId = $this->currentContract?->position?->direct_manager_id;

        if ($managerPositionId === null) {
            return null;
        }

        return Employee::query()
            ->select(Employee::NameColumns)
            ->where('is_active', true)
            ->whereHas('contracts', fn ($contracts) => $contracts->where('position_id', $managerPositionId))
            ->first();
    }

    /**
     * @return Collection<int, Education>
     */
    #[Computed]
    public function educations(): Collection
    {
        return $this->canViewRecords ? $this->employee->educations()->orderByDesc('degree')->get() : new Collection;
    }

    /**
     * @return Collection<int, Experience>
     */
    #[Computed]
    public function experiences(): Collection
    {
        return $this->canViewRecords ? $this->employee->experiences()->orderByDesc('start_date')->get() : new Collection;
    }

    /**
     * @return Collection<int, Course>
     */
    #[Computed]
    public function courses(): Collection
    {
        return $this->canViewRecords
            ? $this->employee->courses()->with('category:id,name_en,name_ar')->orderByDesc('end_date')->get()
            : new Collection;
    }

    /**
     * @return Collection<int, Relative>
     */
    #[Computed]
    public function relatives(): Collection
    {
        return $this->canViewPrivate ? $this->employee->relatives()->orderBy('id')->get() : new Collection;
    }

    /**
     * The employee's uploaded documents (hr_media rows).
     *
     * @return SupportCollection<int, \stdClass>
     */
    #[Computed]
    public function attachments(): SupportCollection
    {
        return $this->canViewPrivate ? HrMedia::for(HrMedia::EMPLOYEE, $this->employeeId, 'attachments') : collect();
    }

    /**
     * The supporting file of each education, experience and course row, as
     * "kind:row id" => hr_media id.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function recordFiles(): array
    {
        if (! $this->canViewPrivate) {
            return [];
        }

        $files = [];

        foreach ([
            'education' => [HrMedia::EDUCATION, $this->educations],
            'experience' => [HrMedia::EXPERIENCE, $this->experiences],
            'course' => [HrMedia::COURSE, $this->courses],
        ] as $kind => [$type, $rows]) {
            foreach (HrMedia::for($type, $rows->modelKeys(), 'file') as $media) {
                $files[$kind.':'.$media->model_id] = (int) $media->id;
            }
        }

        return $files;
    }

    /**
     * @return Collection<int, Badge>
     */
    #[Computed]
    public function badges(): Collection
    {
        if (! $this->canViewBadges) {
            return new Collection;
        }

        return Badge::query()
            ->select($this->canViewFinancial
                ? ['id', 'employee_id', 'type', 'subtype', 'currency', 'date', 'reason', 'amount', 'new_position', 'leave_unit', 'current_salary', 'new_salary', 'notes']
                : ['id', 'employee_id', 'type', 'subtype', 'currency', 'date', 'reason', 'amount', 'new_position', 'leave_unit', 'notes'])
            ->where('employee_id', $this->employeeId)
            ->orderByDesc('date')
            ->get();
    }

    /**
     * The leave balances of the running contract, read only.
     *
     * @return Collection<int, LeaveBalance>
     */
    #[Computed]
    public function leaveBalances(): Collection
    {
        $contract = $this->currentContract;

        if ($contract === null || ! ($this->isSelf || $this->canViewRecords)) {
            return new Collection;
        }

        return LeaveBalance::query()
            ->select(['id', 'contract_id', 'type_id', 'deserved', 'used'])
            ->where('contract_id', $contract->id)
            ->with('leaveType:id,name_en,name_ar')
            ->orderBy('type_id')
            ->get();
    }

    /**
     * @return list<string>
     */
    public function tags(?string $value): array
    {
        if (! $value) {
            return [];
        }

        $decoded = json_decode($value, true);
        $tags = is_array($decoded) ? $decoded : explode(',', $value);

        return array_values(array_filter(array_map(fn (mixed $tag): string => trim((string) $tag), $tags)));
    }

    protected function resolveAccess(): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $employee = Employee::query()->select(['id', 'city_id', 'is_active'])->findOrFail($this->employeeId);
        $gate = Gate::forUser($user);

        abort_if($gate->denies('viewDirectory', $employee), 404);

        $this->isSelf = (int) $user->employee_id === (int) $employee->id;

        $isHrInScope = HrAccess::is($user, 'HR', 'super-admin') && HrAccess::canAccessCity($user, $employee->city_id);

        $this->canViewPrivate = $this->isSelf || $isHrInScope;
        $this->canViewFinancial = $isHrInScope && HrAccess::canViewSalary($user);
        $this->canEdit = $isHrInScope && $gate->allows('update', $employee);
        $this->canViewRecords = $gate->allows('viewRecords', $employee);
        $this->canViewBadges = $gate->allows('viewForEmployee', [Badge::class, $employee]);
        $this->canViewCard = $gate->allows('viewCard', $employee);
        $this->canManageCard = $gate->allows('manageCard', $employee);
    }
}
