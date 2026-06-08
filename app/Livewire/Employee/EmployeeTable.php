<?php

namespace App\Livewire\Employee;

use App\Models\City;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\BooleanColumn;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Enums\SearchScope;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeeExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeTable extends LivewireTable
{
    protected string $model = Employee::class;

    protected $listeners = ['delete', 'toggleItemStatusConfirmed','exportExcel' => 'exportExcel'];

    protected function query(): Builder
    {
        return $this->model()::query()
            ->with(['contracts.position.directManager', 'contracts.position.techManager', 'account', 'media'])->orderByDesc('is_active');
    }

    protected function globalSearchColumns(): array
    {
        return ['name', 'actual_dob'];
    }

    protected function managerOptions(): array
    {
        return Employee::query()
            ->select(['id', 'first_name_en', 'first_name_ar'])
            ->orderBy(transColumn('first_name'), 'asc')
            ->get()
            ->mapWithKeys(fn($e) => [
                $e->id => $e->first_name
            ])
            ->all();
    }

    protected function columns(): array
    {
        $user = auth()->user();
        return [
            Column::make('ID', 'id')->sortable(),
            Column::make(trans('general.photo'), function (Model $m): string {
                $src = $m->getFirstMediaUrl('avatar'); // use 'thumb' conversion if you have it
                return $src
                    ? '<img src="' . e($src) . '" alt="' . e($m->name) . '" class="object-cover border rounded-full size-12 border-1 border-primary" />'
                    : '<div class="w-12 h-12 bg-gray-200 rounded-full"></div>';
            })->asHtml(),
            Column::make(trans('general.name'), transColumn('name'))
                ->displayUsing(fn($v, Model $m) => $m->name) // uses accessor
                ->sortable()->searchable(),
            Column::make(trans_choice('general.role', 1), 'account')
                ->displayUsing(fn($v, Model $m) => $m->account?->myRole()),

            Column::make(trans('general.years-of-service'), 'years_of_service_formatted')->computed()->sortable(),

            Column::make(trans('general.join-at'), 'join_at')->computed()->sortable(),
            SelectColumn::make(trans_choice('general.city', 1), 'city.' . transColumn('name'))
                ->options(City::query()->whereNot('code', '000')->where('is_active', true)->get()->pluck(transColumn('name'), transColumn('name'))->toArray())
                ->sortable()->searchable(),

            SelectColumn::make(trans('general.direct_manager'), 'direct_manager')
                ->displayUsing(function ($v, Model $model) {
                    return $model->latestActiveContract()?->position->directManager?->employee()?->name ?? '—';
                })
                ->options($this->managerOptions())
                ->sortable()
                ->searchable(function (Builder $builder, mixed $search, SearchScope $scope): void {
                    $builder->whereHas('positions', function (Builder $builder) use ($search) {
                        $builder->whereHas('directManager', function (Builder $builder) use ($search) {
                            $builder->whereHas('contracts', function (Builder $builder) use ($search) {
                                $builder->whereHas('employee', function (Builder $builder) use ($search) {
                                    $builder->where('id', $search);
                                });
                            });
                        });
                    });
                }),

            SelectColumn::make(trans('general.tech_manager'), 'tech_manager')
                ->displayUsing(function ($v, Model $model) {
                    return $model->latestActiveContract()?->position->techManager?->employee()?->name ?? '—';
                })
                ->options($this->managerOptions())
                ->sortable()
                ->searchable(function (Builder $builder, mixed $search, SearchScope $scope): void {
                    $builder->whereHas('positions', function (Builder $builder) use ($search) {
                        $builder->whereHas('techManager', function (Builder $builder) use ($search) {
                            $builder->whereHas('contracts', function (Builder $builder) use ($search) {
                                $builder->whereHas('employee', function (Builder $builder) use ($search) {
                                    $builder->where('id', $search);
                                });
                            });
                        });
                    });
                }),
            SelectColumn::make(trans('general.position'), 'contracts.position.' . transColumn('title'))
                ->options(Position::query()->orderby(transColumn('title'),'asc')->get()->pluck(transColumn('title'), transColumn('title'))->toArray())
                ->sortable()->searchable(),


            Column::make(trans('general.work_email'),'work_email')
                ->displayUsing(fn($v, Model $m) => $m->account?->email ?? '—'),

            BooleanColumn::make(trans('general.is-active'), 'is_active')->searchable(),

            Column::make(__('general.actions'), function (Model $model): string {
                $isActive = $model->is_active ? 1 : 0;

                $toggleBtn = Blade::render(
                    '<x-forms.toggle-input class="p-2 m-2 text-xl" active="' . $isActive .
                    '" :model-id="' . $model->getKey() .
                    '" wire-event="confirmToggleItemStatus" />'
                );

                $viewBtn = Blade::render(
                    '<a  href="' . route('employees.show', ['employee' => $model->username]) . '" class="p-2 " title="View Profile">
                                <x-icons.eye class="transition text-secondary-light hover:text-primary" />
                            </a>'
                );

                $editBtn = Blade::render(
                    '<x-table.edit-btn href="' . route('employees.edit', $model->getKey()) . '" />'
                );

                $deleteBtn = Blade::render(
                    '<x-table.delete-btn :modelId="' . $model->getKey() . '" />'
                );

                // Wrap everything in a flex row
                return '<div class="flex items-center gap-1">' .
                    $toggleBtn . $viewBtn . $editBtn .
                    '</div>';

            })->visible($user->activeRoleIs('HR') || $user->activeRoleIs('super-admin'))->asHtml(),

        ];
    }


    public function confirmDelete($id): void
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete($payload): void
    {
        Employee::find($payload['id'])?->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }

    public function confirmToggleItemStatus($id): void
    {
        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->question()
            ->withConfirmButton(__('general.save'))
            ->onConfirm('toggleItemStatusConfirmed', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function toggleItemStatusConfirmed($payload): void
    {
        $item = Employee::findOrFail($payload['id']);
        $item->is_active = !$item->is_active;
        $item->save();

        LivewireAlert::title(__('general.item-updated'))
            ->success()->toast()->position('top-start')->show();
    }

        public function exportExcel(): BinaryFileResponse
    {
        $query = $this->appliedQuery(); 
        $data = $query->get();

        $userSelectedColumns = $this->columns; 

        $visibleColumnCodes = collect($this->resolveColumns())
            ->filter(fn ($column) => $column->isVisible()) 
            ->filter(function ($column) use ($userSelectedColumns) {
                if (empty($userSelectedColumns)) return true;
                return in_array($column->code(), $userSelectedColumns);
            })
            ->filter(fn ($column) => $column->label() !== __('general.actions') && $column->code() !== 'photo')
            ->map(fn ($column) => $column->code()) 
            ->toArray();

        $filename = 'employees_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new EmployeeExport($data, $visibleColumnCodes), 
            $filename
        );
    }
}
