<?php

namespace App\Livewire\Basics;

use App\Models\JobLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\BooleanColumn;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class JobLevelTable extends LivewireTable
{
    protected string $model = JobLevel::class;
    public $itemID;

    protected function query(): \Illuminate\Database\Eloquent\Builder
    {
        return JobLevel::query();
    }

    protected function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable(),

            Column::make(__('general.name') . ' (AR)', 'name_ar')
                ->sortable()
                ->searchable(),

            Column::make(__('general.name') . ' (EN)', 'name_en')
                ->sortable()
                ->searchable(),

            Column::make(__('general.grade'), 'grade')
                ->sortable()
                ->searchable(),

            Column::make(__('general.email'), 'email')
                ->sortable()
                ->searchable(),

            BooleanColumn::make(__('general.status'), 'is_active'),

            Column::make(__('general.actions'), function (Model $m): string {
                return Blade::render(
                    '<x-forms.toggle-input active="' . ($m->is_active ? 1 : 0) . '" :model-id="' . $m->id . '" wire-event="confirmToggleItemStatus" />'
                );
            })->asHtml(),
        ];
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
        $item = JobLevel::findOrFail($payload['id']);
        $item->is_active = ! $item->is_active;
        $item->save();

        LivewireAlert::title(__('general.item-updated'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }
}
