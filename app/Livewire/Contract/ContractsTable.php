<?php

namespace App\Livewire\Contract;

use App\Enums\SalaryPer;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
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
            ]);
    }

    protected function globalSearchColumns(): array
    {
        return ['id'];
    }

    protected function columns(): array
    {
        $loc = app()->getLocale();

        return [
            Column::make('ID', 'id')->sortable(),

            Column::make(trans_choice('general.employee', 2), function (Model $m) use ($loc) {
                return $m->employee?->name ?? '—';
            }),

            Column::make(trans_choice('general.positions', 1), function (Model $m) {
                return $m->position?->title
                    ?? $m->position?->requiredPosition?->title
                    ?? ('#' . $m->position_id);
            }),

            Column::make(trans_choice('general.contract-type', 1), function (Model $m) use ($loc) {
                return $m->contractType?->name ?? '—';
            }),

            Column::make(trans_choice('general.department', 1), function (Model $m) {
                return $m->position?->department?->name ?? '—';
            }),

            Column::make(trans_choice('general.job-level', 1), function (Model $m) use ($loc) {
                return $m->position?->jobLevel?->name ?? '—';
            }),

            Column::make(__('general.period'), function (Model $m) {
                return Blade::render('<x-period :start="$start" :end="$end" />', [
                    'start' => $m->start_date,
                    'end'   => $m->end_date,
                ]);
            })->asHtml(),

            Column::make(__('general.yearly-leave-days'), 'yearly_leave_days')
                ->displayUsing(fn ($v) => $v ?? '—')
                ->sortable(),

            Column::make(__('general.salary-per'), function (Model $m) {
                $value = $m->salary_per instanceof \BackedEnum ? $m->salary_per->value : (string) $m->salary_per;
                $per   = SalaryPer::getLocalizedName($value);

                return Blade::render('<x-badge :color="$color">{{$text}}</x-badge>', [
                    'color' => $per['color'] ?? 'gray',
                    'text'  => $per['value'] ?? $value,
                ]);
            })->asHtml(),

            Column::make(__('general.weekly-hours'), 'weekly_hours')
                ->displayUsing(fn ($v) => $v ?? '—'),

            Column::make(__('general.status'), function (Model $m) {
                return Blade::render(
                    '<x-badge :color="$c">{{$t}}</x-badge>',
                    [
                        'c' => $m->is_active ? 'green' : 'red',
                        't' => $m->is_active ? __('general.active') : __('general.inactive'),
                    ]
                );
            })->asHtml(),

            Column::make(__('Actions'), function (Model $m): string {
                $view = Blade::render(
                    <<<'BLADE'
<a href="{{ route('contracts.show', $id) }}"
   class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-gray-100 dark:hover:bg-gray-800"
   title="{{ __('general.view') }}">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
         stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z" />
        <path stroke-linecap="round" stroke-linejoin="round"
             d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
</a>
BLADE
                    , ['id' => $m->getKey()]
                );

                $edit = Blade::render(
                    '<x-table.edit-btn :href="route(\'contracts.edit\', $id)" />',
                    ['id' => $m->getKey()]
                );

                // ✅ FIXED: just pass modelId, no extra wire:click
                $del = Blade::render(
                    '<x-table.delete-btn :modelId="$id" />',
                    ['id' => $m->getKey()]
                );

                return '<div class="flex items-center justify-center gap-2">' . $view . $edit . $del . '</div>';
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
        Contract::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();

        $this->dispatch('refreshContractsTable');
    }
}
