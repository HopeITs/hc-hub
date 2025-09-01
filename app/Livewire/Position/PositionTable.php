<?php

namespace App\Livewire\Position;

use App\Models\Center;
use App\Models\City;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Position;

use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Util\PHP\Job;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class PositionTable extends LivewireTable
{
    protected string $model = Position::class;

    protected function columns(): array
    {
        return [


            Column::make(__('Title'), 'title')
                ->sortable()
                ->searchable(),

            Column::make(trans_choice('general.city',1), 'city.name')
                ->sortable()
                ->searchable(),
            Column::make(trans_choice('general.program',1), 'program.name')
                ->sortable()
                ->searchable(),
            Column::make(trans_choice('general.center',1), 'center.name')
                ->sortable()
                ->searchable(),
            Column::make(trans_choice('general.department',1), 'department.name')
                ->sortable()
                ->searchable(),
            Column::make(trans_choice('general.job-level',1), 'jobLevel.name')
                ->sortable()
                ->searchable(),



            Column::make(__('Actions'), function (Model $model): string {
                $editBtn = "<x-table.edit-btn href=".route('positions.edit', ['position' => $model->getKey()])."/>";
                $edit = '<a class="cursor-pointer p-2 inline-flex items-center">
    <svg class="size-5 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
         width="24" height="24" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
    </svg>
</a>';
                return $editBtn .$edit  ;
            } )
                ->clickable(false)
                ->asHtml(),
        ];
    }

    protected function filters(): array
    {
        return [

            SelectFilter::make(trans_choice('general.city',1), 'city_id')
                ->options(
                    City::query()->get()->pluck('name', 'id')->toArray()
                )
                ,
            SelectFilter::make(trans_choice('general.program',1), 'program_id')
                ->options(
                    Program::query()->get()->pluck('name', 'id')->toArray()
                )
            ,
            SelectFilter::make(trans_choice('general.center',1), 'center_id')
                ->options(
                    Center::query()->get()->pluck('name', 'id')->toArray()
                )
            ,
            SelectFilter::make(trans_choice('general.department',1), 'department_id')
                ->options(
                    Department::query()->get()->pluck('name', 'id')->toArray()
                )
            ,
            SelectFilter::make(trans_choice('general.job-level',1), 'job_level_id')
                ->options(
                    JobLevel::query()->get()->pluck('name', 'id')->toArray()
                )
            ,
        ];
    }

    protected function actions(): array
    {
        return [

        ];
    }
}
