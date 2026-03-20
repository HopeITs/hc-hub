<?php

namespace App\Livewire\Basics;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\BooleanColumn;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class LeaveTypeTable extends LivewireTable
{
    protected string $model = LeaveType::class;

    public $itemID;

    protected function query(): \Illuminate\Database\Eloquent\Builder
    {
        return LeaveType::query();
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

            Column::make(__('general.base-days'), 'base_days')
                ->sortable()
                ->searchable(),

            Column::make(__('general.allowed-gender'), function (Model $m) {
                $gender = \App\Enums\LeaveType::getGender($m->allowed_gender);
                return '<x-badge :color="' . $gender['color'] . '">' . $gender['value'] . '</x-badge>';
            })->asHtml(),

            Column::make(__('general.marital_status'), function (Model $m) {
                $marital = \App\Enums\LeaveType::getMaritalStatus($m->marital_status);
                return '<x-badge :color="' . $marital['color'] . '">' . $marital['value'] . '</x-badge>';
            })->asHtml(),

            Column::make(__('general.actions'), function (Model $m): string {
                return Blade::render(
                    '<x-table.edit-btn wire:click="edit(' . $m->id . ')"/>'
                );
            })->asHtml(),
        ];
    }

    public function confirmDelete($id)
    {
        $this->itemID = $id;

        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('deleteConfirmed', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function deleteConfirmed($payload)
    {
        $item = LeaveType::findOrFail($payload['id']);
        $item->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }
}
