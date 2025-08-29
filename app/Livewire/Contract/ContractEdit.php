<?php

namespace App\Livewire\Contract;

use App\Livewire\Forms\ContractForm;
use App\Models\{Contract, Employee, Position, ContractType, Department, JobLevel, Program};
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class ContractEdit extends Component
{
    public ContractForm $form;
    public Contract $contract;

    public int $step = 1;
    public int $maxStep = 3;

    protected array $validatorsByStep = [
        1 => 'validateIdentity',
        2 => 'validateTerms',
        3 => 'validatePay',
    ];

    public function mount(Contract $contract): void
    {
        $this->contract = $contract;

        $this->form->setDefaults();
        $this->form->fillFromModel($contract);
    }

    protected function userIsHr(): bool
    {
        $user = auth()->user();
        $hrNames = ['HR', 'HR Manager'];

        if (method_exists($user, 'roles')) {
            return $user->roles()->whereIn('name', $hrNames)->exists();
        }

        return DB::table('role_user')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('role_user.user_id', $user->id)
            ->whereIn('roles.name', $hrNames)
            ->exists();
    }

    protected function userCityId(): ?int
    {
        return optional(auth()->user()->profile)->city_id;
    }

    public function next(): void
    {
        $method = $this->validatorsByStep[$this->step] ?? null;
        if ($method && method_exists($this->form, $method)) {
            $this->form->{$method}();
        }

        if ($this->step < 3) $this->step++;
    }

    public function prev(): void
    {
        if ($this->step > 1) $this->step--;
    }

    public function jumpTo(int $target): void
    {
        $target = max(1, min(3, $target));
        if ($target <= $this->maxStep) $this->step = $target;
    }

    public function submit()
    {
        $this->form->validateIdentity();
        $this->form->validateTerms();
        $this->form->validatePay();

        $this->form->update($this->contract);

        LivewireAlert::success(__('general.success-message'));
        return redirect()->route('contracts.index');
    }

    public function saveIdentity(): void {}
    public function saveTerms(): void {}
    public function savePay(): void {}
    public function save() { return $this->submit(); }

    public function render()
    {
        $locale = app()->getLocale();
        $isHr   = $this->userIsHr();
        $cityId = $this->userCityId();

        // Employees
        $employeesQuery = Employee::select('id', 'name')->orderBy("name->$locale");
        if ($isHr && $cityId) {
            $employeesQuery->where('city_id', $cityId);
        }
        $employees = $employeesQuery->get();

        $programsQuery = Program::select('id', 'name')->orderBy("name->$locale");
        $programs = $programsQuery->get();

        $positionsQuery = Position::with([
            'center:id,name',
            'requiredPosition:id,title',
        ])->orderBy('id')->select(['id','center_id','position_id']);

        if ($isHr && $cityId) {
            $positionsQuery->whereHas('center', fn($c) => $c->where('city_id', $cityId));
        }
        $positions = $positionsQuery->get();

        $types       = ContractType::select('id', 'name')->orderBy("name->$locale")->get();
        $departments = Department::select('id', 'name')->orderBy('name')->get();
        $levels      = JobLevel::select('id', 'name')->orderBy('id')->get();

        return view('livewire.contract.contract-edit', compact(
            'employees', 'programs', 'positions', 'types', 'departments', 'levels'
        ));
    }
}
