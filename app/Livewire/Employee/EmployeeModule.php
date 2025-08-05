<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class EmployeeModule extends Component
{
    use WithPagination;

    public $showModal = false;
    public $modalTitle = '';
    public $item = [];

    protected $rules = [
    'item.full_name' => 'required|string|max:255',
    'item.years_of_service' => 'nullable|integer',
    'item.direct_manager_id' => 'nullable|integer',
    'item.tech_manager_id' => 'nullable|integer',
    'item.national_number' => 'nullable|string',
    'item.dob' => 'nullable|date',
    'item.actual_job' => 'nullable|string',
    'item.civil_registry_office' => 'nullable|string',
    'item.civil_registry_number' => 'nullable|string',
    'item.place_of_registration' => 'nullable|string',
    'item.gender' => 'nullable|string',
    'item.father_name' => 'nullable|array',
    'item.mother_name' => 'nullable|array',
    'item.nationality' => 'nullable|string',
    'item.marital_status' => 'nullable|string',
    'item.children' => 'nullable|integer',
    'item.work_phone' => 'nullable|string',
    'item.personal_phone' => 'nullable|string',
    'item.personal_email' => 'nullable|email',
    'item.work_email' => 'required|email',
    'item.address' => 'nullable|array',
    'item.skills' => 'nullable|string',
    'item.interests' => 'nullable|string',
    'item.is_active' => 'boolean',
];

    public function create()
    {
        $this->reset('item');
        $this->item['is_active'] = true;
        $this->modalTitle = __('general.add_new');
        $this->showModal = true;
    }

public function edit($id)
{
    $model = Employee::findOrFail($id);
    $this->item = [
        'id' => $model->id,
        'full_name' => $model->getTranslation('name', app()->getLocale()),
        'years_of_service' => $model->years_of_service,
        'direct_manager_id' => $model->direct_manager_id,
        'tech_manager_id' => $model->tech_manager_id,
        'national_number' => $model->national_number,
        'dob' => $model->dob,
        'actual_job' => $model->actual_job,
        'civil_registry_office' => $model->civil_registry_office,
        'civil_registry_number' => $model->civil_registry_number,
        'place_of_registration' => $model->place_of_registration,
        'gender' => $model->gender,
        'father_name' => $model->getTranslations('father_name'),
        'mother_name' => $model->getTranslations('mother_name'),
        'nationality' => $model->nationality,
        'marital_status' => $model->marital_status,
        'children' => $model->children,
        'work_phone' => $model->work_phone,
        'personal_phone' => $model->personal_phone,
        'personal_email' => $model->personal_email,
        'work_email' => $model->work_email,
        'address' => $model->getTranslations('address'),
        'skills' => $model->skills,
        'interests' => $model->interests,
        'is_active' => $model->is_active,
        'job_level_id' => $model->job_level_id,
        'contract_type_id' => $model->contract_type_id,
        'department_id' => $model->department_id,
    ];

    $this->modalTitle = __('general.edit');
    $this->showModal = true;
}

    public function save()
    {
        $this->validate();

        if (isset($this->item['id'])) {
            Employee::findOrFail($this->item['id'])->update($this->item);
        } else {
            Employee::create($this->item);
        }

        $this->reset('item', 'showModal');

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
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

    public function delete($id)
    {
        Employee::findOrFail($id['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }

    public function render()
    {
        $items = Employee::with(['directManager', 'techManager', 'jobLevel', 'contractType', 'department'])->paginate(15);

        return view('livewire.employee.index', compact('items'));
    }
}
