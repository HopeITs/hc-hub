<?php

namespace App\Livewire\Position;

use App\Models\Center;
use App\Models\City;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Position;

use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\BooleanColumn;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class PositionTable extends LivewireTable
{
    protected string $model = Position::class;


    /** @return \Illuminate\Database\Eloquent\Builder */
    protected function query(): \Illuminate\Database\Eloquent\Builder
    {

        $query = $this->model()->query();
        $filters = request()->query();
        if(isset($filters['center'])){
            $query->where('center_id', $filters['center']);
        }
        if(isset($filters['city'])){
            $query->where('city_id', $filters['city']);
        }

        return $query;
    }
    protected function columns(): array
    {
        return [
            Column::make(__('Title (EN)'), 'title_en')
                ->sortable()
                ->searchable(),

            Column::make(__('Title (AR)'), 'title_ar')
                ->sortable()
                ->searchable(),

            SelectColumn::make(trans_choice('general.city', 1), 'city_id')
                ->displayUsing(function (mixed $value, Model $model): string {
                    return $model->city?->{'name_' . app()->getLocale()} ?? '-';
                })
                ->options(
                    City::query()->get()->mapWithKeys(fn($city) => [$city->id => $city->{'name_' . app()->getLocale()}])->toArray()
                )
                ->sortable()
                ->searchable(),
            SelectColumn::make(trans_choice('general.program', 1), 'program_id')
                ->displayUsing(function (mixed $value, Model $model): string {
                    return $model->program->name;
                })
                ->options(
                    Program::query()->get()->pluck('name', 'id')->toArray()
                )
                ->sortable()
                ->searchable(),
            SelectColumn::make(trans_choice('general.center', 1), 'center_id')
                ->displayUsing(function (mixed $value, Model $model): string {
                    return $model->center->name ?? '-';
                })
                ->options(
                    Center::query()->get()->pluck('name', 'id')->toArray()
                )
                ->sortable()
                ->searchable(),
            SelectColumn::make(trans_choice('general.department', 1), 'department_id')
                ->displayUsing(function (mixed $value, Model $model): string {
                    return $model->department->name ?? '-';
                })
                ->options(
                    Department::query()->get()->pluck('name', 'id')->toArray()
                )
                ->sortable()
                ->searchable(),
            SelectColumn::make(trans_choice('general.job-level', 1), 'job_level_id')
                ->displayUsing(function (mixed $value, Model $model): string {
                    return $model->jobLevel?->{'name_' . app()->getLocale()} ?? '-';
                })
                ->options(
                    JobLevel::query()->get()->mapWithKeys(fn($level) => [$level->id => $level->{'name_' . app()->getLocale()}])->toArray()
                )
                ->sortable()
                ->searchable(),

            BooleanColumn::make(__('general.is-active'), 'is_active'),

            Column::make(__('Actions'), function (Model $model): string {
                $is_active = $model->is_active ? 1 : 0;
                $is_activeBtn = Blade::render('<x-forms.toggle-input class="!me-2 text-xl p-2 mb-3" active="' . $is_active . '" :model-id="' . $model->id . '" wire-event="confirmToggleItemStatus" />');
                $editBtn = Blade::render("<x-table.edit-btn href=" . route('positions.edit', ['position' => $model->getKey()]) . "/>");
                $deleteBtn = Blade::render("<x-table.delete-btn :modelId=" . $model->getKey() . "/>");
                return $is_activeBtn . $editBtn . $deleteBtn;
            })
                ->asHtml(),
        ];
    }

    protected function filters(): array
    {
        return [

            SelectFilter::make(trans_choice('general.city', 1), 'city_id')
                ->options(
                    City::query()->get()->mapWithKeys(fn($city) => [$city->id => $city->{'name_' . app()->getLocale()}])->toArray()
                )
            ,
            SelectFilter::make(trans_choice('general.program', 1), 'program_id')
                ->options(
                    Program::query()->get()->pluck('name', 'id')->toArray()
                )
            ,
            SelectFilter::make(trans_choice('general.center', 1), 'center_id')
                ->options(
                    Center::query()->get()->pluck('name', 'id')->toArray()
                )
            ,
            SelectFilter::make(trans_choice('general.department', 1), 'department_id')
                ->options(
                    Department::query()->get()->pluck('name', 'id')->toArray()
                )
            ,
            SelectFilter::make(trans_choice('general.job-level', 1), 'job_level_id')
                ->options(
                    JobLevel::query()->get()->mapWithKeys(fn($level) => [$level->id => $level->{'name_' . app()->getLocale()}])->toArray()
                )
            ,
        ];
    }

    protected function actions(): array
    {
        return [

        ];
    }

    public function confirmDelete($id)
    {
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
        Position::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }

    public function confirmToggleItemStatus($id)
    {
        $this->itemID = $id;

        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->question()
            ->withConfirmButton(__('general.save'))
            ->onConfirm('toggleItemStatusConfirmed', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function toggleItemStatusConfirmed($payload)
    {
        $item = Position::findOrFail($payload['id']);
        $item->is_active = !$item->is_active;
        $item->save();

        LivewireAlert::title(__('general.item-updated'))
            ->success()->toast()->position('top-start')->show();
    }

}
