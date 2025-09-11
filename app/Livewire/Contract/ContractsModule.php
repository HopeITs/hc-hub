<?php

namespace App\Livewire\Contract;

use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Employee;
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

    public string $search = '';
    public bool $showModal = false;
    public string $modalTitle = '';
    public $itemID;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    // (kept for inline modal; adjust if you still use it)
    public $item = [
        'id'               => null,
        'employee_id'      => '',
        'position_id'      => '',
        'contract_type_id' => '',
        'start_date'       => '',
        'end_date'         => '',
        'terminated_at'    => null,
        'reason_of_termination' => null,
        'yearly_leave_days'=> 12,
        'currency'         => 1,
        'salary'           => null,
        'salary_per'       => 1,
        'weekly_hours'     => null,
        'work_days'        => ['day' => ['from' => 'mon', 'to' => 'fri']],
        'work_hours'       => ['hour'=> ['from' => '09:00', 'to' => '17:00']],
        'is_active'        => true,
    ];

    protected $rules = [
        'item.employee_id'      => 'required|exists:employees,id',
        'item.position_id'      => 'required|exists:positions,id',
        'item.contract_type_id' => 'required|exists:contract_types,id',

        'item.start_date'       => 'required|date',
        'item.end_date'         => 'required|date|after_or_equal:item.start_date',
        'item.terminated_at'    => 'nullable|date|after_or_equal:item.start_date',

        'item.yearly_leave_days'=> 'nullable|integer|min:0|max:365',
        'item.currency'         => 'required|integer|in:0,1,2,3',
        'item.salary'           => 'required|numeric|min:0',
        'item.salary_per'       => 'required|integer|in:0,1,2,3',
        'item.weekly_hours'     => 'required|integer|min:0|max:168',
        'item.is_active'        => 'boolean',

        'item.work_days'        => 'required|array',
        'item.work_hours'       => 'required|array',
        'item.reason_of_termination' => 'nullable|string',
    ];

    public function updatingSearch() { $this->resetPage(); }

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
                LivewireAlert::title(__('general.error'))
                    ->text(__('This position has reached its capacity.'))
                    ->error()->toast()->position('top-start')->show();
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
        $sort   = $this->sortField ?: 'id';
        $dir    = $this->sortDirection ?: 'desc';

        $query = Contract::query()
            ->with([
                'employee',
                'position.center',
                'position.program',
                'position.department',
                'position.jobLevel',
                'contractType',
            ])
            // ⛔️ No city/center filtering at all
            ->when($this->search, function ($q) use ($locale) {
                $term = $this->search;
                $q->where(function ($w) use ($term, $locale) {
                    $w->orWhereHas('employee', function ($e) use ($term, $locale) {
                        $e->search(["name->$locale"], $term)
                            ->orWhere('personal_email', 'like', "%{$term}%");
                    })
                        ->orWhereHas('contractType', function ($t) use ($term, $locale) {
                            $t->search(["name->$locale"], $term);
                        })
                        ->orWhereHas('position.program', function ($p) use ($term, $locale) {
                            if (method_exists($p->getModel(), 'search')) {
                                $p->search(["name->$locale"], $term);
                            } else {
                                $p->where('name', 'like', "%{$term}%");
                            }
                        })
                        ->orWhereHas('position.department', function ($d) use ($term) {
                            $d->where('name', 'like', "%{$term}%");
                        })
                        ->orWhereHas('position.jobLevel', function ($l) use ($term, $locale) {
                            if (method_exists($l->getModel(), 'search')) {
                                $l->search(["name->$locale"], $term);
                            } else {
                                $l->where('name', 'like', "%{$term}%");
                            }
                        });
                });
            });

        if ($sort === 'employee_name') {
            $query->leftJoin('employees', 'contracts.employee_id', '=', 'employees.id')
                ->orderBy("employees.name->$locale", $dir)->select('contracts.*');
        } elseif ($sort === 'type_name') {
            $query->leftJoin('contract_types', 'contracts.contract_type_id', '=', 'contract_types.id')
                ->orderBy("contract_types.name->$locale", $dir)->select('contracts.*');
        } elseif (in_array($sort, ['start_date','end_date','salary','is_active','position_id','id'])) {
            $query->orderBy($sort, $dir);
        } elseif ($sort === 'period') {
            $query->orderBy('start_date', $dir)->orderBy('end_date', $dir);
        } else {
            $query->orderBy('id', 'desc');
        }

        $items = $query->paginate(15);

        // dropdown data (no city scoping)
        $employees = Employee::orderBy("name->$locale")->get(['id','name']);
        $positions = Position::orderBy('id')->get(['id','center_id']);
        $types     = ContractType::orderBy("name->$locale")->get(['id','name']);

        return view('livewire.contract.contracts-module', compact(
            'items','employees','positions','types'
        ));
    }

    // create/edit/save/delete left as-is from your version (no city logic involved)
    public function create()
    {
        $this->item = [
            'id'               => null,
            'employee_id'      => '',
            'position_id'      => '',
            'contract_type_id' => '',
            'start_date'       => '',
            'end_date'         => '',
            'terminated_at'    => null,
            'reason_of_termination' => null,
            'yearly_leave_days'=> 12,
            'currency'         => 1,
            'salary'           => null,
            'salary_per'       => 1,
            'weekly_hours'     => null,
            'work_days'        => ['day' => ['from' => 'mon', 'to' => 'fri']],
            'work_hours'       => ['hour'=> ['from' => '09:00','to' => '17:00']],
            'is_active'        => true,
        ];

        $this->resetValidation();
        $this->showModal  = true;
        $this->modalTitle = __('general.add-new');
    }

    public function edit($id)
    {
        $model = Contract::findOrFail($id);

        $this->modalTitle = __('general.edit');
        $this->item = [
            'id'               => $model->id,
            'employee_id'      => $model->employee_id,
            'position_id'      => $model->position_id,
            'contract_type_id' => $model->contract_type_id,
            'start_date'       => optional($model->start_date)->toDateString(),
            'end_date'         => optional($model->end_date)->toDateString(),
            'terminated_at'    => optional($model->terminated_at)->toDateString(),
            'reason_of_termination' => $model->reason_of_termination,
            'yearly_leave_days'=> $model->yearly_leave_days,
            'currency'         => $model->currency,
            'salary'           => $model->salary,
            'salary_per'       => $model->salary_per,
            'weekly_hours'     => $model->weekly_hours,
            'work_days'        => $model->work_days ?: ['day'=>['from'=>'mon','to'=>'fri']],
            'work_hours'       => $model->work_hours ?: ['hour'=>['from'=>'09:00','to'=>'17:00']],
            'is_active'        => (bool) $model->is_active,
        ];

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $id = $this->item['id'] ?? null;

        if (!empty($this->item['is_active'])) {
            $exists = Contract::where('employee_id', $this->item['employee_id'])
                ->where('is_active', true)
                ->when($id, fn($q) => $q->where('id','!=',$id))
                ->exists();
            if ($exists) {
                $this->addError('item.employee_id', __('This employee already has an active contract.'));
                return;
            }
        }

        $start = $this->item['start_date'];
        $end   = $this->item['end_date'] ?: $start;
        $overlap = Contract::where('employee_id', $this->item['employee_id'])
            ->when($id, fn($q) => $q->where('id','!=',$id))
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($qq) use ($start, $end) {
                        $qq->where('start_date', '<=', $start)
                            ->where(function ($qqq) use ($end) {
                                $qqq->whereNull('end_date')->orWhere('end_date', '>=', $end);
                            });
                    });
            })->exists();

        if ($overlap) {
            $this->addError('item.start_date', __('Contract dates overlap another contract for this employee.'));
            return;
        }

        DB::transaction(function () use ($id) {
            if ($id) {
                $model     = Contract::lockForUpdate()->findOrFail($id);
                $wasActive = (bool) $model->is_active;
                $oldPosId  = $model->position_id;

                $model->update($this->item);

                if ($wasActive && !$model->is_active) {
                    Position::where('id',$oldPosId)->decrement('exist_emp');
                } elseif (!$wasActive && $model->is_active) {
                    $pos = Position::lockForUpdate()->find($model->position_id);
                    if ($pos && $pos->exist_emp >= $pos->max_emp) {
                        throw new \RuntimeException(__('This position has reached its capacity.'));
                    }
                    $pos?->increment('exist_emp');
                } elseif ($model->is_active && $oldPosId != $model->position_id) {
                    Position::where('id',$oldPosId)->decrement('exist_emp');
                    Position::where('id',$model->position_id)->increment('exist_emp');
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

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
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

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }
}
