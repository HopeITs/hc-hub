<?php

namespace App\Livewire\Contract;

use App\Enums\SalaryPer;
use App\Exports\ContractExport;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Maatwebsite\Excel\Facades\Excel;
use RamonRietdijk\LivewireTables\Columns\BooleanColumn;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContractsTable extends LivewireTable
{
    protected string $model = Contract::class;

    protected $listeners = [
        'delete',
        'refreshContractsTable' => '$refresh',
        'exportExcel' => 'exportExcel',
    ];

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
        $contract = Contract::findOrFail($payload['id']);
        Gate::authorize('delete', $contract);

        DB::transaction(fn () => $contract->delete());

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();

        $this->dispatch('refreshContractsTable');
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

        $filename = 'contracts_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new ContractExport($data, $visibleColumnCodes),
            $filename,
        );
    }

    protected function query(): Builder
    {
        return $this->model()::query()
            ->with([
                'employee',
                'position.department',
                'position.jobLevel',
                'contractType',
                'leaveBalances',
            ])
            ->when(
                auth()->user()->activeCityId(),
                fn (Builder $q, int $cityId) => $q->whereHas('employee', fn (Builder $e) => $e->where('city_id', $cityId)),
            );
    }

    protected function globalSearchColumns(): array
    {
        $loc = app()->getLocale();

        return [
            'id',
            'employee.'.($loc === 'ar' ? 'first_name_ar' : 'first_name_en'),
            'position.'.($loc === 'ar' ? 'title_ar' : 'title_en'),
            'contractType.'.($loc === 'ar' ? 'name_ar' : 'name_en'),
            'position.department.name',
        ];
    }

    protected function columns(): array
    {
        $loc = app()->getLocale();
        $name = $loc === 'ar' ? 'name_ar' : 'name_en';
        $title = $loc === 'ar' ? 'title_ar' : 'title_en';
        $canUpdate = auth()->user()?->hasActivePermission('contracts.update') ?? false;
        $canDelete = auth()->user()?->hasActivePermission('contracts.delete') ?? false;

        return [
            Column::make(trans_choice('general.employee', 1), 'employee')
                ->displayUsing(fn ($v, Model $m) => $m->employee?->name ?? '—')
                ->searchable(function (Builder $query, $value) {
                    $parts = array_filter(explode(' ', trim($value)));

                    $query->whereHas('employee', function (Builder $q) use ($parts) {
                        foreach ($parts as $part) {
                            $q->where(function (Builder $inner) use ($part) {
                                $inner->where('first_name_en', 'LIKE', "%{$part}%")
                                    ->orWhere('first_name_ar', 'LIKE', "%{$part}%")
                                    ->orWhere('last_name_en', 'LIKE', "%{$part}%")
                                    ->orWhere('last_name_ar', 'LIKE', "%{$part}%");
                            });
                        }
                    });
                }),
            SelectColumn::make(trans_choice('general.positions', 1), 'position.'.$title)
                ->options(Position::orderBy($title)->pluck($title, $title)->toArray())
                ->sortable()->searchable(),

            SelectColumn::make(trans_choice('general.contract-type', 1), 'contractType.'.$name)
                ->options(ContractType::orderBy($name)->pluck($name, $name)->toArray())
                ->sortable()->searchable(),

            SelectColumn::make(trans_choice('general.department', 1), 'position.department.name')
                ->options(Department::orderBy('name')->pluck('name', 'name')->toArray())
                ->sortable()->searchable(),

            SelectColumn::make(trans_choice('general.job-level', 1), 'position.jobLevel.'.$name)
                ->options(JobLevel::orderBy('grade')->pluck($name, $name)->toArray())
                ->sortable()->searchable(),

            Column::make(__('general.duration'), 'duration')
                ->displayUsing(fn ($v, Model $m) => $m->duration.' '.__('general.months'))
                ->sortable(),

            Column::make(__('general.period'), 'period')
                ->displayUsing(fn ($v, Model $m) => Blade::render('<x-period :start="$start" :end="$end" />', [
                    'start' => $m->start_date,
                    'end' => $m->end_date,
                ]))->asHtml(),

            Column::make(__('general.yearly-leave-days'), 'leaveBalances')
                ->displayUsing(fn ($m) => $m[0]->deserved_formatted ?? 0)
                ->sortable()->asHtml(),

            SelectColumn::make(__('general.salary-per'), 'salary_per')
                ->options(
                    collect(SalaryPer::getValues())->mapWithKeys(fn ($value) => [
                        $value => SalaryPer::getLocalizedName((int) $value)['value'],
                    ])->toArray(),
                )
                ->displayUsing(function ($value) {
                    $value = $value instanceof SalaryPer ? $value->value : (int) $value;

                    $per = SalaryPer::getLocalizedName($value);

                    return Blade::render('<x-badge :color="$color">{{$text}}</x-badge>', [
                        'color' => $per['color'] ?? 'gray',
                        'text' => $per['value'] ?? $value,
                    ]);
                })
                ->sortable()
                ->searchable()
                ->asHtml(),

            Column::make(__('general.weekly-hours'), 'weekly_hours')
                ->displayUsing(fn ($v) => $v ? "{$v} ".trans_choice('general.hour', 2) : '—')
                ->sortable(),

            BooleanColumn::make(__('general.status'), 'is_active')->searchable(),

            Column::make(__('general.actions'), function (Model $m) use ($canUpdate, $canDelete): string {
                $view = Blade::render('<x-table.show-btn :href="route(\'contracts.show\', $id)" />', ['id' => $m->getKey()]);
                $edit = $canUpdate
                    ? Blade::render('<x-table.edit-btn :href="route(\'contracts.edit\', $id)" />', ['id' => $m->getKey()])
                    : '';
                $delete = $canDelete
                    ? Blade::render('<x-table.delete-btn :modelId="$id" />', ['id' => $m->getKey()])
                    : '';

                return '<div class="flex items-center justify-center gap-2">'.$view.$edit.$delete.'</div>';
            })->asHtml(),
        ];
    }
}
