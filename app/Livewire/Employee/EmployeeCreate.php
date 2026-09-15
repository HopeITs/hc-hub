<?php

namespace App\Livewire\Employee;

use App\Livewire\Forms\Employee\DraftContractForm;
use App\Livewire\Forms\Employee\PersonalInfoForm;
use App\Models\City;
use App\Models\Position;
use App\Traits\WithAlerts;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmployeeCreate extends Component
{
    use WithAlerts, WithFileUploads;

    public PersonalInfoForm $infoForm;

    public DraftContractForm $draftForm;

    public $activeTab = 'information';

    public $title = '';

    /**
     * Once personal info is saved, the tab shows the optional draft-position
     * step (see resources/views/livewire/employee/tabs/personal-info-tab.blade.php)
     * instead of redirecting away immediately, so this employee's city is
     * available to scope the position dropdown before the page navigates on.
     */
    public bool $showDraftPrompt = false;

    public function mount()
    {
        $this->title = __('general.add-new');
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function toggleBoardMember($checked): void
    {
        $this->infoForm->is_board_member = (bool) $checked;
        if (! $this->infoForm->is_board_member) {
            $this->infoForm->board_member_started_at = null;
            $this->infoForm->board_member_end_at = null;
        }
    }

    public function saveInformation()
    {
        $this->infoForm->saveInformation();
        $this->successToast();
        $this->showDraftPrompt = true;

        // saveInformation() is reachable from more than one tab's own Save
        // button (Profile and Contact share the same Form object and both
        // call this method) but the draft-position prompt only lives in the
        // Profile tab's view — force the user back there so it is always
        // reachable regardless of which tab they saved from.
        $this->activeTab = 'information';
    }

    /** Positions the draft-position dropdown may offer, scoped to the just-saved employee's city. */
    public function positionOptionsForDraft(): array
    {
        if (! $this->infoForm->employee) {
            return [];
        }

        return Position::query()
            ->inCity($this->infoForm->employee->city_id)
            ->visible()
            ->orderBy(transColumn('title'))
            ->get(['id', 'title_en', 'title_ar'])
            ->pluck('title', 'id')
            ->all();
    }

    public function createDraftAndContinue()
    {
        $this->draftForm->createDraft($this->infoForm->employee);
        $this->successToast();

        return $this->redirect(route('employees.edit', $this->infoForm->employee->id), navigate: true);
    }

    public function skipDraft()
    {
        return $this->redirect(route('employees.edit', $this->infoForm->employee->id), navigate: true);
    }

    public function render()
    {
        $cities = City::where('is_active', true)->where('code', '!=', '000')->pluck(transColumn('name'), 'id')->toArray();

        return view('livewire.employee.form', [
            'cities' => $cities,
            'isCreateMode' => true,
            'employeeChecklistItems' => collect(),
            'canManageBadges' => false,
        ]);
    }
}
