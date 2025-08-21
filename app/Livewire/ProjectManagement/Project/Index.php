<?php

namespace App\Livewire\ProjectManagement\Project;

use App\Livewire\Forms\ProjectManagement\ProjectForm;
use App\Models\Project;
use Exception;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{

    use WithPagination, WithFileUploads;

    protected $listeners = ['load-more' => 'loadMore'];

    public Project $project;
    public ProjectForm $form;
    public $perPage = 6;
    public function loadMore(): void
    {
        $this->perPage += 6; // increase the limit
    }




    public function confirmToggleItemStatus($id)
    {

        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->question()
            ->confirmButtonColor(config('samples.styles.colors.confirm'))
            ->withConfirmButton(__('general.save'))
            ->onConfirm('toggleItemStatusConfirmed', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }


    public function toggleItemStatusConfirmed(Project $project)
    {
        $this->form->setProject($project);
        $this->form->statusToggle();


        LivewireAlert::title(__('general.item-updated'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }


    public function show(Project $project)
    {
        $this->form->setProject($project);
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


    public function delete(Project $project)
    {
        $this->form->setProject($project);
        $this->form->project->delete();

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
        ])->orderBy('is_active', 'desc')->latest()->take($this->perPage)->get();

        return view('livewire.project-management.project.index', compact('items'));
    }
}
