<?php

use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Journeys\OnboardingJourneys;

/*
 * The direct manager's orientation session with a new volunteer: the manager
 * sets the date, then records it once it has happened. Who may act is
 * OnboardingJourneys::canRunManagerOrientation (the direct manager, HR of the
 * volunteer's city as the fallback), checked on mount, on every action and
 * again inside the service.
 */
new #[Layout('layouts::desk'), Title('hr_onboarding.manager_session.title')] class extends Component {
    #[Locked]
    public int $employeeId;

    public string $date = '';

    public function mount(Employee $employee): void
    {
        abort_unless($employee->onboardingJourney !== null, 404);
        abort_unless(app(OnboardingJourneys::class)->canRunManagerOrientation(Auth::user(), $employee), 403);

        $this->employeeId = $employee->id;
        $this->date = $employee->onboardingJourney->manager_orientation_scheduled_for?->toDateString() ?? '';
    }

    #[Computed]
    public function employee(): Employee
    {
        $employee = Employee::query()->with(['onboardingJourney'])->findOrFail($this->employeeId);

        abort_unless(app(OnboardingJourneys::class)->canRunManagerOrientation(Auth::user(), $employee), 403);

        return $employee;
    }

    public function schedule(OnboardingJourneys $journeys): void
    {
        $employee = $this->employee;

        $this->validate(
            ['date' => ['required', 'date_format:Y-m-d']],
            attributes: ['date' => __('hr_onboarding.manager_session.date')],
        );

        $changed = $journeys->scheduleManagerOrientation($employee, CarbonImmutable::createFromFormat('Y-m-d', $this->date, config('app.timezone')), Auth::user());

        unset($this->employee);
        Flux::toast(variant: $changed ? 'success' : 'warning', text: __($changed ? 'hr_onboarding.manager_session.scheduled' : 'hr_journeys.nothing_changed'));
    }

    public function complete(OnboardingJourneys $journeys): void
    {
        $changed = $journeys->completeManagerOrientation($this->employee, Auth::user());

        unset($this->employee);
        Flux::toast(variant: $changed ? 'success' : 'warning', text: __($changed ? 'hr_onboarding.manager_session.done' : 'hr_journeys.nothing_changed'));
    }
};
?>

@php
    $employee = $this->employee;
    $journey = $employee->onboardingJourney;
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $date = fn (mixed $value): string => $value === null ? '—' : CarbonImmutable::parse($value)->translatedFormat(__('formats.date'));
    $canComplete = $journey?->manager_orientation_scheduled_for !== null
        && $journey->manager_orientation_scheduled_for->lte(now(config('app.timezone'))->startOfDay());
@endphp

<section class="flex flex-col gap-5" data-manager-session>
    <x-ui.detail-header
        :breadcrumbs="[['label' => __('hr_onboarding.manager_session.title')]]"
        :title="__('hr_onboarding.manager_session.title')"
        :subtitle="__('hr_onboarding.manager_session.lead', ['name' => (string) $employee->name])"
    />

    <x-ui.panel :title="__('hr_onboarding.manager_session.agenda_intro')">
        <ul class="grid gap-1 p-4 text-sm">
            <li>{{ __('hr_onboarding.manager_session.agenda_programs') }}</li>
            <li>{{ __('hr_onboarding.manager_session.agenda_workflow') }}</li>
        </ul>
    </x-ui.panel>

    <x-ui.panel :title="__('hr_onboarding.manager_session.date')">
        @if ($journey?->manager_orientation_completed_at !== null)
            <p class="p-4 text-sm {{ $muted }}" data-manager-session-done>{{ __('hr_onboarding.manager_session.done_on', ['date' => $date($journey->manager_orientation_scheduled_for)]) }}</p>
        @else
            <form wire:submit="schedule" class="flex flex-wrap items-end gap-3 p-4">
                <flux:input type="date" wire:model="date" :label="__('hr_onboarding.manager_session.date')" />
                <x-ui.button type="submit" data-test="schedule-manager-session">{{ __('hr_journeys.onboarding.schedule') }}</x-ui.button>
                @if ($canComplete)
                    <x-ui.button type="button" tone="primary" icon="check" wire:click="complete" data-test="complete-manager-session">{{ __('hr_journeys.onboarding.orientation_action') }}</x-ui.button>
                @endif
            </form>
            @error('date')
                <p class="px-4 pb-4 form-error">{{ $message }}</p>
            @enderror
        @endif
    </x-ui.panel>
</section>
