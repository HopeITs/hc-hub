<?php

namespace App\Livewire\Contract;

use App\Livewire\Contract\Concerns\ResolvesContractLocationOptions;
use App\Livewire\Forms\ContractForm;
use App\Livewire\Forms\LeaveBalanceForm;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\NewEmployeeWelcome;
use App\Services\LeaveBalanceService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Renderless;
use Livewire\Component;

/**
 * Single-page contract composer.
 *
 * Alpine owns the form while it is being filled in; this class does exactly
 * three things over the wire:
 *
 *   1. locationOptions()  — one renderless request per city change (inherited)
 *   2. leavePreview()     — one renderless request when the entitlement inputs
 *                           are complete, so the leave figure is calculated by
 *                           LeaveBalanceService rather than re-implemented in JS
 *   3. submit()           — one request at the end, where the server re-validates
 *                           everything from scratch
 *
 * Termination fields are deliberately absent: a contract cannot already be
 * terminated at the moment it is created. They remain available in ContractEdit.
 */
class ContractCreate extends Component
{
    use ResolvesContractLocationOptions;

    /**
     * Which composer section each validation key belongs to, so a server-side
     * rejection can reopen the section that actually failed.
     */
    private const SECTION_OF_FIELD = [
        'city_id' => 'assignment',
        'employee_id' => 'assignment',
        'position_id' => 'assignment',
        'contract_type_id' => 'contract',
        'start_date' => 'contract',
        'end_date' => 'contract',
        'weekly_hours' => 'schedule',
        'work_days_en' => 'schedule',
        'work_days_ar' => 'schedule',
        'work_hours_from' => 'schedule',
        'work_hours_to' => 'schedule',
        'attending_notes_en' => 'schedule',
        'attending_notes_ar' => 'schedule',
        'salary' => 'pay',
        'currency' => 'pay',
        'salary_per' => 'pay',
    ];

    public ContractForm $form;

    public LeaveBalanceForm $balanceForm;

    public ?int $city_id = null;

    public ?int $program_id = null;

    public ?int $center_id = null;

    public function mount(): void
    {
        $this->form->setDefaults();
    }

    // ────────────────────────────────────────────────
    // Renderless helpers
    // ────────────────────────────────────────────────

    /**
     * Annual-leave entitlement the contract being composed would receive.
     *
     * Entitlement maths stays in LeaveBalanceService — duplicating it in
     * JavaScript would create a second source of truth that drifts silently.
     */
    #[Renderless]
    public function leavePreview(array $payload): ?float
    {
        $user = auth()->user();

        abort_unless($user, 403);
        abort_unless($user->hasActivePermission('contracts.create'), 403);

        $employeeId = $this->selectionId($payload, 'employeeId');
        $weeklyHours = (int) ($payload['weeklyHours'] ?? 0);
        $startDate = $this->dateOrNull($payload['startDate'] ?? null);
        $endDate = $this->dateOrNull($payload['endDate'] ?? null);

        if (! $employeeId || $weeklyHours < 1 || ! $startDate || ! $endDate) {
            return null;
        }

        // The preview must not leak entitlement figures for employees outside
        // the caller's city, so it is scoped exactly like the option lists are.
        $activeCityId = $user->activeCityId();

        $isVisible = Employee::query()
            ->whereKey($employeeId)
            ->when($activeCityId, fn ($query, int $cityId) => $query->where('city_id', $cityId))
            ->exists();

        if (! $isVisible) {
            return null;
        }

        $contract = new Contract;
        $contract->employee_id = $employeeId;
        $contract->contract_type_id = $this->selectionId($payload, 'contractTypeId');
        $contract->start_date = $startDate;
        $contract->end_date = $endDate;
        $contract->weekly_hours = $weeklyHours;

        return app(LeaveBalanceService::class)->previewAnnual($contract);
    }

    // ────────────────────────────────────────────────
    // Submission
    // ────────────────────────────────────────────────

    public function submit(array $payload): mixed
    {
        $this->applyComposerPayload($payload);

        try {
            $this->form->validateIdentity();
            $this->form->validateTerms();
            $this->validateWorkDays();
            $this->form->validatePay();
        } catch (ValidationException $exception) {
            // Reopen the offending section before Livewire renders the errors.
            $this->dispatch('composer-invalid', sections: $this->sectionsFor(array_keys($exception->errors())));

            throw $exception;
        }

        if (! $this->validateCityConsistency()) {
            $this->dispatch('composer-invalid', sections: $this->sectionsFor($this->getErrorBag()->keys()));

            return null;
        }

        // Everything above was validated in this same request, so persist
        // without running the identical queries a second time.
        $contract = $this->form->create();

        // All contract types receive annual leave balances.
        $this->balanceForm->store($contract->id);

        $this->notifyIfFirstContract($contract);

        $this->dispatch('notification-created');

        LivewireAlert::success(__('general.success-message'));

        return redirect()->route('contracts.show', $contract->id);
    }

    // ────────────────────────────────────────────────
    // Rendering
    // ────────────────────────────────────────────────

    public function render(): View
    {
        $locale = app()->getLocale();
        $nameColumn = "name_{$locale}";

        $cities = $this->getCities($nameColumn);

        return view('livewire.contract.contract-create', [
            'cities' => $cities,
            'lockedCity' => $cities->count() === 1 ? $cities->first() : null,
            'types' => $this->contractTypeCards($nameColumn),
        ]);
    }

    protected function locationOptionsPermission(): string
    {
        return 'contracts.create';
    }

    // ────────────────────────────────────────────────
    // Private helpers
    // ────────────────────────────────────────────────

    /**
     * Contract types decorated with the dates each one implies.
     *
     * The dates are produced by ContractForm::updatedContractTypeId() itself
     * rather than re-derived here, so a card can never state a consequence the
     * form would not actually apply. The form's own dates are restored
     * afterwards because probing must not be observable.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function contractTypeCards(string $nameColumn)
    {
        $categories = config('contracts.map_by_id', []);
        $originalStart = $this->form->start_date;
        $originalEnd = $this->form->end_date;

        $cards = $this->getContractTypes($nameColumn)
            ->sortBy(fn ($type) => [$categories[$type->id] ?? 'zzz', $type->id])
            ->values()
            ->map(function ($type) use ($categories) {
                $this->form->updatedContractTypeId($type->id);

                return (object) [
                    'id' => $type->id,
                    'name' => $type->name,
                    'type' => (int) $type->type,
                    'place' => (int) $type->place,
                    'category' => $categories[$type->id] ?? 'annual',
                    'startDate' => $this->form->start_date,
                    'endDate' => $this->form->end_date,
                ];
            });

        $this->form->start_date = $originalStart;
        $this->form->end_date = $originalEnd;

        return $cards;
    }

    /**
     * Fold the Alpine-owned form into this component as one atomic update.
     */
    private function applyComposerPayload(array $payload): void
    {
        // Sets city / employee / program / center / position / contract type,
        // and — as a side effect of the type change — the default dates.
        $this->applyIdentitySelection($payload);

        // ...which the dates the user actually chose then override.
        $this->form->start_date = $this->dateOrNull($payload['startDate'] ?? null);
        $this->form->end_date = $this->dateOrNull($payload['endDate'] ?? null);

        $this->form->work_hours_from = $this->timeOrNull($payload['workHoursFrom'] ?? null);
        $this->form->work_hours_to = $this->timeOrNull($payload['workHoursTo'] ?? null);
        $this->form->weekly_hours = max(0, (int) ($payload['weeklyHours'] ?? 0));

        $this->form->work_days_en = $this->textOrNull($payload['workDaysEn'] ?? null);
        $this->form->work_days_ar = $this->textOrNull($payload['workDaysAr'] ?? null);
        $this->form->attending_notes_en = $this->textOrNull($payload['attendingNotesEn'] ?? null);
        $this->form->attending_notes_ar = $this->textOrNull($payload['attendingNotesAr'] ?? null);

        $this->form->currency = (int) ($payload['currency'] ?? 1);
        $this->form->salary = $payload['salary'] ?? null;
        $this->form->salary_per = (int) ($payload['salaryPer'] ?? 1);

        // A contract is created active and un-terminated, always.
        $this->form->is_active = true;
        $this->form->terminated_at = null;
        $this->form->termination_reason_id = null;
        $this->form->reason_of_termination = null;
    }

    /**
     * Work days are mandatory on create only.
     *
     * This rule cannot live in ContractForm::validateTerms(): that method is
     * shared with ContractEdit, and contracts imported before the day picker
     * existed have null work days that editing must not be blocked on.
     */
    private function validateWorkDays(): void
    {
        $this->validate(
            [
                'form.work_days_en' => ['required', 'string', 'max:255'],
                'form.work_days_ar' => ['required', 'string', 'max:255'],
            ],
            attributes: [
                'form.work_days_en' => __('general.work-days').' (EN)',
                'form.work_days_ar' => __('general.work-days').' (AR)',
            ],
        );
    }

    /**
     * Map validation keys onto composer sections, tolerating both the bare
     * field name and the `form.` prefix Livewire adds for form objects.
     *
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    private function sectionsFor(array $keys): array
    {
        return collect($keys)
            ->map(fn (string $key) => self::SECTION_OF_FIELD[str_replace('form.', '', $key)] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function dateOrNull(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) === 1 ? $value : null;
    }

    private function timeOrNull(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return preg_match('/^\d{2}:\d{2}$/', (string) $value) === 1 ? $value : null;
    }

    private function textOrNull(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    private function notifyIfFirstContract(Contract $contract): void
    {
        $employee = $contract->employee;

        if ($employee->allContracts()->count() !== 1) {
            return;
        }

        // Queued notifications to active colleagues in the employee's city.
        User::sendBroadcast(new NewEmployeeWelcome($employee), $employee->city_id);
    }
}
