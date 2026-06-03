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

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\JobLevelExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JobLevelTable extends LivewireTable
{
    protected string $model = JobLevel::class;

    protected $listeners = [
        'refreshJobLevelTable' => '$refresh',
        'delete',
        'exportExcel' => 'exportExcel',
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
public function exportExcel(): BinaryFileResponse
    {
        $query = $this->appliedQuery(); 
        $data = $query->get();

        $userSelectedColumns = $this->columns; 

        $visibleColumnCodes = collect($this->resolveColumns())
            ->filter(fn ($column) => $column->isVisible()) 
            ->filter(function ($column) use ($userSelectedColumns) {
                if (empty($userSelectedColumns)) return true;
                return in_array($column->code(), $userSelectedColumns);
            })
            ->filter(fn ($column) => $column->label() !== __('general.actions'))
            ->map(fn ($column) => $column->code()) 
            ->toArray();

        $filename = 'job_levels_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new JobLevelExport($data, $visibleColumnCodes), 
            $filename
        );
    }

}

