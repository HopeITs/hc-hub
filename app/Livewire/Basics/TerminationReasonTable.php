<?php

namespace App\Livewire\Basics;

use App\Models\TerminationReason;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class TerminationReasonTable extends LivewireTable
{
    protected string $model = TerminationReason::class;

    protected $listeners = [
        'refreshTerminationReasonTable' => '$refresh',
        'delete',
    ];

    protected function query(): Builder
    {
        return TerminationReason::query();
    }

    protected function globalSearchColumns(): array
    {
        return ['id', 'title_en', 'title_ar'];
    }

    protected function columns(): array
    {
        $canUpdate = auth()->user()?->hasActivePermission('project-basics.update') ?? false;
        $canDelete = auth()->user()?->hasActivePermission('project-basics.delete') ?? false;

        return [
            Column::make(__('general.title').' (EN)', 'title_en')
                ->sortable()
                ->searchable(),

            Column::make(__('general.title').' (AR)', 'title_ar')
                ->sortable()
                ->searchable(),

            Column::make(__('general.actions'), function (Model $m) use ($canUpdate, $canDelete): string {
                $id = $m->id;

                $editBtn = $canUpdate
                    ? Blade::render('<x-table.edit-btn wire:click="$dispatch(\'openTerminationReasonEdit\', { id: '.$id.' })" />')
                    : '';
                $deleteBtn = $canDelete
                    ? Blade::render('<x-table.delete-btn wire:click="confirmDelete('.$id.')" :modelId="'.$id.'" />')
                    : '';

                return '<div class="flex items-center justify-center gap-2">'.$editBtn.$deleteBtn.'</div>';
            })->asHtml()->visible($canUpdate || $canDelete),
        ];
    }

    public function confirmDelete(int $id): void
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete(array $payload): void
    {
        TerminationReason::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();

        $this->dispatch('refreshTerminationReasonTable');
    }
}
