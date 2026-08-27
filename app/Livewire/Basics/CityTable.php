<?php

namespace App\Livewire\Basics;

use App\Exports\CityExport;
use App\Models\City;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Maatwebsite\Excel\Facades\Excel;
use RamonRietdijk\LivewireTables\Columns\BooleanColumn;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CityTable extends LivewireTable
{
    protected string $model = City::class;

    protected $listeners = [
        'refreshCityTable' => '$refresh',
        'toggleItemStatusConfirmed',
        'exportExcel' => 'exportExcel',
    ];

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
        $item->is_active = ! $item->is_active;
        $item->save();

        LivewireAlert::title(__('general.item-updated'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

        $this->dispatch('refreshCityTable');
    }

    public function exportExcel(): BinaryFileResponse
    {
        $query = $this->appliedQuery();
        $data = $query->get();

        $userSelectedColumns = $this->columns;

        $visibleColumnCodes = collect($this->resolveColumns())
            ->filter(fn ($column) => $column->isVisible())
            ->filter(function ($column) use ($userSelectedColumns) {
                if (empty($userSelectedColumns)) {
                    return true;
                }

                return in_array($column->code(), $userSelectedColumns);
            })
            ->filter(fn ($column) => $column->label() !== __('general.actions'))
            ->map(fn ($column) => $column->code())
            ->toArray();

        $filename = 'cities_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new CityExport($data, $visibleColumnCodes),
            $filename,
        );
    }

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
        $canUpdate = auth()->user()?->hasActivePermission('project-basics.update') ?? false;

        return [

            Column::make('ID', 'id')->sortable()->searchable(),

            Column::make(__('general.name').' (AR)', 'name_ar')
                ->sortable()->searchable(),

            Column::make(__('general.name').' (EN)', 'name_en')
                ->sortable()->searchable(),

            Column::make(__('general.code'), 'code')
                ->sortable()->searchable(),

            Column::make(__('general.slug'), 'slug')
                ->sortable()->searchable(),

            BooleanColumn::make(__('general.active'), 'is_active')
                ->searchable(),

            Column::make(__('general.actions'), function (Model $m): string {

                return Blade::render(
                    '<x-forms.toggle-input
            class="!me-2 text-xl p-2 mb-3"
            active="'.($m->is_active ? 1 : 0).'"
            :model-id="'.$m->id.'"
            wire-event="confirmToggleItemStatus" />',
                );
            })
                ->asHtml()
                ->visible($canUpdate),
        ];
    }
}
