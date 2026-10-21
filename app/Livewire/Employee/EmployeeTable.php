<?php

namespace App\Livewire\Employee;

use App\Exports\EmployeeExport;
use App\Livewire\Concerns\WithFeedback;
use App\Models\City;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\DateColumn;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Enums\SearchScope;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeTable extends LivewireTable
{
    use WithFeedback;

    protected string $model = Employee::class;

    protected $listeners = ['delete', 'toggleItemStatusConfirmed', 'exportExcel' => 'exportExcel'];

    public function confirmDelete($id): void
    {
        $this->confirm('delete', ['id' => $id]);
    }

    public function delete($payload): void
    {
        $employee = Employee::findOrFail($payload['id']);
        Gate::authorize('delete', $employee);
        $employee->delete();

        $this->toast();
    }

    public function confirmToggleItemStatus($id): void
    {
        $this->confirm('toggleItemStatusConfirmed', ['id' => $id], title: __('general.toggle-status'), message: __('general.confirm-status-change'), confirmLabel: __('general.save'), danger: false);
    }

    public function toggleItemStatusConfirmed($payload): void
    {
        $item = Employee::findOrFail($payload['id']);
        Gate::authorize('update', $item);
        $item->is_active = ! $item->is_active;
        $item->save();

        $this->toast(__('general.item-updated'));
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
            ->filter(fn ($column) => $column->label() !== __('general.actions') && $column->code() !== 'photo')
            ->map(fn ($column) => $column->code())
            ->toArray();

        $filename = 'employees_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new EmployeeExport($data, $visibleColumnCodes),
            $filename,
        );
    }

    protected function link(Model $model): ?string
    {
        return auth()->user()->hasActivePermission('employees.update')
            ? route('employees.edit', $model->getKey())
            : null;
    }

    protected function query(): Builder
    {
        return $this->model()::query()
            ->with([
                'contracts.position.directManager.activeContracts',
                'account',
                'media',
            ])
            ->when(
                auth()->user()->activeCityIds(),
                fn (Builder $q, array $cityIds) => $q->whereIn('city_id', $cityIds),
            )
            ->orderByDesc('is_active');
    }

    protected function globalSearchColumns(): array
    {
        return ['name', 'actual_dob'];
    }

    protected function managerOptions(): array
    {
        return Employee::query()
            ->select(['id', 'first_name_en', 'first_name_ar', 'last_name_en', 'last_name_ar'])
            ->when(
                auth()->user()->activeCityIds(),
                fn (Builder $q, array $cityIds) => $q->whereIn('city_id', $cityIds),
            )
            ->where('is_active', true)
            ->orderBy(transColumn('first_name'), 'asc')
            ->get()
            ->mapWithKeys(fn ($e) => [$e->id => $e->name])
            ->all();
    }

    protected function columns(): array
    {
        $user = auth()->user();
        $canUpdate = $user->hasActivePermission('employees.update');
        $canDelete = $user->hasActivePermission('employees.delete');

        return [
            Column::make(__('employees.table.id'), 'id')->sortable(),

            Column::make(trans('general.photo'), 'photo')
                ->displayUsing(function ($v, Model $m): string {
                    $src = $m->avatar_url;

                    return $src
                        ? '<img src="'.e($src).'" alt="'.e($m->name).'" class="size-[38px] max-w-none shrink-0 rounded-full border border-primary bg-white object-cover" />'
                        : '<div class="bg-gray-200 rounded-full size-[38px]"></div>';
                })->asHtml(),

            // Column::make(trans('general.name'), transColumn('name'))
            //     ->displayUsing(fn($v, Model $m) => $m->name) // uses accessor
            //     ->sortable()->searchable(),
            Column::make(trans('general.name'), 'employee')
                // <bdi>: a Latin name that falls back into the Arabic table
                // keeps its own word order.
                ->displayUsing(fn ($v, Model $m) => '<bdi>'.e($m->name).'</bdi>')
                ->asHtml()
                ->sortable()
                ->searchable(function (Builder $query, $value) {
                    $parts = array_filter(explode(' ', trim($value)));

                    $query->where(function (Builder $q) use ($parts) {
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
            Column::make(trans_choice('general.role', 1), 'account')
                ->displayUsing(fn ($v, Model $m) => role_label($m->account?->myRole())),

            Column::make(trans('general.years-of-service'), 'years_of_service_formatted')->computed()->sortable(),

            // Month name in the page's own language, isolated with <bdi>: an
            // Arabic date then reads right to left as a whole, an English one
            // left to right, and neither gets reordered by the text around it.
            DateColumn::make(trans('general.join-at'), 'joined_at')
                ->displayUsing(fn ($v, Model $m) => $m->joined_at
                    ? '<bdi class="whitespace-nowrap">'.e($m->joined_at->copy()->locale(app()->getLocale())->translatedFormat('d M Y')).'</bdi>'
                    : '—')
                ->asHtml()
                ->sortable(),
            SelectColumn::make(trans_choice('general.city', 1), 'city.'.transColumn('name'))
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

            SelectColumn::make(trans('general.position'), 'contracts.position.'.transColumn('title'))
                ->options(Position::query()->orderby(transColumn('title'), 'asc')->get()->pluck(transColumn('title'), transColumn('title'))->toArray())
                ->sortable()->searchable(),

            Column::make(trans('general.work_email'), 'work_email')
                ->displayUsing(fn ($v, Model $m) => $m->account?->email
                    ? '<span dir="ltr">'.e($m->account->email).'</span>'
                    : '—')
                ->asHtml(),

            // Status as text as well as colour, with the filter offering the
            // same two words instead of a bare Yes / No.
            SelectColumn::make(trans('general.is-active'), 'is_active')
                ->options([1 => __('general.active'), 0 => __('general.inactive')])
                ->displayUsing(fn ($v, Model $m): string => $m->is_active
                    ? '<span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-emerald-50 px-2 py-0.5 text-small font-semibold text-emerald-700 ring-1 ring-emerald-200"><span class="size-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>'.e(__('general.active')).'</span>'
                    : '<span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-neutral-100 px-2 py-0.5 text-small font-semibold text-secondary-medium ring-1 ring-neutral-200"><span class="size-1.5 rounded-full bg-neutral-400" aria-hidden="true"></span>'.e(__('general.inactive')).'</span>')
                ->asHtml()
                ->searchable(),

            Column::make(__('general.actions'), function (Model $model) use ($canUpdate, $canDelete): string {
                $isActive = $model->is_active ? 1 : 0;

                $toggleBtn = $canUpdate
                    ? Blade::render(
                        '<x-forms.toggle-input class="p-2 m-2 text-xl" active="'.$isActive.
                            '" :model-id="'.$model->getKey().
                            '" wire-event="confirmToggleItemStatus" />',
                    )
                    : '';

                // Profile route is keyed by username; imported employees may
                // not have one yet, so only link when it exists.
                $viewBtn = $model->username
                    ? Blade::render(
                        '<a  href="'.route('employees.show', ['employee' => $model->username]).'" class="p-2 " title="'.e(__('general.view-profile')).'" aria-label="'.e(__('general.view-profile')).'">
                                <x-icons.eye class="transition text-secondary-light hover:text-primary" />
                            </a>',
                    )
                    : '';

                $editBtn = $canUpdate
                    ? Blade::render('<x-table.edit-btn href="'.route('employees.edit', $model->getKey()).'" />')
                    : '';

                $deleteBtn = $canDelete
                    ? Blade::render('<x-table.delete-btn :modelId="'.$model->getKey().'" />')
                    : '';

                return '<div class="flex items-center gap-1">'.
                    $toggleBtn.$viewBtn.$editBtn.$deleteBtn.
                    '</div>';
            })->asHtml()->clickable(false),

        ];
    }
}
