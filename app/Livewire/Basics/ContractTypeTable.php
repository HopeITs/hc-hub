<?php

namespace App\Livewire\Basics;

use App\Enums\ContractPlace;
use App\Enums\ContractType as ContractTypeEnum;
use App\Exports\ContractTypeExport;
use App\Livewire\Concerns\AuthorizesHrAction;
use App\Models\ContractType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Maatwebsite\Excel\Facades\Excel;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContractTypeTable extends LivewireTable
{
    use AuthorizesHrAction;

    protected string $model = ContractType::class;

    protected $listeners = [
        'refreshContractTypeTable' => '$refresh',
        'delete',
        'exportExcel' => 'exportExcel', // <-- ADD THIS LISTENER
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
        $user = auth()->user();
        $isHR = (bool) $user?->activeRoleIs('HR', 'super-admin');

        return [

            Column::make('ID', 'id')->sortable()->searchable(),

            Column::make(__('general.name').' (AR)', 'name_ar')
                ->sortable()
                ->searchable(),

            Column::make(__('general.name').' (EN)', 'name_en')
                ->sortable()
                ->searchable(),

            SelectColumn::make(__('general.place'), 'place')
                ->options([
                    ContractPlace::Onsite => ContractPlace::getLocalizedName(ContractPlace::Onsite)['value'],
                    ContractPlace::Online => ContractPlace::getLocalizedName(ContractPlace::Online)['value'],
                ])
                ->displayUsing(function ($value) {
                    $place = ContractPlace::getLocalizedName($value);

                    return $place['value'];
                })
                ->sortable()
                ->searchable(),

            SelectColumn::make(__('general.type'), 'type')
                ->options([
                    ContractTypeEnum::FullTime => ContractTypeEnum::getLocalizedName(ContractTypeEnum::FullTime)['value'],
                    ContractTypeEnum::PartTime => ContractTypeEnum::getLocalizedName(ContractTypeEnum::PartTime)['value'],
                    ContractTypeEnum::Freelance => ContractTypeEnum::getLocalizedName(ContractTypeEnum::Freelance)['value'],
                ])
                ->displayUsing(function ($value) {
                    $type = ContractTypeEnum::getLocalizedName($value);

                    return $type['value'];
                })
                ->sortable()
                ->searchable(),

            Column::make(__('general.actions'), function (Model $m): string {

                $id = $m->id;

                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="$dispatch(\'openContractTypeEdit\', { id: '.$id.' })" />'
                );

                return $editBtn;
            })->asHtml()->visible($isHR),
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
        $this->authorizeHrAction();

        ContractType::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();

        $this->dispatch('refreshContractTypeTable');
    }

    public function exportExcel(): BinaryFileResponse
    {

        $query = $this->appliedQuery();

        $data = $query->get();

        $userSelectedColumns = $this->columns;

        // Get the static CODES instead of the translated LABELS
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

        $filename = 'contract_types_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new ContractTypeExport($data, $visibleColumnCodes),
            $filename
        );
    }
}
