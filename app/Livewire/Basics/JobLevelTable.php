<?php

namespace App\Livewire\Basics;

use App\Models\JobLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;


class JobLevelTable extends LivewireTable
{
    protected string $model = JobLevel::class;

    protected $listeners = [
        'refreshJobLevelTable' => '$refresh',
        'delete',
    ];

    protected function query(): Builder
    {
        return JobLevel::query();
    }

    protected function globalSearchColumns(): array
    {
        return ['id', 'name_ar', 'name_en', 'grade', 'email'];
    }

    protected function columns(): array
    {
        $user = auth()->user();

        $isHR = $user?->activeRoleIs('HR') || $user?->activeRoleIs('super-admin');

        return [
            Column::make('ID', 'id')->sortable()->searchable(),

            Column::make(__('general.name') . ' (AR)', 'name_ar')->sortable()->searchable(),
            Column::make(__('general.name') . ' (EN)', 'name_en')->sortable()->searchable(),



            Column::make(__('general.grade'), 'grade')
                ->sortable()
                ->searchable(),

            Column::make(__('general.email'), 'email')
                ->sortable()
                ->searchable(),

            Column::make(__('general.actions'), function (Model $m): string {
                $id = $m->id;

                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="$dispatch(\'openJobLevelEdit\', { id: ' . $id . ' })" />'
                );

                $deleteBtn = Blade::render(
                    '<x-table.delete-btn wire:click="confirmDelete(' . $id . ')" :modelId="' . $id . '" />'
                );

                // return '<div class="flex items-center justify-center gap-2">'
                //     . $editBtn . $deleteBtn .
                //     '</div>';
                return $editBtn;
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
        JobLevel::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

        $this->dispatch('refreshJobLevelTable');
    }
}

