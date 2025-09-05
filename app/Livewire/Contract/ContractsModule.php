<?php

namespace App\Livewire\Contract;

use App\Models\Center;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobLevel;
use App\Models\Position;
use App\Traits\SortableTable;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ContractsModule extends Component
{
    use WithPagination, SortableTable;

    protected $listeners = ['delete', 'toggleItemStatusConfirmed'];

    public string|int $centerId = 'all';
    public string $search = '';
    public bool $showModal = false;
    public string $modalTitle = '';
    public $itemID;

    protected $queryString = [
        'centerId' => ['except' => 'all'],
        'search' => ['except' => ''],
    ];

    public $item = [
        'id' => null,
        'employee_id' => '',
        'position_id' => '',
        'contract_type_id' => '',
        'department_id' => '',
        'job_level_id' => '',
        'start_date' => '',
        'end_date' => '',
        'terminated_at' => null,
        'reason_of_termination' => null,
        'yearly_leave_days' => 12,
        'currency' => 1,
        'salary' => null,
        'salary_per' => 1,
        'weekly_hours' => null,
        'work_days' => ['day' => ['from' => 'mon', 'to' => 'fri']],
        'work_hours' => ['hour' => ['from' => '09:00', 'to' => '17:00']],
        'is_active' => true,
    ];

    protected $rules = [
        'item.employee_id' => 'required|exists:employees,id',
        'item.position_id' => 'required|exists:positions,id',
        'item.contract_type_id' => 'required|exists:contract_types,id',
        'item.department_id' => 'required|exists:departments,id',
        'item.job_level_id' => 'required|exists:job_levels,id',

        'item.start_date' => 'required|date',
        'item.end_date' => 'required|date|after_or_equal:item.start_date',
        'item.terminated_at' => 'nullable|date|after_or_equal:item.start_date',

        'item.yearly_leave_days' => 'nullable|integer|min:0|max:365',
        'item.currency' => 'required|integer|in:0,1,2,3',
        'item.salary' => 'required|numeric|min:0',
        'item.salary_per' => 'required|integer|in:0,1,2,3',
        'item.weekly_hours' => 'required|integer|min:0|max:168',
        'item.is_active' => 'boolean',

        'item.work_days' => 'required|array',
        'item.work_hours' => 'required|array',
        'item.reason_of_termination' => 'nullable|string',
    ];

    protected function userIsHr(): bool
    {
        $hrNames = ['HR', 'HR Manager'];
        $user = auth()->user();

        if (method_exists($user, 'roles')) {
            return $user->roles()->whereIn('name', $hrNames)->exists();
        }

        return DB::table('role_user')->join('roles', 'roles.id', '=', 'role_user.role_id')->where('role_user.user_id', $user->id)->whereIn('roles.name', $hrNames)->exists();
    }

    protected function userCityId(): ?int
    {
        return optional(auth()->user()->profile)->city_id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function setCenter(string|int $id): void
    {
        $this->centerId = $id === 'all' ? 'all' : (int) $id;
        $this->resetPage();
    }

    public function confirmToggleItemStatus($id)
    {
        $this->itemID = $id;

        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->question()
            ->confirmButtonColor(config('samples.styles.colors.confirm'))
            ->withConfirmButton(__('general.save'))
            ->onConfirm('toggleItemStatusConfirmed', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function toggleItemStatusConfirmed()
    {
        $contract = Contract::with('position')->findOrFail($this->itemID);

        if ($contract->is_active) {
            DB::transaction(function () use ($contract) {
                $contract->update([
                    'is_active' => false,
                    'terminated_at' => $contract->terminated_at ?: now()->toDateString(),
                ]);
                $contract->position()->lockForUpdate()->first()?->decrement('exist_emp');
            });

            LivewireAlert::title(__('general.item-updated'))->success()->toast()->position('top-start')->show();
            return;
        }

        DB::transaction(function () use ($contract) {
            $pos = $contract->position()->lockForUpdate()->first();
            if ($pos && $pos->exist_emp >= $pos->max_emp) {
                LivewireAlert::title(__('general.error'))->text(__('This position has reached its capacity.'))->error()->toast()->position('top-start')->show();
                return;
            }
            $contract->update(['is_active' => true]);
            $pos?->increment('exist_emp');

            LivewireAlert::title(__('general.item-updated'))->success()->toast()->position('top-start')->show();
        });
    }

    public function render()
    {
        $locale = app()->getLocale();
        $sort = $this->sortField ?: 'id';
        $dir = $this->sortDirection ?: 'desc';
        $like = '%' . $this->search . '%';

        $query = Contract::query()
            ->with(['employee', 'position.center', 'contractType', 'department', 'jobLevel'])

            ->when($this->userIsHr() && $this->userCityId(), function ($q) {
                $q->whereHas('position.center', function ($c) {
                    $c->where('city_id', $this->userCityId());
                });
            })

            ->when($this->centerId !== 'all', function ($q) {
                $q->whereHas('position', function ($p) {
                    $p->where('center_id', $this->centerId);
                });
            })

            ->when($this->search, function ($q) use ($like, $locale) {
                $q->where(function ($w) use ($like, $locale) {
                    $w->orWhereHas('employee', function ($e) use ($like) {
                        $e->where('name_en', 'like', $like)->orWhere('name_ar', 'like', $like)->orWhere('personal_email', 'like', $like);
                    })
                        ->orWhereHas('department', function ($d) use ($like) {
                            $d->where('name', 'like', $like);
                        })
                        ->orWhereHas('contractType', function ($t) use ($like, $locale) {
                            $t->where('name_' . $locale, 'like', $like);
                        })
                        ->orWhereHas('jobLevel', function ($l) use ($like, $locale) {
                            $l->where('name_' . $locale, 'like', $like);
                        });
                });
            });

        if ($sort === 'employee_name') {
            $query
                ->leftJoin('employees', 'contracts.employee_id', '=', 'employees.id')
                ->orderBy("employees.name_$locale", $dir)
                ->select('contracts.*');
        } elseif ($sort === 'type_name') {
            $query
                ->leftJoin('contract_types', 'contracts.contract_type_id', '=', 'contract_types.id')
                ->orderBy("contract_types.name_$locale", $dir)
                ->select('contracts.*');
        } elseif ($sort === 'dept_name') {
            $query->leftJoin('departments', 'contracts.department_id', '=', 'departments.id')->orderBy('departments.name', $dir)->select('contracts.*');
        } elseif ($sort === 'level_name') {
            $query
                ->leftJoin('job_levels', 'contracts.job_level_id', '=', 'job_levels.id')
                ->orderBy("job_levels.name_$locale", $dir)
                ->select('contracts.*');
        } elseif (in_array($sort, ['start_date', 'end_date', 'salary', 'is_active', 'position_id', 'id'])) {
            $query->orderBy($sort, $dir);
        } elseif ($sort === 'period') {
            $query->orderBy('start_date', $dir)->orderBy('end_date', $dir);
        } else {
            $query->orderBy('id', 'desc');
        }

        $items = $query->paginate(15);

        $employeesQuery = Employee::orderBy("name_$locale")->select(['id', "name_$locale as name", 'city_id']);
        $positionsQuery = Position::with('center:id,city_id')
            ->orderBy('id')
            ->select(['id', 'center_id', "title_$locale as title"]);

        if ($this->userIsHr() && $this->userCityId()) {
            $employeesQuery->where('city_id', $this->userCityId());
            $positionsQuery->whereHas('center', fn($c) => $c->where('city_id', $this->userCityId()));
        }

        $employees = $employeesQuery->get();
        $positions = $positionsQuery->get();
        $types = ContractType::orderBy("name_$locale")
            ->select('id', "name_$locale as name")
            ->get();
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $levels = JobLevel::orderBy('grade')
            ->select('id', "name_$locale as name")
            ->get();

        $centers = collect();
        if ($this->userIsHr() && $this->userCityId()) {
            $centers = Center::where('city_id', $this->userCityId())
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('livewire.contract.contracts-module', compact('items', 'employees', 'positions', 'types', 'departments', 'levels', 'centers'));
    }

    public function create()
    {
        $this->item = [
            'id' => null,
            'employee_id' => '',
            'position_id' => '',
            'contract_type_id' => '',
            'department_id' => '',
            'job_level_id' => '',
            'start_date' => '',
            'end_date' => '',
            'terminated_at' => null,
            'reason_of_termination' => null,
            'yearly_leave_days' => 12,
            'currency' => 1,
            'salary' => null,
            'weekly_hours' => null,
            'work_days' => ['day' => ['from' => 'mon', 'to' => 'fri']],
            'work_hours' => ['hour' => ['from' => '09:00', 'to' => '17:00']],
            'is_active' => true,
        ];

        $this->resetValidation();
        $this->showModal = true;
        $this->modalTitle = __('general.add-new');
    }

    public function edit($id)
    {
        $model = Contract::findOrFail($id);

        $this->modalTitle = __('general.edit');
        $this->item = [
            'id' => $model->id,
            'employee_id' => $model->employee_id,
            'position_id' => $model->position_id,
            'contract_type_id' => $model->contract_type_id,
            'department_id' => $model->department_id,
            'job_level_id' => $model->job_level_id,
            'start_date' => optional($model->start_date)->toDateString(),
            'end_date' => optional($model->end_date)->toDateString(),
            'terminated_at' => optional($model->terminated_at)->toDateString(),
            'reason_of_termination' => $model->reason_of_termination,
            'yearly_leave_days' => $model->yearly_leave_days,
            'currency' => $model->currency,
            'salary' => $model->salary,
            'salary_per' => $model->salary_per,
            'weekly_hours' => $model->weekly_hours,
            'work_days' => $model->work_days ?: ['day' => ['from' => 'mon', 'to' => 'fri']],
            'work_hours' => $model->work_hours ?: ['hour' => ['from' => '09:00', 'to' => '17:00']],
            'is_active' => (bool) $model->is_active,
        ];

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $id = $this->item['id'] ?? null;

        if (!empty($this->item['is_active'])) {
            $exists = Contract::where('employee_id', $this->item['employee_id'])->where('is_active', true)->when($id, fn($q) => $q->where('id', '!=', $id))->exists();
            if ($exists) {
                $this->addError('item.employee_id', __('This employee already has an active contract.'));
                return;
            }
        }

        $start = $this->item['start_date'];
        $end = $this->item['end_date'] ?: $start;
        $overlap = Contract::where('employee_id', $this->item['employee_id'])
            ->when($id, fn($q) => $q->where('id', '!=', $id))
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($qq) use ($start, $end) {
                        $qq->where('start_date', '<=', $start)->where(function ($qqq) use ($end) {
                            $qqq->whereNull('end_date')->orWhere('end_date', '>=', $end);
                        });
                    });
            })
            ->exists();

        if ($overlap) {
            $this->addError('item.start_date', __('Contract dates overlap another contract for this employee.'));
            return;
        }

        DB::transaction(function () use ($id) {
            if ($id) {
                $model = Contract::lockForUpdate()->findOrFail($id);
                $wasActive = (bool) $model->is_active;
                $oldPosId = $model->position_id;

                $model->update($this->item);

                if ($wasActive && !$model->is_active) {
                    Position::where('id', $oldPosId)->decrement('exist_emp');
                } elseif (!$wasActive && $model->is_active) {
                    $pos = Position::lockForUpdate()->find($model->position_id);
                    if ($pos && $pos->exist_emp >= $pos->max_emp) {
                        throw new \RuntimeException(__('This position has reached its capacity.'));
                    }
                    $pos?->increment('exist_emp');
                } elseif ($model->is_active && $oldPosId != $model->position_id) {
                    Position::where('id', $oldPosId)->decrement('exist_emp');
                    Position::where('id', $model->position_id)->increment('exist_emp');
                }
            } else {
                $pos = Position::lockForUpdate()->find($this->item['position_id']);
                if (($this->item['is_active'] ?? false) && $pos && $pos->exist_emp >= $pos->max_emp) {
                    $this->addError('item.position_id', __('This position has reached its capacity.'));
                    throw new \RuntimeException('capacity');
                }
                $model = Contract::create($this->item);
                if ($model->is_active) {
                    $pos?->increment('exist_emp');
                }
            }
        });

        $this->showModal = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('closeModal');

        LivewireAlert::title(__('general.success-message'))->success()->toast()->position('top-start')->show();
    }

    public function confirmDelete($id)
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->confirmButtonColor(config('samples.styles.colors.primary'))
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete($payload)
    {
        $model = Contract::findOrFail($payload['id']);

        DB::transaction(function () use ($model) {
            if ($model->is_active) {
                Position::where('id', $model->position_id)->decrement('exist_emp');
            }
            $model->delete();
        });

        LivewireAlert::title(__('general.success-message'))->success()->toast()->position('top-start')->show();
    }
}
