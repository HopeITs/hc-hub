<?php

namespace App\Livewire\Basics;

use App\Models\City;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\BooleanColumn;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class CityTable extends LivewireTable
{
    protected string $model = City::class;
    protected $listeners = [
        'refreshCityTable' => '$refresh',
        'toggleItemStatusConfirmed'
    ];

    protected function query(): Builder
    {
        return City::query();
    }

    protected function globalSearchColumns(): array
    {
        return ['id', 'name_ar', 'name_en', 'code', 'slug'];
    }

    protected function columns(): array
    {
        $user = auth()->user();
        $isHR = $user?->activeRoleIs('HR') || $user?->activeRoleIs('super-admin');

        return [

            Column::make('ID', 'id')->sortable()->searchable(),

            Column::make(__('general.name') . ' (AR)', 'name_ar')
                ->sortable()->searchable(),

            Column::make(__('general.name') . ' (EN)', 'name_en')
                ->sortable()->searchable(),

            Column::make(__('general.code'), 'code')
                ->sortable()->searchable(),

            Column::make(__('general.slug'), 'slug')
                ->sortable()->searchable(),


            BooleanColumn::make(__('general.active'), 'is_active')
                ->searchable(),

            // Column::make(__('general.actions'), function (Model $m) use ($isHR): string {

            //     if (!$isHR) {
            //         return '';
            //     }

            //     $toggleBtn = Blade::render(
            //         '<x-forms.toggle-input 
            //             class="!me-2 text-xl p-2 mb-3"
            //             active="' . ($m->is_active ? 1 : 0) . '" 
            //             :model-id="' . $m->id . '" 
            //             wire-event="confirmToggleItemStatus" />'
            //     );
            //     return $toggleBtn ;
            // })->asHtml(),
            Column::make(__('general.actions'), function (Model $m): string {

                return Blade::render(
                    '<x-forms.toggle-input 
            class="!me-2 text-xl p-2 mb-3"
            active="' . ($m->is_active ? 1 : 0) . '" 
            :model-id="' . $m->id . '" 
            wire-event="confirmToggleItemStatus" />'
                );
            })
                ->asHtml()
                ->visible($isHR),
        ];
    }

    public function confirmToggleItemStatus($id): void
    {
        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->question()
            ->withConfirmButton(__('general.save'))
            ->onConfirm('toggleItemStatusConfirmed', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function toggleItemStatusConfirmed($payload): void
    {
        $item = City::findOrFail($payload['id']);
        $item->is_active = !$item->is_active;
        $item->save();

        LivewireAlert::title(__('general.item-updated'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

        $this->dispatch('refreshCityTable');
    }
}
