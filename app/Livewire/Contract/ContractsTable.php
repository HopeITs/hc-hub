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
                ->options(Employee::pluck($name, $name)->toArray())
                ->sortable()->searchable(),

            SelectColumn::make(trans_choice('general.positions', 1), 'position.' . $title)
                ->options(Position::pluck($title, $title)->toArray())
                ->sortable()->searchable(),

            SelectColumn::make(trans_choice('general.contract-type', 1), 'contractType.' . $name)
                ->options(ContractType::pluck($name, $name)->toArray())
                ->sortable()->searchable(),

            SelectColumn::make(trans_choice('general.department', 1), 'position.department.name')
                ->options(Department::pluck('name', 'name')->toArray())
                ->sortable()->searchable(),

            SelectColumn::make(trans_choice('general.job-level', 1), 'position.jobLevel.' . $name)
                ->options(JobLevel::pluck($name, $name)->toArray())
                ->sortable()->searchable(),

            Column::make(__('general.duration'), fn(Model $m) => $m->duration() . ' ' . __('general.months'))
                ->sortable(),

            Column::make(__('general.period'), fn(Model $m) =>
            Blade::render('<x-period :start="$start" :end="$end" />', [
                'start' => $m->start_date,
                'end' => $m->end_date,
            ])
            )->asHtml(),

            Column::make(__('general.yearly-leave-days'), 'leaveBalances')
                ->displayUsing(fn($m) => $m[0]->deserved_formatted ?? 0)
                ->sortable(),

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
                ->displayUsing(fn($v) => $v ? "{$v} " . __('general.hours') : '—')
                ->sortable(),

            BooleanColumn::make(__('general.status'), 'is_active')->searchable(),


            Column::make(__('general.actions'), function (Model $m): string {
                $view = Blade::render(
                    '<a href="{{ route(\'contracts.show\', $id) }}"
                        class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-gray-100 dark:hover:bg-gray-800"
                        title="{{ __(\'general.view\') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                            class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12
                                   s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </a>', ['id' => $m->getKey()]
                );

                $edit = Blade::render('<x-table.edit-btn :href="route(\'contracts.edit\', $id)" />', ['id' => $m->getKey()]);
                $delete = Blade::render('<x-table.delete-btn :modelId="$id" />', ['id' => $m->getKey()]);

                return '<div class="flex items-center justify-center gap-2">' . $view . $edit . $delete . '</div>';
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
