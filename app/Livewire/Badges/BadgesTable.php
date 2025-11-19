<?php

namespace App\Livewire\Badges;

use App\Enums\BadgeType;
use App\Models\Badge;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class BadgesTable extends LivewireTable
{
    protected string $model = Badge::class;

    protected function query(): Builder
    {
        return $this->model()::query()->with(['employee']);
    }

    protected function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),

            Column::make(trans('general.employee'), 'employee_id')
                ->displayUsing(fn($v, Model $m) => $m->employee?->name ?? '—')
                ->sortable()
                ->searchable(function (Builder $builder, mixed $search): void {
                    $builder->whereHas('employee', function (Builder $q) use ($search) {
                        $q->where('name_en', 'like', "%{$search}%")
                          ->orWhere('name_ar', 'like', "%{$search}%");
                    });
                }),

            SelectColumn::make(trans('general.type'), 'type')
                ->options($this->badgeTypeOptions())
                ->displayUsing(function ($v, Model $m) {
                    $value = is_numeric($m->type) ? (int)$m->type : (int)($v ?? 0);
                    return BadgeType::getLocalizedName($value);
                })
                ->sortable(),

            Column::make(trans('general.date'), 'date')
                ->displayUsing(fn ($v, Model $m) => optional($m->date)->format('Y-m-d') ?? '—')
                ->sortable(),

            Column::make(trans_choice('general.program', 1), function (Model $m): string {
                $program = $m->employee?->latestActiveContract()?->position?->program?->name ?? '—';
                return e($program);
            })->sortable(),

            Column::make(trans_choice('general.department', 1), function (Model $m): string {
                $dept = $m->employee?->latestActiveContract()?->position?->department?->name ?? '—';
                return e($dept);
            })->sortable(),

            Column::make(__('Actions'), function (Model $m): string {
                return Blade::render(
                    '<x-primary-button type="button"
                        x-data
                        x-on:click="$dispatch(\'open-badge\', { id: ' . $m->getKey() . ' })"
                        class="!w-auto">'
                        . e(trans('general.show-more')) .
                    '</x-primary-button>'
                );
            })->asHtml(),
        ];
    }

    protected function filters(): array
    {
        return [
            SelectFilter::make(trans('general.type'), 'type')
                ->options(['' => trans('general.all')] + $this->badgeTypeOptions()),

            SelectFilter::make(trans('general.employee'), 'employee_id')
                ->options(['' => trans('general.all')] + $this->employeeOptions()),
        ];
    }

    protected function badgeTypeOptions(): array
    {
        $values = \App\Enums\BadgeType::getValues();
        $out = [];
        foreach ($values as $v) {
            $out[$v] = \App\Enums\BadgeType::getLocalizedName((int)$v);
        }
        return $out;
    }

    protected function employeeOptions(): array
    {
        return Employee::query()
            ->select(['id', 'name_en', 'name_ar'])
            ->orderBy(transColumn('name'))
            ->get()
            ->mapWithKeys(fn ($e) => [$e->id => $e->name])
            ->all();
    }
}
