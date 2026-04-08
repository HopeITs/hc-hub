<?php

namespace App\Livewire\Basics;

use App\Models\ChecklistItem;
use App\Enums\ChecklistItemOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class ChecklistItemTable extends LivewireTable
{
    protected string $model = ChecklistItem::class;

    protected $listeners = [
        'refreshChecklistItemTable' => '$refresh',
        'delete',
    ];

    protected function query(): Builder
    {
        return ChecklistItem::query();
    }

    protected function globalSearchColumns(): array
    {
        return ['id', 'name', 'order'];
    }

    protected function columns(): array
    {
        $user = auth()->user();

        $isHR = $user?->activeRoleIs('HR') || $user?->activeRoleIs('super-admin');

        return [
            Column::make('ID', 'id')->sortable()->searchable(),

            Column::make(__('general.name'), 'name')
                ->sortable()
                ->searchable(),

            SelectColumn::make(__('general.order'), 'order')
                ->options([
                    ChecklistItemOrder::ProbationContract =>
                    ChecklistItemOrder::getLocalizedName(ChecklistItemOrder::ProbationContract)['value'],

                    ChecklistItemOrder::AnnualContract =>
                    ChecklistItemOrder::getLocalizedName(ChecklistItemOrder::AnnualContract)['value'],

                    ChecklistItemOrder::Resignation =>
                    ChecklistItemOrder::getLocalizedName(ChecklistItemOrder::Resignation)['value'],
                ])
                ->displayUsing(fn($value) => Blade::render(
                    '<x-badge color="' .
                        ChecklistItemOrder::getLocalizedName($value)['color'] .
                        '">' .
                        e(ChecklistItemOrder::getLocalizedName($value)['value']) .
                        '</x-badge>'
                ))
                ->sortable()
                ->searchable()
                ->asHtml(),

            Column::make(__('general.actions'), function (Model $m): string {
                $id = $m->id;

                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="$dispatch(\'openChecklistItemEdit\', { id: ' . $id . ' })" />'
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
        ChecklistItem::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

        $this->dispatch('refreshChecklistItemTable');
    }
}
