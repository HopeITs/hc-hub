<?php

namespace App\Livewire\Position;

use App\Models\City;
use App\Models\Position;

use Illuminate\Database\Eloquent\Model;
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

            SelectColumn::make(trans_choice('general.city',1), 'city.name')
                ->options(
                    City::query()->get()->pluck('name', 'name')->toArray()
                )
                ->sortable()
                ->searchable(),



            Column::make(__('Actions'), function (Model $model): string {
                return '<a class="underline" href="#'.$model->getKey().'">Edit</a>';
            })
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
                ->multiple(),
        ];
    }

    protected function actions(): array
    {
        return [

        ];
    }
}
