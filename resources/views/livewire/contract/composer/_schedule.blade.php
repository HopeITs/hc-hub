@php
    use App\Enums\ScheduleMode;
    use Illuminate\Support\Js;

    // Work days are demanded when a contract is written from scratch, but not
    // when an imported one is being edited — those predate the field entirely.
    $requireWorkDays ??= true;

    // work_day_from/to store Carbon's own day-of-week numbering (0 = Sunday
    // ... 6 = Saturday), so the picker options follow it too.
    $dayNames = collect(range(0, 6))->mapWithKeys(
        fn (int $day) => [$day => \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::SUNDAY)->addDays($day)->translatedFormat('l')],
    );

    $scheduleModeOptions = [
        ScheduleMode::Fixed => ScheduleMode::getLocalizedName(ScheduleMode::Fixed)['value'],
        ScheduleMode::Flexible => ScheduleMode::getLocalizedName(ScheduleMode::Flexible)['value'],
    ];

    // Quiet-but-visible control, used by the notes disclosure at the foot and
    // the second-shift toggle.
    $miniButton = 'inline-flex items-center gap-1.5 rounded-control border border-neutral-300 bg-white
                   px-2.5 py-1 text-caption font-semibold text-secondary-dark shadow-e1 transition
                   hover:border-primary hover:text-primary active:scale-[0.98]
                   focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';
@endphp

{{-- Section 3 — the working week, in the contract's own words. --}}
<x-contract.section
    name="schedule"
    index="3"
    :title="__('general.composer.schedule')"
    summary="scheduleSummary"
>
    {{--
        Free text in both languages rather than a row of day toggles: what is
        typed here is what the contract PDF prints, so "Monday to Friday" has to
        survive as that phrase and not be rebuilt from a list of five names.
    --}}
    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        <div>
            <x-forms.input-label for="composer_work_days_en" :required="$requireWorkDays">
                @lang('general.work-days') (EN)
            </x-forms.input-label>
            <x-forms.input
                id="composer_work_days_en"
                type="text"
                dir="ltr"
                maxlength="255"
                x-model="workDaysEn"
                {{-- x-forms.input declares `placeholder` as a prop and drops it,
                     so it is bound through Alpine instead of passed as one.
                     Blade directives are left uncompiled inside a component
                     tag's attributes, hence the echoed Js::from literal. --}}
                x-bind:placeholder="{{ Js::from(__('general.composer.work-days-placeholder-en')) }}"
            />
            <x-error-span>{{ $errors->first('form.work_days_en') }}</x-error-span>
        </div>

        <div>
            <x-forms.input-label for="composer_work_days_ar" :required="$requireWorkDays">
                @lang('general.work-days') (AR)
            </x-forms.input-label>
            <x-forms.input
                id="composer_work_days_ar"
                type="text"
                dir="rtl"
                maxlength="255"
                x-model="workDaysAr"
                x-bind:placeholder="{{ Js::from(__('general.composer.work-days-placeholder-ar')) }}"
            />
            <x-error-span>{{ $errors->first('form.work_days_ar') }}</x-error-span>
        </div>
    </div>

    {{-- Fixed vs flexible — everything below changes shape with it. --}}
    <div class="mt-5">
        <x-forms.input-label required>@lang('general.composer.schedule-mode')</x-forms.input-label>
        <div class="mt-1 grid grid-cols-2 gap-2" role="radiogroup" aria-label="@lang('general.composer.schedule-mode')">
            @foreach ($scheduleModeOptions as $value => $label)
                <label class="cursor-pointer">
                    <input type="radio" class="peer sr-only" name="composer_schedule_mode"
                           value="{{ $value }}" x-model="scheduleMode"/>
                    <span class="flex min-h-[2.625rem] items-center justify-center rounded-control
                                 border-2 border-neutral-200 bg-white px-2 py-2 text-body text-neutral-800
                                 shadow-e1 transition
                                 hover:border-neutral-400
                                 peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white
                                 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2
                                 peer-focus-visible:outline-primary">
                        <span class="truncate font-semibold">{{ $label }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        <x-error-span>{{ $errors->first('form.schedule_mode') }}</x-error-span>
    </div>

    {{-- ───────────────────────── Fixed ───────────────────────── --}}
    <div x-cloak x-show="scheduleMode === '1'" class="mt-5 space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-forms.input-label for="composer_work_hours_from">@lang('general.work-hours-from')</x-forms.input-label>
                <x-forms.date-input id="composer_work_hours_from" type="time" x-model="workHoursFrom" />
                <x-error-span>{{ $errors->first('form.work_hours_from') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="composer_work_hours_to">@lang('general.work-hours-to')</x-forms.input-label>
                <x-forms.date-input id="composer_work_hours_to" type="time" x-model="workHoursTo" />
                <x-error-span>{{ $errors->first('form.work_hours_to') }}</x-error-span>
            </div>
        </div>

        {{-- A second shift in the same day, e.g. 9:00-13:00 and 17:00-21:00. --}}
        <div>
            <button type="button" x-cloak x-show="!hasSecondShift()" class="{{ $miniButton }}"
                    x-on:click="toggleSecondShift(true)">
                <svg class="size-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M10 5v10M5 10h10" stroke-linecap="round"/>
                </svg>
                @lang('general.composer.add-second-shift')
            </button>

            <div x-cloak x-show="hasSecondShift()" x-collapse>
                <div class="flex items-center justify-between">
                    <x-forms.input-label>@lang('general.composer.second-shift')</x-forms.input-label>
                    <button type="button" class="{{ $miniButton }}" x-on:click="toggleSecondShift(false)">
                        @lang('general.composer.remove-second-shift')
                    </button>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-forms.input-label for="composer_work_hours_2_from">@lang('general.work-hours-from')</x-forms.input-label>
                        <x-forms.date-input id="composer_work_hours_2_from" type="time" x-model="workHours2From" />
                        <x-error-span>{{ $errors->first('form.work_hours_2_from') }}</x-error-span>
                    </div>

                    <div>
                        <x-forms.input-label for="composer_work_hours_2_to">@lang('general.work-hours-to')</x-forms.input-label>
                        <x-forms.date-input id="composer_work_hours_2_to" type="time" x-model="workHours2To" />
                        <x-error-span>{{ $errors->first('form.work_hours_2_to') }}</x-error-span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-forms.input-label for="composer_work_day_from" required>@lang('general.composer.work-day-from')</x-forms.input-label>
                <x-forms.select id="composer_work_day_from" :options="$dayNames" x-model="workDayFrom"
                                 :placeholder="__('general.select')" />
                <x-error-span>{{ $errors->first('form.work_day_from') }}</x-error-span>
            </div>

            <div>
                <x-forms.input-label for="composer_work_day_to" required>@lang('general.composer.work-day-to')</x-forms.input-label>
                <x-forms.select id="composer_work_day_to" :options="$dayNames" x-model="workDayTo"
                                 :placeholder="__('general.select')" />
                <x-error-span>{{ $errors->first('form.work_day_to') }}</x-error-span>
            </div>
        </div>

        {{--
            Both figures are computed from the ranges above, never typed in —
            the checkbox only controls whether the printed contract states the
            sentence, so it stays off until someone deliberately wants it there.
        --}}
        <div class="grid gap-2 sm:grid-cols-2">
            <label class="flex items-center gap-2 text-body text-neutral-800">
                <x-forms.input type="checkbox" x-model="showDaysPerWeek" id="composer_show_days_per_week" />
                <span x-text="fixedDaysPerWeekLabel"></span>
            </label>

            <label class="flex items-center gap-2 text-body text-neutral-800">
                <x-forms.input type="checkbox" x-model="showHoursPerWeek" id="composer_show_hours_per_week" />
                <span x-text="fixedHoursPerWeekLabel"></span>
            </label>
        </div>
    </div>

    {{-- ───────────────────────── Flexible ───────────────────────── --}}
    <div x-cloak x-show="scheduleMode === '2'" class="mt-5 space-y-3">
        <p class="text-small text-secondary-medium">@lang('general.composer.schedule-flexible-hint')</p>

        {{-- Days/week --}}
        <div class="rounded-control border border-neutral-200 p-3">
            <label class="flex items-center gap-2 text-body font-medium text-neutral-800">
                <x-forms.input type="checkbox" x-model="flexDaysPerWeekOn" id="composer_flex_days_on" />
                @lang('general.composer.flex-days-per-week')
            </label>
            <div x-cloak x-show="flexDaysPerWeekOn" x-collapse class="mt-2 max-w-[10rem]">
                <x-forms.input id="composer_flex_days_per_week" type="number" min="1" max="7" x-model="flexDaysPerWeek" />
                <x-error-span>{{ $errors->first('form.flex_days_per_week') }}</x-error-span>
            </div>
        </div>

        {{-- Hours/week — the one flex box that feeds weekly_hours directly. --}}
        <div class="rounded-control border border-neutral-200 p-3">
            <label class="flex items-center gap-2 text-body font-medium text-neutral-800">
                <x-forms.input type="checkbox" x-model="flexHoursPerWeekOn" id="composer_flex_hours_week_on" />
                @lang('general.composer.flex-hours-per-week')
            </label>
            <div x-cloak x-show="flexHoursPerWeekOn" x-collapse class="mt-2 max-w-[10rem]">
                <x-forms.input id="composer_flex_hours_per_week" type="number" min="1" x-model="flexHoursPerWeek" />
                <x-error-span>{{ $errors->first('form.flex_hours_per_week') }}</x-error-span>
            </div>
        </div>

        {{-- Hours/day --}}
        <div class="rounded-control border border-neutral-200 p-3">
            <label class="flex items-center gap-2 text-body font-medium text-neutral-800">
                <x-forms.input type="checkbox" x-model="flexHoursPerDayOn" id="composer_flex_hours_day_on" />
                @lang('general.composer.flex-hours-per-day')
            </label>
            <div x-cloak x-show="flexHoursPerDayOn" x-collapse class="mt-2 max-w-[10rem]">
                <x-forms.input id="composer_flex_hours_per_day" type="number" min="1" max="24" x-model="flexHoursPerDay" />
                <x-error-span>{{ $errors->first('form.flex_hours_per_day') }}</x-error-span>
            </div>
        </div>

        {{-- Varies by requirements --}}
        <div class="rounded-control border border-neutral-200 p-3">
            <label class="flex items-center gap-2 text-body font-medium text-neutral-800">
                <x-forms.input type="checkbox" x-model="flexVariesByRequirements" id="composer_flex_varies" />
                @lang('general.composer.flex-varies-by-requirements')
            </label>
        </div>

        {{-- Coordinated with direct manager --}}
        <div class="rounded-control border border-neutral-200 p-3">
            <label class="flex items-center gap-2 text-body font-medium text-neutral-800">
                <x-forms.input type="checkbox" x-model="flexCoordinatedWithManager" id="composer_flex_coordinated" />
                @lang('general.composer.flex-coordinated-with-manager')
            </label>
        </div>

        {{-- Custom text, bilingual --}}
        <div class="rounded-control border border-neutral-200 p-3">
            <label class="flex items-center gap-2 text-body font-medium text-neutral-800">
                <x-forms.input type="checkbox" x-model="flexCustomOn" id="composer_flex_custom_on" />
                @lang('general.composer.flex-custom')
            </label>
            <div x-cloak x-show="flexCustomOn" x-collapse class="mt-2 grid gap-3 sm:grid-cols-2">
                <div>
                    <x-forms.input-label for="composer_flex_custom_en">@lang('general.composer.flex-custom') (EN)</x-forms.input-label>
                    <x-forms.input id="composer_flex_custom_en" type="text" dir="ltr" maxlength="255" x-model="flexCustomEn" />
                    <x-error-span>{{ $errors->first('form.flex_custom_en') }}</x-error-span>
                </div>
                <div>
                    <x-forms.input-label for="composer_flex_custom_ar">@lang('general.composer.flex-custom') (AR)</x-forms.input-label>
                    <x-forms.input id="composer_flex_custom_ar" type="text" dir="rtl" maxlength="255" x-model="flexCustomAr" />
                    <x-error-span>{{ $errors->first('form.flex_custom_ar') }}</x-error-span>
                </div>
            </div>
        </div>
    </div>

    {{--
        The number every mode ultimately has to produce — LeaveBalanceService's
        real entitlement engine. Always visible so it is never a surprise at
        submit time, in both modes.
    --}}
    <div class="mt-5 flex items-center justify-between rounded-control border border-neutral-200
                bg-neutral-50 px-3 py-2">
        <span class="text-body font-medium text-neutral-800">@lang('general.weekly-hours')</span>
        <span class="text-body font-semibold tabular-nums" x-bind:class="weeklyHours > 0 ? 'text-neutral-900' : 'text-primary'"
              x-text="weeklyHoursSummary"></span>
    </div>
    <x-error-span>{{ $errors->first('form.weekly_hours') }}</x-error-span>

    {{-- Optional and rarely used, so folded away rather than shown by default. --}}
    <div class="mt-5">
        <button type="button" x-cloak x-show="!showNotes" class="{{ $miniButton }}" x-on:click="showNotes = true">
            <svg class="size-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M10 5v10M5 10h10" stroke-linecap="round"/>
            </svg>
            @lang('general.composer.add-notes')
        </button>

        <div x-cloak x-show="showNotes" x-collapse class="grid gap-3 sm:grid-cols-2">
            @foreach(config('app.supported_locales') as $notesLocale)
                <div>
                    <x-forms.input-label for="composer_notes_{{ $notesLocale }}">
                        @lang('general.attending-notes') ({{ strtoupper($notesLocale) }})
                    </x-forms.input-label>
                    <x-forms.textarea
                        id="composer_notes_{{ $notesLocale }}"
                        rows="3"
                        maxlength="255"
                        dir="{{ $notesLocale === 'ar' ? 'rtl' : 'ltr' }}"
                        x-model="attendingNotes{{ ucfirst($notesLocale) }}"
                        placeholder="{{ __('general.attending-notes-placeholder-'.$notesLocale) }}"
                    />
                    <x-error-span>{{ $errors->first('form.attending_notes_'.$notesLocale) }}</x-error-span>
                </div>
            @endforeach
        </div>
    </div>

    <x-contract.continue section="schedule" />
</x-contract.section>
