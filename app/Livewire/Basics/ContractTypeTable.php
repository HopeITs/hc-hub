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

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ContractTypeExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use RamonRietdijk\LivewireTables\Actions\Action;
class ContractTypeTable extends LivewireTable
{
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
        $isHR = $user?->activeRoleIs('HR') || $user?->activeRoleIs('super-admin');
        return [

            Column::make('ID', 'id')->sortable()->searchable(),

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

                // return '<div class="flex items-center justify-center gap-2">'
                //     . $editBtn . $deleteBtn . '</div>';
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
        ContractType::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();

        $this->dispatch('refreshContractTypeTable');
    }



//   protected function actions(): array
//     {
//         $user = auth()->user();
//         $isHR = $user?->activeRoleIs('HR') || $user?->activeRoleIs('super-admin');

//         $actions = [];

//         if ($isHR) {
//             // FIX: Added 'export_excel' as the second argument!
//             $actions[] = Action::make(__('general.export-excel'), 'export_excel', function (): mixed {
                
//                 $query = $this->appliedQuery(); 
//                 $data = $query->get();

//                 $userSelectedColumns = $this->columns; 

//                 $visibleColumnCodes = collect($this->resolveColumns())
//                     ->filter(fn ($column) => $column->isVisible()) 
//                     ->filter(function ($column) use ($userSelectedColumns) {
//                         if (empty($userSelectedColumns)) return true;
//                         return in_array($column->code(), $userSelectedColumns);
//                     })
//                     ->filter(fn ($column) => $column->label() !== __('general.actions'))
//                     ->map(fn ($column) => $column->code()) 
//                     ->toArray();

//                 $filename = 'contract_types_' . now()->format('Y-m-d') . '.xlsx';

//                 return \Maatwebsite\Excel\Facades\Excel::download(
//                     new \App\Exports\ContractTypeExport($data, $visibleColumnCodes), 
//                     $filename
//                 );
//             })->standalone(); 
//         }

//         return $actions;
//     }  

    /**
     * NEW METHOD: Handle the Excel Export
     */
    // public function exportExcel(): BinaryFileResponse
    // {
    //     // $this->appliedQuery() automatically captures the current filtered, 
    //     // globally searched, and sorted state of the data table.
    //     $query = $this->appliedQuery();
        
    //     $data = $query->get();

    //     $filename = 'contract_types_' . now()->format('Y-m-d') . '.xlsx';

    //     // Return the file download directly from the Livewire component
    //     return Excel::download(new ContractTypeExport($data), $filename);
    // }
// public function exportExcel(): BinaryFileResponse
//     {
//         $query = $this->appliedQuery();
//         $data = $query->get();

//         $userSelectedColumns = $this->columns; 

//         $visibleColumns = collect($this->resolveColumns())
//             // 1. Keep columns that pass backend visibility rules
//             ->filter(fn ($column) => $column->isVisible()) 
//             // 2. Keep columns toggled ON in the UI
//             ->filter(function ($column) use ($userSelectedColumns) {
//                 if (empty($userSelectedColumns)) return true;
//                 return in_array($column->code(), $userSelectedColumns);
//             })
//             // 3. NEW: Explicitly exclude the Actions column by its label
//             ->filter(fn ($column) => $column->label() !== __('general.actions'))
//             // 4. Extract the labels to pass to the Excel header
//             ->map(fn ($column) => $column->label()) 
//             ->toArray();

//         $filename = 'contract_types_' . now()->format('Y-m-d') . '.xlsx';

//         return Excel::download(new ContractTypeExport($data, $visibleColumns), $filename);
//     }
// }

public function exportExcel():BinaryFileResponse
    {

        $query = $this->appliedQuery(); 
        
        $data = $query->get();

        $userSelectedColumns = $this->columns; 

        // Get the static CODES instead of the translated LABELS
        $visibleColumnCodes = collect($this->resolveColumns())
            ->filter(fn ($column) => $column->isVisible()) 
            ->filter(function ($column) use ($userSelectedColumns) {
                if (empty($userSelectedColumns)) return true;
                return in_array($column->code(), $userSelectedColumns);
            })
            ->filter(fn ($column) => $column->label() !== __('general.actions'))
            ->map(fn ($column) => $column->code()) 
            ->toArray();

        $filename = 'contract_types_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new ContractTypeExport($data, $visibleColumnCodes), 
            $filename
        );
    }
}