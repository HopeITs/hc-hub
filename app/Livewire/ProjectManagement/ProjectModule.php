<?php

namespace App\Livewire\ProjectManagement;

use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Exception;

class ProjectModule extends Component
{

    use WithPagination, WithFileUploads;

    public $showModal = false;
    public $showDetailsModal = false;
    public $modalTitle = '';

    public Project $project;
    public $existingLogoUrl = '';

    public $item = [
        'id' => 0,
        'name' => '',
        'description' => '',
        'is_active' => true,
        'start_date' => null,
        'end_date' => null,
        'logo' => null,
        'code' => '',
        'slug' => ''
    ];

    protected function rules()
    {
        return [
            'item.name.*' => 'required',
            'item.description.*' => 'nullable',
            'item.is_active' => 'required|boolean:0,1',
            'item.logo' => 'nullable|image|max:2048',
            'item.code' => 'required',
            'item.slug' => 'required|unique:projects,slug,' . $this->item['id'],
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
        $this->reset(['item']);
        $this->reset('existingLogoUrl');
        $this->item['is_active'] = true;
        $this->showModal = true;
        $this->modalTitle = __('general.add_new');
    }

    public function edit($id)
    {
        $project = Project::findOrFail($id);
        $this->item = $project->toArray();
        $this->item['logo'] = null;
        $this->existingLogoUrl = $project->getFirstMediaUrl('logo');

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

        $data = $this->item;
        $newLogo = $data['logo'] ?? null;
        unset($data['logo']);

        try {
            DB::transaction(function () use ($data, $newLogo) {
                if (isset($data['id']) && $data['id'] > 0) {
                    $project = Project::findOrFail($data['id']);
                    $project->update($data);

                    if ($newLogo) {
                        $project->clearMediaCollection('logo');
                        $project->addMedia($newLogo->getRealPath())
                            ->usingFileName('project-logo.' . $newLogo->getClientOriginalExtension())
                            ->toMediaCollection('logo');
                    }
                } else {
                    $project = Project::create($data);
                    if ($newLogo) {
                        $project->addMedia($newLogo->getRealPath())
                            ->usingFileName('project-logo.' . $newLogo->getClientOriginalExtension())
                            ->toMediaCollection('logo');
                    }
                }
            });

            $this->reset(['item', 'showModal', 'existingLogoUrl']);

            LivewireAlert::title(__('general.success-message'))
                ->success()
                ->toast()
                ->position('top-start')
                ->show();

        } catch (Exception $e) {
            dd($e->getMessage());
        }
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
            'centers', 'requiredPositions',
            'cities as cities_count' => function ($query) {
                $query->select(DB::raw('count(distinct city_id)'));
            }
        ])->orderBy('is_active', 'desc')->paginate(9);

        return view('livewire.project-management.project-module', compact('items'));
    }
}
