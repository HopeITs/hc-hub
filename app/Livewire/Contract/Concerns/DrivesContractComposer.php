<?php

namespace App\Livewire\Contract\Concerns;

use App\Livewire\Forms\ContractForm;
use App\Models\Contract;
use App\Models\Employee;
use App\Services\LeaveBalanceService;
use Livewire\Attributes\Renderless;

/**
 * The half of the single-page composer that both ContractCreate and
 * ContractEdit share.
 *
 * Alpine owns the form while it is being filled in, so only two things cross
 * the wire from here: the leave-entitlement preview, and the one payload the
 * screen posts on submit. Everything below exists to fold that payload back
 * into the server-side form object and to point the user at whichever section
 * a rejection came from.
 *
 * @property ContractForm $form
 * @property ?int $city_id
 */
trait DrivesContractComposer
{
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
        abort_unless($user->hasActivePermission($this->locationOptionsPermission()), 403);

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

        // An edit is pricing a contract that already exists. Carrying its key
        // across lets the service exclude it from its own carry-over lookup,
        // so the figure previewed is the figure the save will store.
        if ($existingId = $this->composerContractKey()) {
            $contract->setAttribute('id', $existingId);
        }

        return app(LeaveBalanceService::class)->previewAnnual($contract);
    }

    /**
     * The contract this screen is pricing, when it already exists.
     * Null while creating one, which has no key to speak of yet.
     */
    protected function composerContractKey(): ?int
    {
        return null;
    }

    /**
     * The sections this screen renders, in order.
     *
     * Creating a contract has four; editing one adds termination, which cannot
     * apply to a contract that does not exist yet.
     *
     * @return array<int, string>
     */
    protected function composerSections(): array
    {
        return ['assignment', 'contract', 'schedule', 'pay'];
    }

    /**
     * Contract types decorated with the dates each one implies.
     *
     * The dates are produced by ContractForm::updatedContractTypeId() itself
     * rather than re-derived here, so a card can never state a consequence the
     * form would not actually apply. The form's own dates are restored
     * afterwards because probing must not be observable.
     *
     * The term is anchored by contractTermAnchor(): a fresh contract has none
     * and keeps the "starts next month" default, while a renewal or an edit
     * anchors to the date it already runs from, so switching type cannot
     * silently back-date the contract.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    protected function contractTypeCards(string $nameColumn)
    {
        $categories = config('contracts.map_by_id', []);
        $originalStart = $this->form->start_date;
        $originalEnd = $this->form->end_date;

        $cards = $this->getContractTypes($nameColumn)
            ->sortBy(fn ($type) => [$categories[$type->id] ?? 'zzz', $type->id])
            ->values()
            ->map(function ($type) use ($categories) {
                $this->form->updatedContractTypeId($type->id, $this->contractTermAnchor());

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
     * The form's current values, handed to Alpine so the composer opens
     * pre-filled instead of blank.
     *
     * Renewing and editing seed it from an existing contract; a plain create
     * has nothing to seed from and passes null instead.
     *
     * @return array<string, mixed>
     */
    protected function composerSeed(): array
    {
        return [
            'cityId' => $this->city_id,
            'programId' => $this->program_id,
            'centerId' => $this->center_id,
            'employeeId' => $this->form->employee_id,
            'positionId' => $this->form->position_id,
            'contractTypeId' => $this->form->contract_type_id,
            'startDate' => $this->form->start_date,
            'endDate' => $this->form->end_date,
            'workHoursFrom' => $this->form->work_hours_from,
            'workHoursTo' => $this->form->work_hours_to,
            'weeklyHours' => $this->form->weekly_hours,
            'workDaysEn' => $this->form->work_days_en,
            'workDaysAr' => $this->form->work_days_ar,
            'attendingNotesEn' => $this->form->attending_notes_en,
            'attendingNotesAr' => $this->form->attending_notes_ar,
            'salary' => $this->form->salary,
            'currency' => $this->form->currency,
            'salaryPer' => $this->form->salary_per,
            // Always null on create, where the termination section is not
            // rendered at all; ContractEdit is the only screen that reads them.
            'terminatedAt' => $this->form->terminated_at,
            'terminationReasonId' => $this->form->termination_reason_id,
            'reasonOfTermination' => $this->form->reason_of_termination,
        ];
    }

    /**
     * Fold the Alpine-owned form into this component as one atomic update.
     */
    protected function applyComposerPayload(array $payload): void
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

        $this->applyComposerTermination($payload);
    }

    /**
     * A contract is created active and un-terminated, always — whatever the
     * client sends. ContractEdit overrides this with the real thing.
     */
    protected function applyComposerTermination(array $payload): void
    {
        $this->form->is_active = true;
        $this->form->terminated_at = null;
        $this->form->termination_reason_id = null;
        $this->form->reason_of_termination = null;
    }

    /**
     * Which composer section each validation key belongs to, so a server-side
     * rejection can reopen the section that actually failed.
     *
     * @return array<string, string>
     */
    protected function sectionOfField(): array
    {
        return [
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
    }

    /**
     * Map validation keys onto composer sections, tolerating both the bare
     * field name and the `form.` prefix Livewire adds for form objects.
     *
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    protected function sectionsFor(array $keys): array
    {
        $map = $this->sectionOfField();

        return collect($keys)
            ->map(fn (string $key) => $map[str_replace('form.', '', $key)] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function dateOrNull(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) === 1 ? $value : null;
    }

    protected function timeOrNull(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return preg_match('/^\d{2}:\d{2}$/', (string) $value) === 1 ? $value : null;
    }

    protected function textOrNull(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }
}
