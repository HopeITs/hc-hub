<?php

namespace App\Livewire\Basics;

use App\Models\LeaveType;
use App\Enums\LeaveType as LeaveTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class LeaveTypeTable extends LivewireTable
{
    protected string $model = LeaveType::class;

    protected $listeners = [
        'refreshLeaveTypeTable' => '$refresh',
        'delete',
    ];

    protected function query(): Builder
    {
        return LeaveType::query();
    }

    protected function globalSearchColumns(): array
    {
        return ['id', 'name_ar', 'name_en', 'base_days', 'allowed_gender', 'marital_status'];
    }



    protected function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),

            Column::make(__('general.name') . ' (AR)', 'name_ar')->sortable()->searchable(),
            Column::make(__('general.name') . ' (EN)', 'name_en')->sortable()->searchable(),

            Column::make(__('general.base-days'), 'base_days')->sortable()->searchable(),

            SelectColumn::make(__('general.allowed-gender'), 'allowed_gender')
                ->options([
                    LeaveTypeEnum::GENDER_ANY => LeaveTypeEnum::getGender(LeaveTypeEnum::GENDER_ANY)['value'],
                    LeaveTypeEnum::GENDER_MALE => LeaveTypeEnum::getGender(LeaveTypeEnum::GENDER_MALE)['value'],
                    LeaveTypeEnum::GENDER_FEMALE => LeaveTypeEnum::getGender(LeaveTypeEnum::GENDER_FEMALE)['value'],
                ])
                ->displayUsing(fn($value) => Blade::render(
                    '<x-badge color="' . LeaveTypeEnum::getGender($value)['color'] . '">'
                        . e(LeaveTypeEnum::getGender($value)['value']) . '</x-badge>'
                ))
                ->sortable()
                ->searchable()
                ->asHtml(),

            SelectColumn::make(__('general.marital_status'), 'marital_status')
                ->options([
                    LeaveTypeEnum::MARITAL_STATUS_ANY => LeaveTypeEnum::getMaritalStatus(LeaveTypeEnum::MARITAL_STATUS_ANY)['value'],
                    LeaveTypeEnum::MARITAL_STATUS_SINGLE => LeaveTypeEnum::getMaritalStatus(LeaveTypeEnum::MARITAL_STATUS_SINGLE)['value'],
                    LeaveTypeEnum::MARITAL_STATUS_MARRIED => LeaveTypeEnum::getMaritalStatus(LeaveTypeEnum::MARITAL_STATUS_MARRIED)['value'],
                ])
                ->displayUsing(fn($value) => Blade::render(
                    '<x-badge color="' . LeaveTypeEnum::getMaritalStatus($value)['color'] . '">'
                        . e(LeaveTypeEnum::getMaritalStatus($value)['value']) . '</x-badge>'
                ))
                ->sortable()
                ->searchable()
                ->asHtml(),

            Column::make(__('general.actions'), function (Model $m): string {
                $id = $m->id;
                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="$dispatch(\'openLeaveTypeEdit\', { id: ' . $id . ' })" />'
                );
                $deleteBtn = Blade::render(
                    '<x-table.delete-btn wire:click="confirmDelete(' . $id . ')" :modelId="' . $id . '" />'
                );

                // return '<div class="flex items-center justify-center gap-2">' . $editBtn . '</div>';
                return $editBtn;
            })->asHtml(),
        ];
    }

    public function confirmDelete(int $id): void
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete(array $payload): void
    {
        LeaveType::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();

        $this->dispatch('refreshLeaveTypeTable');
    }
}
