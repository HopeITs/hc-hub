<?php

namespace App\Livewire\Basics;

use App\Exports\AnnualLeaveRuleExport;
use App\Livewire\Concerns\AuthorizesHrAction;
use App\Models\AnnualLeaveRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Maatwebsite\Excel\Facades\Excel;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnnualLeaveRuleTable extends LivewireTable
{
    use AuthorizesHrAction;

    protected string $model = AnnualLeaveRule::class;

    protected $listeners = [
        'refreshAnnualLeaveRuleTable' => '$refresh',
        'delete',
        'exportExcel' => 'exportExcel',
    ];

    protected function query(): Builder
    {
        return AnnualLeaveRule::query();
    }

    protected function globalSearchColumns(): array
    {
        return ['id', 'days', 'day_per_month', 'years_of_volunteering'];
    }

    protected function columns(): array
    {
        $user = auth()->user();

        $isHR = (bool) $user?->activeRoleIs('HR', 'super-admin');

        return [
            Column::make(__('general.days_per_year'), 'days')
                ->displayUsing(
                    fn ($value) => $value.' '.trans_choice('general.day', $value)
                )
                ->sortable()->searchable(),

            Column::make(__('general.day_per_month'), 'day_per_month')
                ->displayUsing(
                    fn ($value) => $value.' '.trans_choice('general.day', $value)
                )
                ->sortable()->searchable(),

            Column::make(__('general.years_of_volunteering'), 'years_of_volunteering')
                ->displayUsing(
                    fn ($value) => $value.' '.trans_choice('general.year', $value)
                )
                ->sortable()
                ->searchable(),

            Column::make(__('general.actions'), function (Model $m): string {
                $id = $m->id;

                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="$dispatch(\'openAnnualLeaveRuleEdit\', { id: '.$id.' })" />'
                );
                $deleteBtn = Blade::render(
                    '<x-table.delete-btn wire:click="confirmDelete('.$id.')" :modelId="'.$id.'" />'
                );

                return '<div class="flex items-center justify-center gap-2">'.$editBtn.$deleteBtn.'</div>';
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

        AnnualLeaveRule::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();

        $this->dispatch('refreshAnnualLeaveRuleTable');
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

        $filename = 'annual_leave_rules_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new AnnualLeaveRuleExport($data, $visibleColumnCodes),
            $filename
        );
    }
}
