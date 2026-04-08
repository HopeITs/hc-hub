<?php

namespace App\Livewire\Basics;

use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class DepartmentTable extends LivewireTable
{
    protected string $model = Department::class;

    protected $listeners = [
        'refreshDepartmentTable' => '$refresh',
        'delete',
    ];

    protected function query(): Builder
    {
        return Department::query();
    }

    protected function globalSearchColumns(): array
    {
        return [
            'id',
            'name',
            'code',
            'description_ar',
            'description_en',
            'email',
        ];
    }

    protected function columns(): array
    {
        $user = auth()->user();

        $isHR = $user?->activeRoleIs('HR') || $user?->activeRoleIs('super-admin');

        return [
            Column::make('ID', 'id')
                ->sortable()
                ->searchable(),

            Column::make(__('general.department-name'), 'name')
                ->sortable()
                ->searchable(),

            Column::make(__('general.code'), 'code')
                ->sortable()
                ->searchable(),

            // Column::make(__('general.department-description') . ' (EN)', 'description_en')
            //     ->sortable()
            //     ->searchable(),

            //     Column::make(__('general.department-description') . ' (AR)', 'description_ar')
            //     ->sortable()
            //     ->searchable(),

            Column::make(__('general.department-email'), 'email')
                ->sortable()
                ->searchable(),

            Column::make(__('general.actions'), function (Model $m): string {
                $id = $m->id;

                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="$dispatch(\'openDepartmentEdit\', { id: ' . $id . ' })" />'
                );

                $deleteBtn = Blade::render(
                    '<x-table.delete-btn wire:click="confirmDelete(' . $id . ')" :modelId="' . $id . '" />'
                );

                // return '<div class="flex items-center justify-center gap-2">'
                //     . $editBtn . $deleteBtn . '</div>';
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
        Department::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

        $this->dispatch('refreshDepartmentTable');
    }
}