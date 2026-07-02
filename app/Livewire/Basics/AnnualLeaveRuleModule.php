<?php

namespace App\Livewire\Basics;

use App\Livewire\Concerns\AuthorizesHrAction;
use App\Models\AnnualLeaveRule;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class AnnualLeaveRuleModule extends Component
{
    use AuthorizesHrAction;

    protected $listeners = ['openAnnualLeaveRuleEdit' => 'edit'];

    public bool $showModal = false;

    public string $modalTitle = '';

    public array $item = [
        'id' => null,
        'days' => null,
        'day_per_month' => null,
        'years_of_volunteering' => null,
    ];

    protected function rules(): array
    {
        return [
            'item.days' => 'required|integer',
            'item.day_per_month' => 'required|numeric',
            'item.years_of_volunteering' => 'required|integer',
        ];
    }

    public function render()
    {
        return view('livewire.basics.annual-leave-rule-module');
    }

    public function create(): void
    {
        $this->item = [
            'id' => null,
            'days' => null,
            'day_per_month' => null,
            'years_of_volunteering' => null,
        ];
        $this->resetValidation();
        $this->modalTitle = __('general.add-new');
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $model = AnnualLeaveRule::findOrFail($id);

        $this->item = [
            'id' => $model->id,
            'days' => $model->days,
            'day_per_month' => $model->day_per_month,
            'years_of_volunteering' => $model->years_of_volunteering,
        ];

        $this->modalTitle = __('general.edit');
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorizeHrAction();

        $this->validate();

        $model = ! empty($this->item['id'])
            ? AnnualLeaveRule::findOrFail($this->item['id'])
            : new AnnualLeaveRule;

        $model->fill($this->item)->save();

        $this->showModal = false;

        $this->dispatch('refreshAnnualLeaveRuleTable');

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }
}
