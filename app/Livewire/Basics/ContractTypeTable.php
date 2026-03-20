<?php

namespace App\Livewire\Basics;

use App\Enums\ContractPlace;
use App\Enums\ContractType as ContractTypeEnum;
use App\Models\ContractType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class ContractTypeTable extends LivewireTable
{
    protected string $model = ContractType::class;

    protected $listeners = ['refresh-table' => '$refresh'];

    protected function query(): \Illuminate\Database\Eloquent\Builder
    {
        return ContractType::query();
    }

    protected function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),

            Column::make(__('general.name').' (AR)', 'name_ar')
                ->sortable()
                ->searchable(),

            Column::make(__('general.name').' (EN)', 'name_en')
                ->sortable()
                ->searchable(),

            Column::make(__('general.place'), function (Model $m): string {
                $place = ContractPlace::getLocalizedName($m->place);

                return Blade::render(
                    '<x-badge color="'.$place['color'].'">'.$place['value'].'</x-badge>'
                );
            })
                ->sortable('place')
                ->asHtml(),

            Column::make(__('general.type'), function (Model $m): string {
                $type = ContractTypeEnum::getLocalizedName($m->type);

                return Blade::render(
                    '<x-badge color="'.$type['color'].'">'.$type['value'].'</x-badge>'
                );
            })
                ->sortable('type')
                ->asHtml(),

            Column::make(__('general.actions'), function (Model $m): string {
                return Blade::render(
                    '<x-table.edit-btn wire:click="$dispatch(\'edit-contract-type\', '.$m->id.')" />'
                );
            })->asHtml(),
        ];
    }

    protected function actions(): array
    {
        return [];
    }
}
