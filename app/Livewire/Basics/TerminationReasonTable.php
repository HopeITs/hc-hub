<?php

namespace App\Livewire\Basics;

use App\Livewire\Concerns\AuthorizesHrAction;
use App\Models\TerminationReason;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class TerminationReasonTable extends LivewireTable
{
    use AuthorizesHrAction;

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
        $user = auth()->user();
        $isHR = (bool) $user?->activeRoleIs('HR', 'super-admin');

        return [
            Column::make(__('general.title').' (EN)', 'title_en')
                ->sortable()
                ->searchable(),

            Column::make(__('general.title').' (AR)', 'title_ar')
                ->sortable()
                ->searchable(),

            Column::make(__('general.actions'), function (Model $m): string {
                $id = $m->id;

                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="$dispatch(\'openTerminationReasonEdit\', { id: '.$id.' })" />'
                );
                $deleteBtn = Blade::render(
                    '<x-table.delete-btn wire:click="confirmDelete('.$id.')" :modelId="'.$id.'" />'
                );

                return '<div class="flex items-center justify-center gap-2">'.$editBtn.$deleteBtn.'</div>';
            })->asHtml()->visible($isHR),
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
        $this->authorizeHrAction();

        TerminationReason::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();

        $this->dispatch('refreshTerminationReasonTable');
    }
}
