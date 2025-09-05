<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeModule extends Component
{
    use WithPagination;

    protected $listeners = ['delete'];

    public function confirmDelete($id)
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->confirmButtonColor(config('samples.styles.colors.primary', '#ef4444'))
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete($data)
    {
        Employee::find($data['id'])?->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }

    public function render()
    {
        $items = Employee::with(['directManager', 'techManager','account'])
            ->select(['id', 'name_en', 'name_ar', 'years_of_service', 'direct_manager_id', 'tech_manager_id', 'actual_job', 'is_active'])->with('media')
            ->paginate(15);

        return view('livewire.employee.employee-module', [
            'items' => $items,
        ]);
    }
}
