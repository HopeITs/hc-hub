<?php

namespace App\Livewire\Contract;

use App\Enums\SalaryPer;
use App\Models\{Contract, Department, JobLevel, ContractType, Employee, Position};
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Support\Facades\{Blade, DB};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\{BooleanColumn, Column, SelectColumn};
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class ContractsTable extends LivewireTable
{
    protected string $model = Contract::class;

    protected $listeners = [
        'delete',
        'refreshContractsTable' => '$refresh',
    ];

    protected function query(): Builder
    {
        return $this->model()::query()
            ->with([
                'employee',
                'position.department',
                'position.jobLevel',
                'contractType',
                'leaveBalances'
            ]);
    }

    protected function globalSearchColumns(): array
    {
        $loc = app()->getLocale();
        return [
            'id',
            'employee.' . ($loc === 'ar' ? 'name_ar' : 'name_en'),
            'position.' . ($loc === 'ar' ? 'title_ar' : 'title_en'),
            'contractType.' . ($loc === 'ar' ? 'name_ar' : 'name_en'),
            'position.department.name',
        ];
    }

    protected function columns(): array
    {
        $loc = app()->getLocale();
        $name = $loc === 'ar' ? 'name_ar' : 'name_en';
        $title = $loc === 'ar' ? 'title_ar' : 'title_en';

        return [
            Column::make('ID', 'id')->sortable()->searchable(),

            SelectColumn::make(trans_choice('general.employee', 1), 'employee.' . $name)
                ->options(Employee::orderBy($name)->pluck($name, $name)->toArray())
                ->sortable()->searchable(),

            SelectColumn::make(trans_choice('general.positions', 1), 'position.' . $title)
                ->options(Position::orderBy($title)->pluck($title, $title)->toArray())
                ->sortable()->searchable(),

            SelectColumn::make(trans_choice('general.contract-type', 1), 'contractType.' . $name)
                ->options(ContractType::orderBy($name)->pluck($name, $name)->toArray())
                ->sortable()->searchable(),

            SelectColumn::make(trans_choice('general.department', 1), 'position.department.name')
                ->options(Department::orderBy('name')->pluck('name', 'name')->toArray())
                ->sortable()->searchable(),

            SelectColumn::make(trans_choice('general.job-level', 1), 'position.jobLevel.' . $name)
                ->options(JobLevel::orderBy('grade')->pluck($name, $name)->toArray())
                ->sortable()->searchable(),

            Column::make(__('general.duration'), fn(Model $m) => $m->duration . ' ' . __('general.months'))
                ->sortable(),

            Column::make(__('general.period'), fn(Model $m) =>
            Blade::render('<x-period :start="$start" :end="$end" />', [
                'start' => $m->start_date,
                'end' => $m->end_date,
            ])
            )->asHtml(),

            Column::make(__('general.yearly-leave-days'), 'leaveBalances')
                ->displayUsing(fn($m) => $m[0]->deserved_formatted ?? 0)
                ->sortable()->asHtml(),

            SelectColumn::make(__('general.salary-per'), 'salary_per')
                ->options(
                    collect(SalaryPer::getValues())->mapWithKeys(fn($value) => [
                        $value => SalaryPer::getLocalizedName((int)$value)['value']
                    ])->toArray()
                )
                ->displayUsing(function ($value) {
                    $value = $value instanceof SalaryPer ? $value->value : (int)$value;

                    $per = SalaryPer::getLocalizedName($value);
                    return Blade::render('<x-badge :color="$color">{{$text}}</x-badge>', [
                        'color' => $per['color'] ?? 'gray',
                        'text'  => $per['value'] ?? $value,
                    ]);
                })
                ->sortable()
                ->searchable()
                ->asHtml(),


            Column::make(__('general.weekly-hours'), 'weekly_hours')
                ->displayUsing(fn($v) => $v ? "{$v} " . trans_choice('general.hour',2) : '—')
                ->sortable(),

            BooleanColumn::make(__('general.status'), 'is_active')->searchable(),


            Column::make(__('general.actions'), function (Model $m): string {
                $view = Blade::render('<x-table.show-btn :href="route(\'contracts.show\', $id)" />', ['id' => $m->getKey()]);
                $edit = Blade::render('<x-table.edit-btn :href="route(\'contracts.edit\', $id)" />', ['id' => $m->getKey()]);
                $delete = Blade::render('<x-table.delete-btn :modelId="$id" />', ['id' => $m->getKey()]);

                return '<div class="flex items-center justify-center gap-2">' . $view . $edit  . '</div>';
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
        DB::transaction(fn() => Contract::findOrFail($payload['id'])->delete());

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();

        $this->dispatch('refreshContractsTable');
    }
}
