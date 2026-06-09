<?php

namespace App\Livewire\ProgramManagement\DefaultDeliverable;

use App\Models\ChecklistItem;
use App\Models\City;
use App\Models\DefaultDeliverable as DefaultDeliverableModel;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class DefaultDeliverablesTable extends LivewireTable
{
    protected string $model = DefaultDeliverableModel::class;

    protected $listeners = [
        'delete',
        'refreshDefaultDeliverablesTable' => '$refresh',
    ];

    protected function query(): Builder
    {
        return $this->model()::query()->with(['employee', 'city', 'item']);
    }

    protected function globalSearchColumns(): array
    {
        $loc = app()->getLocale();
        $name = $loc === 'ar' ? 'name_ar' : 'name_en';

        return [
            'id',
            "employee.$name",
            "city.$name",
            'item.name',
        ];
    }

    protected function employeeOptions(): array
    {
        return Employee::query()
            ->whereHas('contracts', function ($query) {
                $query->whereHas('position', function ($subQuery) {
                    $subQuery->whereHas('department', function ($q) {
                        $q->whereIn('code', ['IT', 'CT', 'HR']);
                    });
                });
            })
            ->orderBy(transColumn('first_name'))
            ->get()
            ->mapWithKeys(fn($e) => [$e->id => $e->name])
            ->toArray();
    }

    protected function cityOptions(): array
    {
        $loc = app()->getLocale();
        $name = $loc === 'ar' ? 'name_ar' : 'name_en';

        return City::query()
            ->whereHas('centers')
            ->pluck($name, $name)
            ->toArray();
    }

    protected function itemOptions(): array
    {
        return ChecklistItem::query()->orderBy('order')->pluck('name', 'name')->toArray();
    }

    protected function columns(): array
    {
        $loc = app()->getLocale();
        $name = $loc === 'ar' ? 'name_ar' : 'name_en';

        return [

            Column::make('ID', 'id')->sortable()->searchable(),

            // SelectColumn::make(trans_choice('general.employee', 1), 'employee.' . $name)
            //     ->options($this->employeeOptions())
            //     ->sortable()
            //     ->searchable(),
            SelectColumn::make(trans_choice('general.employee', 1), 'employee_id')
            ->options($this->employeeOptions())
            ->displayUsing(fn($v, Model $m) => $m->employee?->name ?? '—')
            ->sortable()
            ->searchable(function (Builder $query, $value) {
                $query->whereHas('employee', function ($q) use ($value) {
                    $q->where('id', $value);
                });
            }),
            SelectColumn::make(trans_choice('general.city', 1), 'city.' . $name)
                ->options($this->cityOptions())
                ->sortable()
                ->searchable(),





            SelectColumn::make(__('general.item'), 'item.name')
                ->options(
                    ChecklistItem::orderBy('order')
                        ->pluck('name', 'name')
                        ->toArray()
                )
                ->sortable()
                ->searchable(),

            Column::make(__('general.actions'), function (Model $m): string {
                $user = auth()->user()->load('profile');
                $role = $user->myRole();

                $roleArr = explode('-', $role);

                $canEdit = true;

                if ($roleArr[0] === 'HR') {

                    if ($roleArr[1] === 'SYR') {
                        $canEdit = true;
                    } else {
                        $citySlug = $roleArr[1];

                        if ($m->city->slug !== $citySlug) {
                            $canEdit = false;
                        }
                    }
                }

                if (!$canEdit) {
                    return ''; // hide buttons
                }

                $id = $m->getKey();

                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="$dispatch(\'openDefaultDeliverableEdit\', { id: ' . $id . ' })" />'
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
        DefaultDeliverableModel::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();

        $this->dispatch('refreshDefaultDeliverablesTable');
    }
}
