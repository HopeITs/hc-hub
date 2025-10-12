<?php

namespace App\Livewire\ChecklistItems;

use App\Models\Checklist;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Enums\Direction;
use RamonRietdijk\LivewireTables\Enums\SearchScope;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class DoneRequestsTable extends LivewireTable
{
    protected string $model = Checklist::class;

    protected bool $showFilters = true;

    protected function query(): Builder
    {
        $employee = auth()->user()->profile ?? null;
        if (!$employee) {
            return Checklist::query()->whereRaw('1 = 0');
        }

        $department = $employee->contracts()->with('position.department')->first()?->position?->department?->name;

        if (!in_array($department, ['IT', 'Communication', 'HR'])) {
            return Checklist::query()->whereRaw('1 = 0');
        }

        $deptEmployeeIds = Employee::whereHas('contracts.position.department', function ($q) use ($department) {
            $q->where('name', $department);
        })->pluck('id');

        return Checklist::query()
            ->with(['employee', 'item', 'delivered', 'receiver'])
            ->where(function (Builder $q) use ($deptEmployeeIds) {
                $q->whereIn('delivered_id', $deptEmployeeIds)->where('delivered_status', 'delivered')
                    ->orWhereIn('receiver_id', $deptEmployeeIds)->where('returned_status', 'received');
            });
    }

    protected function columns(): array
    {
        $employeeOptions = Employee::query()
            ->with('contracts.position.department')
            ->get()
            ->pluck('name', 'name')
            ->unique()
            ->sort()
            ->toArray();

        $itemOptions = Checklist::query()
            ->whereHas('item')
            ->with('item')
            ->get()
            ->pluck('item.name', 'item.name')
            ->unique()
            ->sort()
            ->toArray();

        $noteOptions = Checklist::query()
            ->whereNotNull('notes')
            ->distinct()
            ->orderBy('notes')
            ->pluck('notes', 'notes')
            ->take(50)
            ->toArray();

        $actionOptions = [
            'delivered' => __('general.delivered'),
            'received' => __('general.received'),
        ];

        $counterpartyOptions = Checklist::query()
            ->with(['employee', 'receiver', 'delivered'])
            ->get()
            ->map(function ($row) {
                if ($row->returned_status === 'received') {
                    return $row->employee?->name ?? $row->delivered?->name;
                }
                if ($row->delivered_status === 'delivered') {
                    return $row->receiver?->name ?? $row->employee?->name;
                }
                return null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->mapWithKeys(fn($name) => [$name => $name])
            ->toArray();

        return [
            SelectColumn::make(__('general.employee'))
                ->options($employeeOptions)
                ->displayUsing(function (mixed $value, Model $row): string {
                    if ($row->returned_status === 'received') {
                        return $row->receiver?->name ?? '—';
                    }
                    if ($row->delivered_status === 'delivered') {
                        return $row->delivered?->name ?? '—';
                    }
                    return '—';
                })
                ->sortable()
                ->searchable(),

            SelectColumn::make(__('general.action'))
                ->options($actionOptions)
                ->displayUsing(function (mixed $value, Model $row): string {
                    return $row->returned_status === 'received'
                        ? __('general.received')
                        : ($row->delivered_status === 'delivered' ? __('general.delivered') : '-');
                })
                ->sortable(function (Builder $builder, Direction $direction): void {
                    $dir = $direction->value;
                    $builder->orderByRaw("
                        CASE
                            WHEN returned_status = 'received' THEN 1
                            WHEN delivered_status = 'delivered' THEN 2
                            ELSE 3
                        END {$dir}
                    ");
                })
                ->searchable(),

            SelectColumn::make(__('general.item'), 'item.name')
                ->options($itemOptions)
                ->sortable()
                ->searchable(),

            SelectColumn::make(__('general.counterparty'))
                ->options($counterpartyOptions)
                ->displayUsing(function (mixed $value, Model $row): string {
                    if ($row->returned_status === 'received') {
                        return $row->employee?->name ?? ($row->delivered?->name ?? '—');
                    }
                    if ($row->delivered_status === 'delivered') {
                        return $row->receiver?->name ?? ($row->employee?->name ?? '—');
                    }
                    return '—';
                })
                ->sortable()
                ->searchable(),

            Column::make(__('general.dates'))
                ->displayUsing(function (mixed $value, Model $row): string {
                    $requestDate = $row->created_at?->format('Y-m-d') ?? '-';

                    if ($row->returned_status === 'received') {
                        $actionDate = $row->returned_at?->format('Y-m-d') ?? '-';
                        return "{$requestDate} → " . __('general.received_at') . ": {$actionDate}";
                    }

                    if ($row->delivered_status === 'delivered') {
                        $actionDate = $row->delivered_at?->format('Y-m-d') ?? '-';
                        return "{$requestDate} → " . __('general.delivered_at') . ": {$actionDate}";
                    }

                    return "{$requestDate} → -";
                })
                ->sortable(function (Builder $builder, Direction $direction): void {
                    $dir = $direction->value;
                    $builder->orderByRaw("COALESCE(returned_at, delivered_at) {$dir}");
                }),

            SelectColumn::make(__('general.note'), 'notes')
                ->options($noteOptions)
                ->sortable()
                ->searchable(),
        ];
    }
}
