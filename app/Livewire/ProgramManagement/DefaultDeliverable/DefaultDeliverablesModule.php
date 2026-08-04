<?php

namespace App\Livewire\ProgramManagement\DefaultDeliverable;

use App\Livewire\Concerns\AuthorizesActivePermissions;
use App\Models\ChecklistItem;
use App\Models\City;
use App\Models\DefaultDeliverable as DefaultDeliverableModel;
use App\Models\Employee;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class DefaultDeliverablesModule extends Component
{
    use AuthorizesActivePermissions;

    protected $listeners = ['openDefaultDeliverableEdit' => 'edit'];

    public bool $showModal = false;
    public string $modalTitle = '';

    public array $item = [
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


   public function render()
{
    $locale = app()->getLocale();

    $user = auth()->user()->load('profile');
    $role = $user->myRole();
    $roleArr = explode('-', $role);

    $cityCol = "name_{$locale}";

    $employeesQuery = Employee::whereHas('contracts', function ($query) {
        $query->whereHas('position', function ($subQuery) {
            $subQuery->whereHas('department', function ($q) {
                $q->whereIn('code', ['IT', 'CT', 'HR']);
            });
        });
    });

    $citiesQuery = City::whereHas('centers');

    if ($roleArr[0] === 'HR') {

        if ($roleArr[1] !== 'SYR') {
            // HR-City 

            $citySlug = $roleArr[1];

            $employees = $employeesQuery
                ->whereHas('city', function ($q) use ($citySlug) {
                    $q->where('slug', $citySlug);
                })
                ->orderBy(transColumn('first_name'))
                 ->get()
                ->mapWithKeys(fn($e) => [$e->id => $e->name]);

            $cities = $citiesQuery
                ->where('slug', $citySlug)
                ->get(['id', $cityCol])
                ->map(fn($c) => tap($c, fn() => $c->name = $c->{$cityCol}));

        } else {

            // HR-SYR 
            $employees = $employeesQuery
                ->orderBy(transColumn('first_name'))
                ->get()
                ->mapWithKeys(fn($e) => [$e->id => $e->name]); 

            $cities = $citiesQuery
                ->get(['id', $cityCol])
                ->map(fn($c) => tap($c, fn() => $c->name = $c->{$cityCol}));
        }

    } else {

        // SuperAdmin or GeneralAdmin 
        $employees = $employeesQuery
            ->orderBy(transColumn('first_name'))
            ->get()  
            ->mapWithKeys(fn($e) => [$e->id => $e->name]);

        $cities = $citiesQuery
            ->get(['id', $cityCol])
            ->map(fn($c) => tap($c, fn() => $c->name = $c->{$cityCol}));
    }

    $checklistItems = ChecklistItem::orderBy('order')->get(['id','name']);

    return view('livewire.program-management.default-deliverables-module', compact(
        'employees',
        'cities',
        'checklistItems'
    ));
}
 

    public function create(): void
    {
        $this->authorizeActivePermission('checklists.manage');

        $this->item = ['id'=>null,'employee_id'=>'','city_id'=>'','item_id'=>''];
        $this->resetValidation();
        $this->modalTitle = __('general.add-new');
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $this->authorizeActivePermission('checklists.manage');

        $model = DefaultDeliverableModel::findOrFail($id);

        $this->item = [
            'id'          => $model->id,
            'employee_id' => $model->employee_id,
            'city_id'     => $model->city_id,
            'item_id'     => $model->item_id,
        ];

        $this->resetValidation();
        $this->modalTitle = __('general.edit');
        $this->showModal  = true;
    }


public function save(): void
{
    $this->authorizeActivePermission('checklists.manage');

    $user = auth()->user();
    $roleArr = explode('-', $user->myRole());

    if ($roleArr[0] === 'HR') {

        if ($roleArr[1] !== 'SYR') {

            $citySlug = $roleArr[1];
            $allowedCity = City::where('slug', $citySlug)->first();
            if (!$allowedCity || $this->item['city_id'] != $allowedCity->id) {
                abort(403);
            }
        }
    }

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
    $this->dispatch('refreshDefaultDeliverablesTable');

    LivewireAlert::title(__('general.success-message'))
        ->success()
        ->toast()
        ->position('top-start')
        ->show();
}}
