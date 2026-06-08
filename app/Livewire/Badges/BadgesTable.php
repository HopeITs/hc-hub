<?php

namespace App\Livewire\Badges;

use App\Enums\BadgeType;
use App\Models\Badge;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Illuminate\Support\Facades\DB;

class BadgesTable extends LivewireTable
{
    protected string $model = Badge::class;

    protected $listeners = [
        'delete',
        'refreshBadgesTable' => '$refresh',
    ];

    protected function query(): Builder
    {
        return $this->model()::query()->with(['employee']);
    }

    protected function columns(): array
    {
        return [
            SelectColumn::make(trans_choice('general.employee', 1), 'employee_id')
                ->options($this->employeeOptions())
                ->displayUsing(fn($v, Model $m) => $m->employee?->name ?? '—')
                ->sortable()
                ->searchable(),

            SelectColumn::make(trans('general.type'), 'type')
                ->options($this->badgeTypeOptions())
                ->displayUsing(fn($v, Model $m) => BadgeType::getLocalizedName((int)($m->type ?? 0)))
                ->sortable()
                ->searchable(),

            SelectColumn::make(trans_choice('general.program', 1), 'program_id')
                ->options($this->programOptions())
                ->displayUsing(fn($v, Model $m) => $m->employee?->latestActiveContract()?->position?->program?->name ?? '—')
                // ->sortable()
                ->searchable(function (Builder $query, $value) {
                    $query->whereHas('employee.contracts.position.program', function ($q) use ($value) {
                        $q->where('id', $value);
                    });
                }),

            SelectColumn::make(trans_choice('general.department', 1), 'department_id')
                ->options($this->departmentOptions())
                ->displayUsing(fn($v, Model $m) => $m->employee?->latestActiveContract()?->position?->department?->name ?? '—')
                // ->sortable()
                ->searchable(function (Builder $query, $value) {
                    $query->whereHas('employee.contracts.position.department', function ($q) use ($value) {
                        $q->where('id', $value);
                    });
                }),

            Column::make(trans('general.date'), 'date')
                ->displayUsing(fn($v, Model $m) => optional($m->date)->format('Y-m-d') ?? '—')
                ->sortable(),

            Column::make(__('general.actions'), function (Model $m): string {

                $id = $m->getKey();

                $viewBtn = Blade::render(
                    '<button
                        type="button"
                        wire:click="$dispatch(\'open-badge-modal\', { id: {{ $id }} })"
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
                    </button>', ['id' => $id]
                );

                $deleteBtn = Blade::render('<x-table.delete-btn :modelId="$id" />', ['id' => $id]);

                return '<div class="flex items-center justify-center gap-2">' . $viewBtn . $deleteBtn . '</div>';
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
        DB::transaction(fn() => Badge::findOrFail($payload['id'])->delete());

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();

        $this->dispatch('refreshBadgesTable');
    }

    protected function badgeTypeOptions(): array
    {
        $values = BadgeType::getValues();
        $out = [];
        foreach ($values as $v) {
            $out[$v] = BadgeType::getLocalizedName((int)$v);
        }
        return $out;
    }

    protected function employeeOptions(): array
    {
        return Employee::query()
            ->select(['id', 'first_name_en', 'first_name_ar' , 'last_name_en', 'last_name_ar'])
            ->orderBy(transColumn('first_name'))
            ->get()
            ->mapWithKeys(fn($e) => [$e->id => $e->name])
            ->all();
    }

    protected function programOptions(): array
    {
        return Program::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected function departmentOptions(): array
    {
        return Department::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
