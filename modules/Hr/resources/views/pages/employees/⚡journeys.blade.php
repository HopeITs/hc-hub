<?php

use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\OffboardingJourney;
use Modules\Hr\Models\OnboardingJourney;
use Modules\Hr\Models\RenewalJourney;
use Modules\Hr\Support\Journeys\OffboardingJourneys;
use Modules\Hr\Support\Journeys\OnboardingJourneys;
use Modules\Hr\Support\Journeys\RenewalJourneys;

/**
 * The Journeys tab of an employee profile (the HR app's three journey panels):
 * onboarding, exit and the renewal of each contract ending soon. One row per
 * step with its state, who acts, due date, what blocks it and the one action
 * HR can take now. Mounted lazily by ⚡show for HR in the employee's city;
 * every action re-checks EmployeePolicy::update (renewal decisions
 * ContractPolicy::manageAutoRenew) and goes through the journey services.
 */
new class extends Component {
    #[Locked]
    public int $employeeId;

    public string $orientationDate = '';

    public bool $showConfirm = false;

    /** One of the CONFIRMABLE actions, set together with $showConfirm. */
    public string $confirmAction = '';

    public string $confirmTarget = '';

    private const CONFIRMABLE = ['reopen-onboarding', 'reopen-offboarding', 'stop', 'undo'];

    public function mount(int $employeeId): void
    {
        $this->employeeId = $employeeId;
        Gate::authorize('update', $this->employee);

        $this->orientationDate = $this->onboardingJourney?->orientation_scheduled_for?->toDateString() ?? '';
    }

    public function placeholder(): string
    {
        return <<<'HTML'
            <div class="grid gap-4" aria-busy="true">
                <div class="h-40 animate-pulse rounded-panel bg-secondary-soft/60 dark:bg-surface-2"></div>
                <div class="h-40 animate-pulse rounded-panel bg-secondary-soft/60 dark:bg-surface-2"></div>
            </div>
            HTML;
    }

    #[Computed]
    public function employee(): Employee
    {
        return Employee::query()
            ->withExists(['realContracts as has_real_contract'])
            ->findOrFail($this->employeeId);
    }

    #[Computed]
    public function onboardingJourney(): ?OnboardingJourney
    {
        return app(OnboardingJourneys::class)->journeyFor($this->employeeId);
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    #[Computed]
    public function onboardingSteps(): ?array
    {
        return app(OnboardingJourneys::class)->steps($this->employee, $this->onboardingJourney);
    }

    #[Computed]
    public function offboardingJourney(): ?OffboardingJourney
    {
        return app(OffboardingJourneys::class)->latestFor($this->employeeId);
    }

    /**
     * @return array{steps: list<array<string, mixed>>|null, calledOff: bool}
     */
    #[Computed]
    public function offboarding(): array
    {
        $journey = $this->offboardingJourney;

        if ($journey === null) {
            return ['steps' => null, 'calledOff' => false];
        }

        $service = app(OffboardingJourneys::class);
        $contracts = $this->employee->realContracts()->get();

        return [
            'steps' => $service->steps($this->employee, $journey, $contracts),
            'calledOff' => $service->isCalledOff($journey, $contracts),
        ];
    }

    /**
     * Only the renewals whose decision this user may take: the same rule every
     * action re-checks. Anyone else sees none.
     *
     * @return Collection<int, array{journey: RenewalJourney, steps: list<array<string, mixed>>, canRenew: bool}>
     */
    #[Computed]
    public function renewals(): Collection
    {
        $user = Auth::user();
        $service = app(RenewalJourneys::class);

        return $service->forEmployee($this->employee)
            ->filter(fn (RenewalJourney $journey): bool => $journey->contract !== null && $user?->can('manageAutoRenew', $journey->contract))
            ->map(fn (RenewalJourney $journey): array => [
                'journey' => $journey,
                'steps' => $service->steps($journey, $journey->contract),
                'canRenew' => $user->can('renew', $journey->contract),
            ])
            ->values();
    }

    public function scheduleOrientation(OnboardingJourneys $journeys): void
    {
        $employee = $this->authorizedEmployee();

        $this->validate(
            ['orientationDate' => ['required', 'date_format:Y-m-d']],
            attributes: ['orientationDate' => __('hr_journeys.onboarding.orientation_date')],
        );

        $changed = $journeys->scheduleOrientation($employee, CarbonImmutable::createFromFormat('Y-m-d', $this->orientationDate, config('app.timezone')), $this->actor());

        $this->done($changed, 'hr_journeys.onboarding.orientation_scheduled');
    }

    public function completeOrientation(OnboardingJourneys $journeys): void
    {
        $this->done($journeys->completeOrientation($this->authorizedEmployee(), $this->actor()), 'hr_journeys.onboarding.orientation_done');
    }

    public function markCardHandedOver(OnboardingJourneys $journeys): void
    {
        $this->done($journeys->markCardHandedOver($this->authorizedEmployee(), $this->actor()), 'hr_journeys.onboarding.card_done');
    }

    public function markExitInterviewHeld(OffboardingJourneys $journeys): void
    {
        $this->done($journeys->markExitInterviewHeld($this->authorizedEmployee(), $this->actor()), 'hr_journeys.offboarding.interview_done');
    }

    public function decideRenew(int $journeyId, RenewalJourneys $journeys): void
    {
        $changed = $journeys->decide($this->authorizedRenewal($journeyId), RenewalJourney::DECISION_RENEW, $this->actor());

        $this->done($changed, 'hr_journeys.renewal.decided_renew', 'hr_journeys.renewal.nothing_changed');
    }

    /** Ask first: undoing a step or a decision, and stopping a renewal, are recorded. */
    public function askConfirm(string $action, string $target): void
    {
        abort_unless(in_array($action, self::CONFIRMABLE, true), 404);

        match ($action) {
            'reopen-onboarding' => abort_unless(in_array($target, ['card', 'orientation'], true), 404),
            'reopen-offboarding' => abort_unless($target === 'interview', 404),
            default => $this->authorizedRenewal((int) $target),
        };

        $this->authorizedEmployee();
        $this->confirmAction = $action;
        $this->confirmTarget = $target;
        $this->showConfirm = true;
    }

    public function cancelConfirm(): void
    {
        $this->reset('showConfirm', 'confirmAction', 'confirmTarget');
    }

    public function runConfirmed(OnboardingJourneys $onboarding, OffboardingJourneys $offboarding, RenewalJourneys $renewals): void
    {
        $action = $this->confirmAction;
        $target = $this->confirmTarget;
        $this->cancelConfirm();

        match ($action) {
            'reopen-onboarding' => $this->done(
                in_array($target, ['card', 'orientation'], true) && $onboarding->reopen($this->authorizedEmployee(), $target, $this->actor()),
                'hr_journeys.reopened',
            ),
            'reopen-offboarding' => $this->done(
                $target === 'interview' && $offboarding->reopen($this->authorizedEmployee(), $target, $this->actor()),
                'hr_journeys.reopened',
            ),
            'stop' => $this->done(
                $renewals->decide($this->authorizedRenewal((int) $target), RenewalJourney::DECISION_STOP, $this->actor()),
                'hr_journeys.renewal.decided_stop',
                'hr_journeys.renewal.nothing_changed',
            ),
            'undo' => $this->done(
                $renewals->undo($this->authorizedRenewal((int) $target), $this->actor()),
                'hr_journeys.renewal.undone',
                'hr_journeys.renewal.nothing_changed',
            ),
            default => abort(404),
        };
    }

    private function done(bool $changed, string $successKey, string $unchangedKey = 'hr_journeys.nothing_changed'): void
    {
        unset($this->employee, $this->onboardingJourney, $this->onboardingSteps, $this->offboardingJourney, $this->offboarding, $this->renewals);

        Flux::toast(variant: $changed ? 'success' : 'warning', text: __($changed ? $successKey : $unchangedKey));
    }

    private function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /** Re-checked on every action: a role switch or a city change since mount counts. */
    private function authorizedEmployee(): Employee
    {
        $employee = Employee::query()->findOrFail($this->employeeId);
        Gate::authorize('update', $employee);

        return $employee;
    }

    /** The journey must belong to this employee, and its decision to this user. */
    private function authorizedRenewal(int $journeyId): RenewalJourney
    {
        $journey = RenewalJourney::query()
            ->where('employee_id', $this->employeeId)
            ->findOrFail($journeyId);
        $contract = $journey->contract()->first();

        abort_unless($contract !== null, 404);
        Gate::authorize('manageAutoRenew', $contract);

        return $journey;
    }
};
?>

@php
    $date =fn (mixed $value): string => $value === null ? '—' : CarbonImmutable::parse($value)->timezone(config('app.timezone'))->translatedFormat(__('formats.date'));
    $by = fn (?User $user): string => $user?->name ?: __('hr_journeys.unknown');
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $ink = 'text-secondary-dark dark:text-ink';
    $onboardingSteps = $this->onboardingSteps;
    $offboarding = $this->offboarding;
    $renewals = $this->renewals;
    $canViewContracts = auth()->user()->can('contracts.view');
    $canCreateContracts = auth()->user()->can('contracts.create');
    $confirmTitle = match ($confirmAction) {
        'stop' => __('hr_journeys.renewal.stop_title'),
        'undo' => __('hr_journeys.renewal.undo_title'),
        default => __('hr_journeys.reopen_title'),
    };
    $confirmMessage = match ($confirmAction) {
        'stop' => __('hr_journeys.renewal.stop_message'),
        'undo' => __('hr_journeys.renewal.undo_message'),
        'reopen-onboarding' => __('hr_journeys.reopen_message', ['step' => __('hr_journeys.onboarding.steps.'.($confirmTarget === 'orientation' ? 'orientation' : 'card'))]),
        'reopen-offboarding' => __('hr_journeys.reopen_message', ['step' => __('hr_journeys.offboarding.steps.interview')]),
        default => '',
    };
@endphp

<div class="grid gap-5" data-employee-journeys="{{ $employeeId }}">
    @if ($onboardingSteps === null && $offboarding['steps'] === null && $renewals->isEmpty())
        <x-ui.empty-state icon="map" :heading="__('hr_journeys.none')" :body="__('hr_journeys.none_body')" />
    @endif

    @if ($offboarding['steps'] !== null)
        @include('hr::pages.employees._journeys-offboarding')
    @endif

    @foreach ($renewals as $row)
        @include('hr::pages.employees._journeys-renewal', ['row' => $row])
    @endforeach

    @if ($onboardingSteps !== null)
        @include('hr::pages.employees._journeys-onboarding')
    @endif

    <x-ui.confirm-modal
        wire:model="showConfirm"
        close="cancelConfirm"
        :tone="$confirmAction === 'stop' ? 'danger' : 'neutral'"
        :title="$confirmTitle"
        :message="$confirmMessage"
        confirm="runConfirmed"
        :confirm-label="$confirmAction === 'stop' ? __('hr_journeys.renewal.stop') : __('hr_journeys.undo')"
        confirm-test="confirm-journey-action"
    />
</div>
