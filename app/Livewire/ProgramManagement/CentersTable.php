<?php

namespace App\Livewire\ProgramManagement;

use App\Models\Center;
use App\Models\City;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\BooleanColumn;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class CentersTable extends LivewireTable
{
    protected string $model = Center::class;

    protected $listeners = ['delete', 'toggleItemStatusConfirmed'];

    protected function query(): Builder
    {
        return $this->model()::query()
            ->with(['city'])
            ->withCount('positions');
    }

    protected function globalSearchColumns(): array
    {
        return ['name'];
    }

    protected function cityOptions(): array
    {
        $locale = app()->getLocale();

        return City::query()
            ->orderBy(transColumn('name'))
            ->get(['id', transColumn( 'name')])
            ->mapWithKeys(fn($c) => [$c->id => $c->name])
            ->all();
    }

    protected function columns(): array
    {
        $locale = app()->getLocale();

        return [
            Column::make('ID', 'id')->sortable(),

            Column::make(__('general.center-name'), 'name')
                ->sortable()
                ->searchable(),

            SelectColumn::make(__('general.center-city'), 'city_id')
                ->displayUsing(fn($v, Model $m) => optional($m->city)?->name ?? '—'
                )
                ->options($this->cityOptions())
                ->sortable(),

            Column::make(__('general.address'))
                ->displayUsing(fn($v, Model $m) => $m->address ?: '—'
                ),

            BooleanColumn::make(__('general.is-active'), 'is_active'),

            // ✅ Positions button with count
            Column::make(__('general.positions'), function (Model $m): string {
                $url = route('positions.index', ['center' => $m->id]);

                $classes =
                    'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs md:text-sm font-medium ' .
                    'border shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary/40 ' .
                    ($m->is_active
                        ? 'bg-primary/10 text-primary border-primary/20 hover:bg-primary/15 hover:border-primary/30'
                        : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 hover:border-gray-300');

                $count = $m->positions_count ?? 0;
                $badge = '<span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full text-[11px] md:text-xs bg-current/10">'
                    . $count . '</span>';

                return Blade::render(
                    '<a href="' . $url . '" class="' . $classes . '" title="' . e(__('general.positions')) . '">
                        <i class="ti ti-briefcase text-base"></i>
                        <span>' . e(trans_choice('general.positions', 2)) . '</span>' .
                    $badge .
                    '</a>'
                );
            })->asHtml(),

            // Actions
            Column::make(__('Actions'), function (Model $m): string {
                $isActive = $m->is_active ? 1 : 0;

                $toggleBtn = Blade::render(
                    '<x-forms.toggle-input class="!me-2 text-xl p-2 mb-3" active="' . $isActive .
                    '" :model-id="' . $m->getKey() .
                    '" wire-event="confirmToggleItemStatus" />'
                );

                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="$dispatch(\'openCenterEdit\', { id: ' . $m->getKey() . ' })" />'
                );

                $deleteBtn = Blade::render(
                    '<x-table.delete-btn :modelId="' . $m->getKey() . '" />'
                );

                return $toggleBtn . $editBtn . $deleteBtn;
            })->asHtml(),
        ];
    }

    protected function filters(): array
    {
        return [
            SelectFilter::make(trans_choice('general.city', 1), 'city_id')
                ->options($this->cityOptions()),

            SelectFilter::make(__('general.is-active'), 'is_active')
                ->options([1 => __('general.active'), 0 => __('general.inactive')]),
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
        $item = Center::findOrFail($payload['id']);
        $item->is_active = !$item->is_active;
        $item->save();

        LivewireAlert::title(__('general.item-updated'))
            ->success()->toast()->position('top-start')->show();
    }

    public function confirmDelete($id): void
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete($payload): void
    {
        Center::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }
}
