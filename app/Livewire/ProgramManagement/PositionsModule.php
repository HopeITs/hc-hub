<?php

namespace App\Livewire\ProgramManagement;

use App\Models\Center;
use App\Models\Position;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\SortableTable;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class PositionsModule extends Component
{
    use WithPagination, SortableTable;





    public function render()
    {

        return view('livewire.position.index');
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
        $this->modalTitle = __('general.add-new');
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
