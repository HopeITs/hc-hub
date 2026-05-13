<?php

namespace App\Livewire\Basics;

use App\Models\AnnualLeaveRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class AnnualLeaveRuleTable extends LivewireTable
{
    protected string $model = AnnualLeaveRule::class;

    protected $listeners = [
        'refreshAnnualLeaveRuleTable' => '$refresh',
        'delete',
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

        $isHR = $user?->activeRoleIs('HR') || $user?->activeRoleIs('super-admin');

        return [
            Column::make(__('general.days_per_year'), 'days')
                ->displayUsing(
                    fn($value) => $value . ' ' . trans_choice('general.day', $value) 
                )
                ->sortable()->searchable(),

            Column::make(__('general.day_per_month'), 'day_per_month')
                ->displayUsing(
                    fn($value) => $value . ' ' . trans_choice('general.day', $value)
                )
                ->sortable()->searchable(),

            Column::make(__('general.years_of_volunteering'), 'years_of_volunteering')
                ->displayUsing(
                    fn($value) => $value . ' ' . trans_choice('general.year', $value)
                )
                ->sortable()
                ->searchable(),

            Column::make(__('general.actions'), function (Model $m): string {
                $id = $m->id;

                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="$dispatch(\'openAnnualLeaveRuleEdit\', { id: ' . $id . ' })" />'
                );
                $deleteBtn = Blade::render(
                    '<x-table.delete-btn wire:click="confirmDelete(' . $id . ')" :modelId="' . $id . '" />'
                );

                return '<div class="flex items-center justify-center gap-2">' . $editBtn . $deleteBtn . '</div>';
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
        AnnualLeaveRule::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();

        $this->dispatch('refreshAnnualLeaveRuleTable');
    }
}
