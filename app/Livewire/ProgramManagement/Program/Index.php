<?php

namespace App\Livewire\ProgramManagement\Program;

use App\Livewire\Forms\ProgramManagement\ProgramForm;
use App\Models\Program;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithFileUploads, WithPagination;

    public Program $program;

    public ProgramForm $form;

    public $perPage = 6;

    protected $listeners = ['load-more' => 'loadMore'];

    public function loadMore(): void
    {
        $this->perPage += 6;
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

    public function toggleItemStatusConfirmed(Program $program)
    {
        $this->form->setProgram($program);
        $this->form->statusToggle();

        LivewireAlert::title(__('general.item-updated'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

        $this->resetPage();
    }

    public function show(Program $program)
    {
        $this->form->setProgram($program);
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

    public function delete(Program $program)
    {
        $this->form->setProgram($program);
        $this->form->program->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }

    public function render()
    {
        $items = Program::with('media')
            ->withCount([
                'cities as cities_count' => function ($query) {
                    $query->select(DB::raw('count(distinct city_id)'));
                },
                'contracts',
            ])
            ->with('contracts')
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        $items->each(function ($item) {
            $programManager = $item->manager();
            if ($programManager) {
                $item->program_manager_name = $programManager->name;
            }
        });

        return view('livewire.program-management.program.index', compact('items'));
    }
}
