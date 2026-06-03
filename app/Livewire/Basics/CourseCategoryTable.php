<?php

namespace App\Livewire\Basics;

use App\Models\CourseCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CourseCategoryExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CourseCategoryTable extends LivewireTable
{
    protected string $model = CourseCategory::class;

    protected $listeners = [
        'refreshCourseCategoryTable' => '$refresh',
        'delete',
        'exportExcel' => 'exportExcel',
    ];

    protected function query(): Builder
    {
        return CourseCategory::query()->withCount('courses');
    }

    protected function globalSearchColumns(): array
    {
        return [
            'id',
            'name_en',
            'name_ar',
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

                Column::make(__('general.name') . ' (AR)', 'name_ar')
                ->sortable()
                ->searchable(),

            Column::make(__('general.name') . ' (EN)', 'name_en')
                ->sortable()
                ->searchable(),
                
            Column::make(trans_choice('general.course', 2), 'courses_count')
                ->sortable()->searchable(),

            Column::make(__('general.actions'), function (Model $m): string {
                $id = $m->id;

                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="$dispatch(\'openCourseCategoryEdit\', { id: ' . $id . ' })" />'
                );

                $deleteBtn = Blade::render(
                    '<x-table.delete-btn wire:click="confirmDelete(' . $id . ')" :modelId="' . $id . '" />'
                );

                return '<div class="flex items-center justify-center gap-2">'
                    . $editBtn . $deleteBtn .
                    '</div>';
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
        $cat = CourseCategory::withCount('courses')
            ->findOrFail($payload['id']);

        if ($cat->courses_count > 0) {
            LivewireAlert::title(__('general.error'))
                ->text(__('general.cannot-delete-category-has-courses'))
                ->error()
                ->toast()
                ->position('top-start')
                ->show();

            return;
        }

        $cat->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

        $this->dispatch('refreshCourseCategoryTable');
    }

    public function exportExcel(): BinaryFileResponse
    {
        $query = $this->appliedQuery(); 
        $data = $query->get();
        $data->loadCount('courses');
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

        $filename = 'course_categories_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new CourseCategoryExport($data, $visibleColumnCodes), 
            $filename
        );
    }
}