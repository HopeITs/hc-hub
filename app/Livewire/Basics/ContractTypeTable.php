<?php

namespace App\Livewire\Basics;

use App\Models\ContractType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;
use App\Enums\ContractPlace;
use App\Enums\ContractType as ContractTypeEnum;

class ContractTypeTable extends LivewireTable
{
    protected string $model = ContractType::class;

    protected $listeners = [
        'refreshContractTypeTable' => '$refresh',
        'delete',
    ];

    protected function query(): Builder
    {
        return ContractType::query();
    }

    protected function globalSearchColumns(): array
    {
        return ['id', 'name_ar', 'name_en', 'place', 'type'];
    }

    protected function columns(): array
    {
        return [

            Column::make('ID', 'id')->sortable(),

            Column::make(__('general.name') . ' (AR)', 'name_ar')
                ->sortable()
                ->searchable(),

            Column::make(__('general.name') . ' (EN)', 'name_en')
                ->sortable()
                ->searchable(),

            SelectColumn::make(__('general.place'), 'place')
                ->options([
                    ContractPlace::Onsite => ContractPlace::getLocalizedName(ContractPlace::Onsite)['value'],
                    ContractPlace::Online => ContractPlace::getLocalizedName(ContractPlace::Online)['value'],
                ])
                ->displayUsing(function ($value) {
                    $place = ContractPlace::getLocalizedName($value);

                    return Blade::render(
                        '<x-badge color="' . $place['color'] . '">' . e($place['value']) . '</x-badge>'
                    );
                })
                ->sortable()
                ->searchable()
                ->asHtml(),

            SelectColumn::make(__('general.type'), 'type')
                ->options([
                    ContractTypeEnum::FullTime => ContractTypeEnum::getLocalizedName(ContractTypeEnum::FullTime)['value'],
                    ContractTypeEnum::PartTime => ContractTypeEnum::getLocalizedName(ContractTypeEnum::PartTime)['value'],
                    ContractTypeEnum::Freelance => ContractTypeEnum::getLocalizedName(ContractTypeEnum::Freelance)['value'],
                ])
                ->displayUsing(function ($value) {
                    $type = ContractTypeEnum::getLocalizedName($value);

                    return Blade::render(
                        '<x-badge color="' . $type['color'] . '">' . e($type['value']) . '</x-badge>'
                    );
                })
                ->sortable()
                ->searchable()
                ->asHtml(),

            Column::make(__('general.actions'), function (Model $m): string {

                $id = $m->id;

                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="$dispatch(\'openContractTypeEdit\', { id: ' . $id . ' })" />'
                );

                $deleteBtn = Blade::render(
                    '<x-table.delete-btn wire:click="confirmDelete(' . $id . ')" :modelId="' . $id . '" />'
                );

                return '<div class="flex items-center justify-center gap-2">'
                    . $editBtn . $deleteBtn . '</div>';
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
        ContractType::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();

        $this->dispatch('refreshContractTypeTable');
    }
}
