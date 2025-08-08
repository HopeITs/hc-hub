<?php

namespace App\Livewire\ProjectManagement;

use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProjectModule extends Component
{

    use WithPagination, WithFileUploads;

    public $showModal = false;
    public $showDetailsModal = false;
    public $modalTitle = '';

    public Project $project;

    public $item = [
        'name' => '',
        'description' => '',
        'is_active' => true,
        'start_date' => '',
        'end_date' => null,
        'logo' => ''

    ];

    protected function rules()
    {
        return [

            'item.name.*' => 'required',
            'item.description.*' => 'nullable',
            'item.is_active' => 'required|boolean:0,1',
            'item.logo' => 'nullable|image|max:2048',
            'item.start_date' => 'required|date',
            'item.end_date' => 'nullable|date|after:item.start_date',
        ];
    }

    public $itemID;

    public function confirmToggleItemStatus($id)
    {
        $this->itemID = $id;

        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->question()
            ->confirmButtonColor(config('samples.styles.colors.confirm'))
            ->withConfirmButton(__('general.save'))
            ->onConfirm('toggleItemStatusConfirmed', ['id' => $this->itemID])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }


    public function toggleItemStatusConfirmed()
    {
        $item = Project::findOrFail($this->itemID);
        $item->is_active = !$item->is_active;
        $item->save();

        LivewireAlert::title(__('general.item-updated'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }


    public function show($id)
    {
        $project = Project::findOrFail($id);
        $this->reset(['item']);
        $this->item = $project;
        $this->modalTitle = $project->name;
        $this->showDetailsModal = true;

    }

    public function create()
    {
        $this->reset(['item']); // Reset all
        $this->item['is_active'] = true;
        $this->showModal = true;
        $this->modalTitle = __('general.add_new');
    }

    public function edit($id)
    {
        $project = Project::findOrFail($id);
        $this->item = $project->toArray();
        $this->item['logo'] = null; // Reset logo

        $this->modalTitle = __('general.edit');
        $this->showModal = true;
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

    public function save()
    {


        $this->validate();

        if (array_key_exists('id', $this->item)) {
            $project = Project::findOrFail($this->item['id']);
            $project->update($this->item);
            if ($this->item['logo'] !== null) {

                // Remove existing logo
                $project->clearMediaCollection('logo');

                // Add new logo
                $project->addMedia($this->item['logo']->getRealPath())
                    ->usingFileName('project-logo.' . $this->item['logo']->getClientOriginalExtension())
                    ->toMediaCollection('logo');
            }
        } else {
            $project = Project::create($this->item);
            $project->addMedia($this->item['logo']->getRealPath())
                ->usingFileName('project-logo.' . $this->item['logo']->getClientOriginalExtension())
                ->toMediaCollection('logo');

        }


        $this->reset(['item', 'showModal']);

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }

    public function delete($id)
    {

        Project::find($id['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

    }

    public function render()
    {
        $items = Project::with('media')->withCount([
            'centers',
            'cities as cities_count' => function ($query) {
                $query->select(DB::raw('count(distinct city_id)'));
            }
        ])->orderBy('is_active', 'desc')->paginate(9);

        return view('livewire.project-management.project-module', compact('items'));
    }
}
