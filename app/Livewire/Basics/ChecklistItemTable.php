<?php

namespace App\Livewire\Basics;

use App\Models\ChecklistItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;
use App\Enums\ChecklistItemOrder;

class ChecklistItemTable extends LivewireTable
{
    protected string $model = ChecklistItem::class;

    public $itemID;

    protected function query(): \Illuminate\Database\Eloquent\Builder
    {
        return ChecklistItem::query();
    }

    protected function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),

            Column::make(__('general.name'), 'name')
                ->sortable()
                ->searchable(),

            Column::make(__('general.order'), 'order')
                ->sortable()
                ->searchable(),

            Column::make(__('general.actions'), 'id')
        ];
    }

    // This method will be called from the Blade view to render badges for order
    public function renderBadge($item)
    {
        $order = ChecklistItemOrder::getLocalizedName($item->order);
        return Blade::render('<x-badge :color="$color">{{ $value }}</x-badge>', [
            'color' => $order['color'],
            'value' => $order['value'],
        ]);
    }

    // This method renders the action buttons
    public function renderActions($item)
    {
        return Blade::render(
            '<x-table.edit-btn wire:click="edit(' . $item->id . ')" />
             <x-table.delete-btn :modelId="' . $item->id . '" wire:click="confirmDelete(' . $item->id . ')" />'
        );
    }

    public function confirmDelete($id)
    {
        $this->itemID = $id;

        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->confirmButtonColor(config('samples.styles.colors.primary'))
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('deleteConfirmed', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function deleteConfirmed($payload)
    {
        ChecklistItem::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }

    public function edit($id)
    {
        $this->emit('editChecklistItem', $id);
    }
}
