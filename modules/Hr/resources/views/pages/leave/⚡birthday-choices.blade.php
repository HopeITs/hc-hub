<?php

use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Leave\ChooseBirthdayHoliday;
use Modules\Hr\Actions\Leave\DecideBirthdayHoliday;
use Modules\Hr\Support\Leave\BirthdayHoliday;

/*
 * The birthday holiday (team-coverage-contract §6.1, H-4): the volunteer's
 * own birthday day in the coming year and, when it falls on a weekend or
 * holiday, the working day they ask for instead; and, for a direct manager,
 * the requests of their reports waiting for a decision. Everyone signed in
 * may open it: it shows only what applies to them.
 */
new #[Layout('layouts::desk'), Title('hr_leave_calendar.titles.birthday_choices')] class extends Component {
    /** @var array<string, string> the day picked per "holidayId:year" */
    public array $picked = [];

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    #[Computed]
    public function hasContract(): bool
    {
        return app(BirthdayHoliday::class)->contractOfUser($this->user()) !== null;
    }

    /** @return Collection<int, array<string, mixed>> */
    #[Computed]
    public function own(): Collection
    {
        $birthdays = app(BirthdayHoliday::class);
        $contract = $birthdays->contractOfUser($this->user());

        if ($contract === null) {
            return collect();
        }

        $today = CarbonImmutable::today();

        return collect($birthdays->occurrencesFor($contract, $today->subDays(7), $today->addYear()));
    }

    /** @return Collection<int, \Modules\Hr\Models\BirthdayHolidayChoice> */
    #[Computed]
    public function team(): Collection
    {
        return collect(app(BirthdayHoliday::class)->pendingFor($this->user()));
    }

    public function choose(string $key): void
    {
        if (preg_match('/^(\d+):(\d{4})$/', $key, $match) !== 1) {
            return;
        }

        try {
            app(ChooseBirthdayHoliday::class)->handle($this->user(), (int) $match[1], (int) $match[2], $this->picked[$key] ?? null);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw \Illuminate\Validation\ValidationException::withMessages(['picked.'.$key => collect($exception->errors())->flatten()->all()]);
        }

        unset($this->own);

        Flux::toast(variant: 'success', text: __('hr_leave_calendar.birthday.requested'));
    }

    public function decide(int $choiceId, bool $approve): void
    {
        app(DecideBirthdayHoliday::class)->handle($this->user(), $choiceId, $approve);

        unset($this->team);

        Flux::toast(variant: 'success', text: __($approve ? 'hr_leave_calendar.birthday.approved_toast' : 'hr_leave_calendar.birthday.rejected_toast'));
    }
};
?>

@php
    use Modules\Hr\Models\BirthdayHolidayChoice;

    $format = fn ($date) => \Carbon\CarbonImmutable::parse($date)->translatedFormat('l j F');
    $own = $this->own;
    $team = $this->team;
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<x-desk.register heading-id="hr-birthday-heading" :title="__('hr_leave_calendar.titles.birthday_choices')" data-hr-birthday>
    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.panel :title="__('hr_leave_calendar.birthday.card_title')" data-birthday-own>
            <div class="space-y-3 px-4 py-3">
                @if (! $this->hasContract)
                    <p class="text-sm {{ $muted }}">{{ __('hr_leave_calendar.birthday.no_contract') }}</p>
                @elseif ($own->isEmpty())
                    <p class="text-sm {{ $muted }}">{{ __('hr_leave_calendar.birthday.none') }}</p>
                @endif

                @foreach ($own as $occurrence)
                    @php
                        $key = $occurrence['holiday']->id.':'.$occurrence['year'];
                        $choice = $occurrence['choice'];
                        $weekStart = $occurrence['date']->startOfWeek(\Carbon\CarbonInterface::MONDAY)->toDateString();
                        $weekEnd = $occurrence['date']->endOfWeek(\Carbon\CarbonInterface::SUNDAY)->toDateString();
                    @endphp
                    <div class="space-y-2" wire:key="birthday-{{ $key }}">
                        @if (! $occurrence['conflict'])
                            <p class="text-sm">{{ __('hr_leave_calendar.birthday.on_day', ['date' => $format($occurrence['date'])]) }}</p>
                        @elseif ($choice?->status === BirthdayHolidayChoice::APPROVED)
                            <p class="text-sm">{{ __('hr_leave_calendar.birthday.approved', ['date' => $format($choice->chosen_date)]) }}</p>
                        @else
                            <p class="text-sm">
                                @if ($choice?->status === BirthdayHolidayChoice::PENDING)
                                    {{ __('hr_leave_calendar.birthday.pending', ['date' => $format($choice->chosen_date)]) }}
                                @elseif ($choice?->status === BirthdayHolidayChoice::REJECTED)
                                    {{ __('hr_leave_calendar.birthday.rejected', ['date' => $format($choice->chosen_date)]) }}
                                @else
                                    {{ __('hr_leave_calendar.birthday.needs_choice', ['date' => $format($occurrence['date'])]) }}
                                @endif
                            </p>

                            <div class="flex flex-wrap items-end gap-2">
                                <x-ui.form-input wire:model="picked.{{ $key }}" type="date" :label="__('hr_leave_calendar.birthday.chosen_date')" :min="$weekStart" :max="$weekEnd" class="min-w-44" />
                                <x-ui.button type="button" tone="primary" wire:click="choose('{{ $key }}')" data-test="choose-birthday">{{ __('hr_leave_calendar.birthday.choose') }}</x-ui.button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-ui.panel>

        <x-ui.panel :title="__('hr_leave_calendar.birthday.team_title')" :count="$team->count()" data-birthday-team>
            @if ($team->isEmpty())
                <p class="px-4 py-3 text-sm {{ $muted }}">{{ __('hr_leave_calendar.birthday.team_empty') }}</p>
            @else
                <ul class="divide-y divide-secondary-soft dark:divide-line">
                    @foreach ($team as $choice)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3" wire:key="birthday-team-{{ $choice->id }}">
                            <span class="text-sm">{{ __('hr_leave_calendar.birthday.team_row', ['name' => $choice->employee?->name, 'date' => $format($choice->chosen_date)]) }}</span>
                            <span class="flex gap-2">
                                <x-ui.button type="button" tone="primary" size="sm" wire:click="decide({{ $choice->id }}, true)">{{ __('hr_leave_calendar.birthday.approve') }}</x-ui.button>
                                <x-ui.button type="button" tone="secondary" size="sm" wire:click="decide({{ $choice->id }}, false)">{{ __('hr_leave_calendar.birthday.reject') }}</x-ui.button>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.panel>
    </div>
</x-desk.register>
