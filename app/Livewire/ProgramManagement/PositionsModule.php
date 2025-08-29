<?php

namespace App\Livewire\ProgramManagement;

use App\Models\Center;
use App\Models\Position;
use App\Models\RequiredPosition;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\SortableTable;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class PositionsModule extends Component
{
    use WithPagination, SortableTable;

    public Center $center;
    public int $cityId;

    public $search = '';
    public $showModal = false;
    public $modalTitle = '';
    public $itemID = null;

    public $item = [
        'position_id' => '',
        'responsibilities'     => '',
        'max_emp'              => 0,
        'exist_emp'            => 0,
        'is_active'            => true,
    ];

    public function mount(Center $center, Request $request)
    {
        $this->center = $center;
        $this->cityId = (int)($request->query('city') ?? $center->city_id);
    }

    public function updatingSearch() { $this->resetPage(); }

    protected function rules()
    {
        // unique position_id within this center
        $unique = Rule::unique((new Position)->getTable(), 'position_id')
            ->where(fn($q) => $q->where('center_id', $this->center->id))
            ->ignore($this->itemID);

        return [
            'item.position_id' => ['required', 'integer', 'exists:' . (new RequiredPosition)->getTable() . ',id', $unique],
            'item.responsibilities'     => ['nullable', 'string', 'max:2000'],
            'item.max_emp'              => ['required', 'integer', 'min:0'],
            'item.exist_emp'            => ['required', 'integer', 'min:0'],
            'item.is_active'            => ['boolean'],
        ];
    }

    public function render()
    {
        $locale = app()->getLocale();

        $rows = Position::with(['requiredPosition', 'center'])
            ->where('center_id', $this->center->id)
            ->when($this->search, function ($q) use ($locale) {
                $term = "%{$this->search}%";
                $q->whereHas('requiredPosition', fn($w) => $w->where('title', 'like', $term))
                    ->orWhereHas('center', fn($w) => $w->where("name->$locale", 'like', $term));
            })
            ->orderBy($this->sortField ?: 'id', $this->sortDirection ?: 'desc')
            ->paginate(15);

        $taken = Position::where('center_id', $this->center->id)
            ->when($this->itemID, fn($q) => $q->where('id', '!=', $this->itemID))
            ->pluck('position_id');

        $availableRequired = RequiredPosition::where('program_id', $this->center->program_id)
            ->whereNotIn('id', $taken)
            ->orderBy('title')
            ->get(['id','title']);

        return view('livewire.program-management.positions-module', compact('rows', 'availableRequired', 'locale'));
    }

    public function create()
    {
        $this->itemID = null;
        $this->item = [
            'position_id' => '',
            'responsibilities'     => '',
            'max_emp'              => 0,
            'exist_emp'            => 0,
            'is_active'            => true,
        ];
        $this->modalTitle = __('general.add_new');
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $m = Position::where('center_id', $this->center->id)->findOrFail($id);
        $this->itemID = $m->id;
        $this->item = [
            'position_id' => $m->required_position_id,
            'responsibilities'     => $m->responsibilities,
            'max_emp'              => $m->max_emp,
            'exist_emp'            => $m->exist_emp,
            'is_active'            => (bool)$m->is_active,
        ];
        $this->modalTitle = __('general.edit');
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        // guard: required_position must belong to the same program as the center
        $rp = RequiredPosition::findOrFail($this->item['required_position_id']);
        if ($rp->program_id !== $this->center->program_id) {
            $this->addError('item.required_position_id', __('This role is not in the center’s program.'));
            return;
        }
        if ($this->item['exist_emp'] > $this->item['max_emp']) {
            $this->addError('item.exist_emp', __('Existing cannot exceed Max.'));
            return;
        }

        $model = $this->itemID
            ? Position::where('center_id', $this->center->id)->findOrFail($this->itemID)
            : new Position(['center_id' => $this->center->id]);

        $model->required_position_id = $this->item['required_position_id'];
        $model->responsibilities     = $this->item['responsibilities'];
        $model->max_emp              = $this->item['max_emp'];
        $model->exist_emp            = $this->item['exist_emp'];
        $model->is_active            = (bool)$this->item['is_active'];
        $model->save();

        $this->showModal = false;
        $this->resetErrorBag();
        $this->resetValidation();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }

    // Status toggle like your other modules
    public function confirmToggleItemStatus($id)
    {
        $this->itemID = $id;

        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->question()
            ->withConfirmButton(__('general.save'))
            ->onConfirm('toggleItemStatusConfirmed', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function toggleItemStatusConfirmed()
    {
        $item = Position::where('center_id', $this->center->id)->findOrFail($this->itemID);
        $item->is_active = ! $item->is_active;
        $item->save();

        LivewireAlert::title(__('general.item-updated'))
            ->success()->toast()->position('top-start')->show();
    }

    public function confirmDelete($id)
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete($payload)
    {
        Position::where('center_id', $this->center->id)->findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }
}
