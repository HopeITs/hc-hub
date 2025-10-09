<?php

namespace App\Livewire\ChecklistItems;

use App\Models\Checklist;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Enums\Direction;
use RamonRietdijk\LivewireTables\Enums\SearchScope;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class DoneRequestsTable extends LivewireTable
{
    protected string $model = Checklist::class;

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
                $q->whereIn('delivered_id', $deptEmployeeIds)->where('delivered_status', 'delivered')->orWhereIn('receiver_id', $deptEmployeeIds)->where('returned_status', 'received');
            });
    }

    protected function columns(): array
    {
        return [
            Column::make(__('general.employee'))
                ->displayUsing(function (mixed $value, Model $row): string {
                    if ($row->returned_status === 'received') {
                        return $row->receiver?->name ?? '—';
                    }
                    if ($row->delivered_status === 'delivered') {
                        return $row->delivered?->name ?? '—';
                    }
                    return '—';
                })
                ->searchable(function (Builder $builder, mixed $search, SearchScope $scope): void {
                    $builder->where(function (Builder $q) use ($search): void {
                        $q->whereHas('receiver', function (Builder $qq) use ($search): void {
                            $qq->where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%");
                        })->orWhereHas('delivered', function (Builder $qq) use ($search): void {
                            $qq->where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%");
                        });
                    });
                }),

            Column::make(__('general.action'))->displayUsing(function (mixed $value, Model $row): string {
                return $row->returned_status === 'received' ? 'Received' : ($row->delivered_status === 'delivered' ? 'Delivered' : '-');
            }),

            Column::make(__('general.item'), 'item.name')->sortable()->searchable(),

            Column::make(__('general.counterparty'))->displayUsing(function (mixed $value, Model $row): string {
                if ($row->returned_status === 'received') {
                    return $row->employee?->name ?? ($row->delivered?->name ?? '—');
                }
                if ($row->delivered_status === 'delivered') {
                    return $row->receiver?->name ?? ($row->employee?->name ?? '—');
                }
                return '—';
            }),

            Column::make(__('general.date'))
                ->displayUsing(function (mixed $value, Model $row): string {
                    return $row->returned_status === 'received' ? $row->returned_at?->format('Y-m-d') ?? '-' : $row->delivered_at?->format('Y-m-d') ?? '-';
                })
                ->sortable(function (Builder $builder, Direction $direction): void {
                    $dir = $direction->value;
                    $builder->orderByRaw("COALESCE(returned_at, delivered_at) {$dir}");
                }),

            Column::make(__('general.note'), 'notes')->searchable()->sortable(),
        ];
    }

    protected function filters(): array
    {
        return [
            SelectFilter::make(__('general.action'), function (Builder $builder, mixed $value): void {
                if ($value === 'delivered') {
                    $builder->where('delivered_status', 'delivered');
                } elseif ($value === 'received') {
                    $builder->where('returned_status', 'received');
                }
            })->options([
                'delivered' => 'Delivered',
                'received' => 'Received',
            ]),

            SelectFilter::make(__('general.note'), function (Builder $builder, mixed $value): void {
                if ($value && $value !== 'all') {
                    $builder->where('notes', 'like', "%{$value}%");
                }
            })->options(['all' => __('general.all')] + Checklist::query()->whereNotNull('notes')->distinct()->orderBy('notes')->pluck('notes', 'notes')->take(50)->toArray()),
        ];
    }
}
