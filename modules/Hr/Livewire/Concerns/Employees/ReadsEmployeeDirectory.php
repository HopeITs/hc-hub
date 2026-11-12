<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Employees;

use App\Support\CsvExport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Employees\HrAccess;
use Modules\Hr\Support\Employees\WorkPresence;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The employee directory's queries: the filtered, city-scoped page of
 * employees, the count behind each view, and the CSV export.
 *
 * Contract columns are listed by hand everywhere: pay is never selected here.
 */
trait ReadsEmployeeDirectory
{
    /** The views of the directory, in the order of their tabs. */
    public const VIEWS = ['active', 'inactive', 'awaiting', 'incomplete'];

    private const LIST_COLUMNS = [
        'employees.id', 'employees.city_id', 'employees.username', 'employees.first_name_en', 'employees.first_name_ar',
        'employees.last_name_en', 'employees.last_name_ar', 'employees.work_phone', 'employees.is_active',
        'employees.joined_at', 'employees.record_status', 'employees.updated_at',
    ];

    /**
     * Inactive and awaiting records are for HR, the general manager and the
     * super admin; drafts for HR and the super admin.
     */
    public function canUseView(string $view): bool
    {
        $user = Auth::user();

        return match ($view) {
            'inactive', 'awaiting' => HrAccess::is($user, 'HR', 'general-manager', 'super-admin'),
            'incomplete' => HrAccess::is($user, 'HR', 'super-admin'),
            default => true,
        };
    }

    public function effectiveView(): string
    {
        return in_array($this->view, self::VIEWS, true) && $this->canUseView($this->view) ? $this->view : 'active';
    }

    /**
     * @return LengthAwarePaginator<int, Employee>
     */
    #[Computed]
    public function employees(): LengthAwarePaginator
    {
        return $this->directoryQuery()
            ->select(self::LIST_COLUMNS)
            ->with([
                'account:id,employee_id,email',
                'contracts' => fn ($contracts) => $contracts
                    ->select(['contracts.id', 'contracts.employee_id', 'contracts.position_id', 'contracts.start_date', 'contracts.end_date', 'contracts.is_active'])
                    ->with('position:id,title_en,title_ar,department_id,program_id'),
            ])
            ->withExists(['realContracts as has_real_contract'])
            ->orderBy($this->nameColumn())
            ->orderBy('employees.id')
            ->paginate(25);
    }

    /**
     * The presence dot of each person on the page (WorkPresence), read once
     * for the whole page. Only the active view draws it.
     *
     * @return array<int, array{tone: string, label: string}>
     */
    #[Computed]
    public function presence(): array
    {
        $viewer = Auth::user();

        if ($viewer === null || $this->effectiveView() !== 'active') {
            return [];
        }

        return app(WorkPresence::class)->for($viewer, $this->employees->modelKeys());
    }

    /**
     * How many employees each view holds for this user, ignoring the search.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function viewCounts(): array
    {
        $counts = [];

        foreach (self::VIEWS as $view) {
            if ($this->canUseView($view)) {
                $counts[$view] = $this->scopedQuery()->tap(fn (Builder $query) => $this->applyView($query, $view))->count();
            }
        }

        return $counts;
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function cityOptions(): array
    {
        $allowed = HrAccess::cityIds(Auth::user());

        return array_filter(
            City::options(withNational: false),
            fn (int $id): bool => $allowed === null || in_array($id, $allowed, true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function programOptions(): array
    {
        return Program::namesById();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function departmentOptions(): array
    {
        return Department::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('employees.export');

        $rows = $this->directoryQuery()
            ->select([...self::LIST_COLUMNS, 'employees.personal_phone'])
            ->with([
                'account:id,employee_id,email',
                'contracts' => fn ($contracts) => $contracts
                    ->select(['contracts.id', 'contracts.employee_id', 'contracts.position_id', 'contracts.start_date', 'contracts.end_date', 'contracts.is_active'])
                    ->with('position:id,title_en,title_ar,department_id,program_id'),
            ])
            ->withExists(['realContracts as has_real_contract'])
            ->orderBy($this->nameColumn())
            ->lazy(200)
            ->map(function (Employee $employee): array {
                $position = $employee->contracts->sortByDesc('start_date')->first()?->position;

                return [
                    $employee->id,
                    $employee->name_en,
                    $employee->name_ar,
                    $position?->title,
                    $position === null ? '' : Program::nameOf($position->program_id),
                    $position === null ? '' : Department::nameOf((int) $position->department_id),
                    City::nameOf($employee->city_id),
                    $employee->account?->email,
                    $employee->work_phone,
                    $employee->joined_at,
                    $this->statusLabel(EmployeeStatus::for($employee)),
                ];
            });

        return CsvExport::download('employees_'.now()->format('Y-m-d').'.csv', [
            __('hr_employees.fields.id'), __('hr_employees.fields.name_en'), __('hr_employees.fields.name_ar'),
            __('hr_employees.fields.position'), __('common.program'), __('hr_employees.fields.department'),
            __('common.city'), __('hr_employees.fields.work_email'), __('hr_employees.fields.work_phone'),
            __('hr_employees.fields.joined_at'), __('common.status'),
        ], $rows);
    }

    public function statusLabel(EmployeeStatus $status): string
    {
        return __('hr_employees.status.'.str_replace('-', '_', $status->value));
    }

    /**
     * @return Builder<Employee>
     */
    protected function directoryQuery(): Builder
    {
        return $this->scopedQuery()
            ->tap(fn (Builder $query) => $this->applyView($query, $this->effectiveView()))
            ->when(trim($this->search) !== '', fn (Builder $query) => $query->search($this->search))
            ->when($this->city !== '', fn (Builder $query) => $query->where('employees.city_id', (int) $this->city))
            ->when($this->program !== '' || $this->department !== '', fn (Builder $query) => $query
                ->whereHas('contracts.position', fn (Builder $position) => $position
                    ->when($this->program !== '', fn (Builder $q) => $q->where('program_id', (int) $this->program))
                    ->when($this->department !== '', fn (Builder $q) => $q->where('department_id', (int) $this->department))));
    }

    /**
     * @return Builder<Employee>
     */
    protected function scopedQuery(): Builder
    {
        return Employee::query()->forCity(HrAccess::cityIds(Auth::user()));
    }

    /**
     * @param  Builder<Employee>  $query
     */
    protected function applyView(Builder $query, string $view): void
    {
        match ($view) {
            'inactive' => $query->completeRecords()->where('employees.is_active', false)->whereHas('realContracts'),
            'awaiting' => $query->awaitingContract(),
            'incomplete' => $query->drafts(),
            default => $query->where('employees.is_active', true),
        };
    }

    protected function nameColumn(): string
    {
        return app()->getLocale() === 'ar' ? 'employees.first_name_ar' : 'employees.first_name_en';
    }
}
