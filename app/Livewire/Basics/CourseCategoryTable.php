<?php

namespace App\Livewire\Basics;

use App\Models\CourseCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class CourseCategoryTable extends LivewireTable
{
    protected string $model = CourseCategory::class;
    public $itemID;

    protected function query(): \Illuminate\Database\Eloquent\Builder
    {
        return CourseCategory::withCount('courses');
    }

    protected function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),

            Column::make(__('Name (EN)'), 'name_en')
                ->sortable()
                ->searchable(),

            Column::make(__('Name (AR)'), 'name_ar')
                ->sortable()
                ->searchable(),

            Column::make(trans_choice('general.course', 2), function (Model $m) {
                return $m->courses_count;
            })->asHtml(),

            Column::make(__('Actions'), function (Model $m) {
                return Blade::render(
                    '<div class="inline-flex items-center space-x-1 rtl:space-x-reverse">
                        <x-table.edit-btn wire:click="edit('.$m->id.')" />
                        <x-table.delete-btn wire:click="confirmDelete('.$m->id.')" :model-id="'.$m->id.'" />
                    </div>'
                );
            })->asHtml(),
        ];
    }

    public function confirmDelete($id)
    {
        $this->itemID = $id;

        $cat = CourseCategory::withCount('courses')->findOrFail($id);
        if ($cat->courses_count > 0) {
            LivewireAlert::title(__('general.error'))
                ->text(__('Cannot delete: category has courses.'))
                ->error()
                ->toast()
                ->position('top-start')
                ->show();
            return;
        }

        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete($payload)
    {
        CourseCategory::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }
}
