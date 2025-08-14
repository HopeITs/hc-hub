<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\SortableTable;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

use App\Models\DefaultDeliverable as DefaultDeliverableModel; // alias to avoid clash
use App\Models\Employee;
use App\Models\City;
use App\Models\ChecklistItem;

class DefaultDeliverablesModule extends Component
{
    use WithPagination, SortableTable;

    public $search = '';
    public $showModal = false;
    public $modalTitle = '';
    public $itemID;

    public $item = [
        'id'          => null,
        'employee_id' => '',
        'city_id'     => '',
        'item_id'     => '',
    ];

    protected function rules()
    {
        $emp  = $this->item['employee_id'] ?? null;
        $city = $this->item['city_id'] ?? null;

        $uniqueCombo = Rule::unique('default_deliverables', 'item_id')
            ->where(fn ($q) => $q->where('employee_id', $emp)
                ->where('city_id', $city));

        if (!empty($this->item['id'])) {
            $uniqueCombo->ignore($this->item['id']);
        }

        return [
            'item.employee_id' => ['required', 'exists:employees,id'],
            'item.city_id'     => ['required', 'exists:cities,id'],
            'item.item_id'     => ['required', 'exists:checklist_items,id', $uniqueCombo],
        ];
    }

    public function updatingSearch() { $this->resetPage(); }

    public function render()
    {
        $locale = app()->getLocale();
        $sort   = $this->sortField ?: 'id';
        $dir    = $this->sortDirection ?: 'desc';
        $term   = trim($this->search);
        $like   = '%'.$term.'%';

        $query = DefaultDeliverableModel::query()
            ->with(['employee','city','item'])
            ->when($term, function ($q) use ($like, $locale) {
                $q->where(function ($w) use ($like, $locale) {
                    $w->whereHas('employee', fn($e) => $e->where('name', 'like', $like))
                        ->orWhereHas('item',     fn($i) => $i->where('name', 'like', $like))
                        ->orWhereHas('city',     fn($c) => $c->where("name->$locale", 'like', $like));
                });
            });

        // Sorting (same approach as CentersModule)
        if ($sort === 'employee_name') {
            $query->leftJoin('employees as e', 'e.id', '=', 'default_deliverables.employee_id')
                ->orderBy('e.name', $dir)
                ->select('default_deliverables.*');
        } elseif ($sort === 'city_name') {
            $query->leftJoin('cities as c', 'c.id', '=', 'default_deliverables.city_id')
                ->orderBy("c.name->$locale", $dir)
                ->select('default_deliverables.*');
        } elseif ($sort === 'item_name') {
            $query->leftJoin('checklist_items as i', 'i.id', '=', 'default_deliverables.item_id')
                ->orderBy('i.name', $dir)
                ->select('default_deliverables.*');
        } else {
            $query->orderBy($sort, $dir);
        }

        $items = $query->paginate(15);

        $locale = app()->getLocale();

        $employees = Employee::get()->mapWithKeys(function ($employee) use ($locale) {
            return [$employee->id => $employee->getTranslation('name', $locale)];
        });
        $cities         = City::whereHas('centers')->orderBy("name->$locale")->get(['id','name']);
        $checklistItems = ChecklistItem::orderBy('order')->get(['id','name']);

        return view('livewire.default-deliverables-module', compact('items','employees','cities','checklistItems'));
    }

    public function create()
    {
        $this->item = ['id'=>null,'employee_id'=>'','city_id'=>'','item_id'=>''];
        $this->resetValidation();
        $this->showModal  = true;
        $this->modalTitle = __('general.add_new');
    }

    public function edit($id)
    {
        $model = DefaultDeliverableModel::findOrFail($id);

        $this->modalTitle = __('general.edit');
        $this->item = [
            'id'          => $model->id,
            'employee_id' => $model->employee_id,
            'city_id'     => $model->city_id,
            'item_id'     => $model->item_id,
        ];

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $model = !empty($this->item['id'])
            ? DefaultDeliverableModel::findOrFail($this->item['id'])
            : new DefaultDeliverableModel();

        $model->employee_id = $this->item['employee_id'];
        $model->city_id     = $this->item['city_id'];
        $model->item_id     = $this->item['item_id'];
        $model->save();

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
        DefaultDeliverableModel::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }
}
