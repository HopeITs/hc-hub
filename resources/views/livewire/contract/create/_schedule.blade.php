{{-- Section 3 — days, hours, and the weekly total derived from both. --}}
<x-contract.section
    name="schedule"
    index="3"
    :title="__('general.composer.schedule')"
    :hint="__('general.composer.schedule-hint')"
    summary="scheduleSummary"
>
    {{-- Day strip. Writes both work_days_en and work_days_ar, which is why each
         toggle shows both names: what you pick is what gets stored, twice. --}}
    <div class="mt-4" x-cloak x-show="!manualDays">
        <div class="flex items-center justify-between gap-3">
            <x-forms.input-label required class="!mb-0">@lang('general.work-days')</x-forms.input-label>
            <button type="button"
                    class="text-caption text-secondary-medium underline decoration-dotted underline-offset-2
                           transition hover:text-primary"
                    x-on:click="enableManualDays()">@lang('general.composer.enter-manually')</button>
        </div>

        <div class="mt-2 flex flex-wrap gap-1.5" role="group" aria-label="@lang('general.work-days')">
            @foreach($dayNames['en'] as $index => $unused)
                <button
                    type="button"
                    class="flex min-w-[3.25rem] flex-1 flex-col items-center justify-center gap-0.5 rounded-control
                           border px-2 py-1.5 transition sm:flex-none
                           focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                    x-bind:aria-pressed="days[{{ $index }}]"
                    x-bind:class="days[{{ $index }}]
                        ? 'border-primary bg-primary text-white'
                        : 'border-neutral-200 bg-white text-neutral-800 hover:border-neutral-300'"
                    x-on:click="toggleDay({{ $index }})"
                >
                    <span class="text-small font-medium">{{ $dayShort[$locale][$index] }}</span>
                    <span class="text-caption"
                          x-bind:class="days[{{ $index }}] ? 'text-white/70' : 'text-secondary-light'"
                    >{{ $dayShort[$locale === 'ar' ? 'en' : 'ar'][$index] }}</span>
                </button>
            @endforeach
        </div>

        <p x-cloak x-show="selectedDayCount === 0" class="mt-2 text-caption text-secondary-light">
            @lang('general.composer.work-days-required')
        </p>
    </div>

    {{-- Escape hatch for rotas the strip cannot express. --}}
    <div class="mt-4" x-cloak x-show="manualDays">
        <div class="flex items-center justify-between gap-3">
            <x-forms.input-label required class="!mb-0">@lang('general.work-days')</x-forms.input-label>
            <button type="button"
                    class="text-caption text-secondary-medium underline decoration-dotted underline-offset-2
                           transition hover:text-primary"
                    x-on:click="disableManualDays()">@lang('general.composer.use-picker')</button>
        </div>

        <div class="mt-2 grid gap-3 sm:grid-cols-2">
            <div>
                <x-forms.input id="composer_work_days_en" type="text" dir="ltr" x-model="workDaysEnManual" />
                <p class="mt-1 text-caption text-secondary-light">EN</p>
            </div>
            <div>
                <x-forms.input id="composer_work_days_ar" type="text" dir="rtl" x-model="workDaysArManual" />
                <p class="mt-1 text-caption text-secondary-light">AR</p>
            </div>
        </div>
    </div>

    <x-error-span>{{ $errors->first('form.work_days_en') ?: $errors->first('form.work_days_ar') }}</x-error-span>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1.4fr]">
        <div>
            <x-forms.input-label for="composer_work_hours_from">@lang('general.work-hours-from')</x-forms.input-label>
            <x-forms.date-input id="composer_work_hours_from" type="time" x-model="workHoursFrom" />
        </div>

        <div>
            <x-forms.input-label for="composer_work_hours_to">@lang('general.work-hours-to')</x-forms.input-label>
            <x-forms.date-input id="composer_work_hours_to" type="time" x-model="workHoursTo" />
        </div>

        {{-- Derived from the two fields to its left, with a visible way out. --}}
        <div>
            <x-forms.input-label for="composer_weekly_hours" required>@lang('general.weekly-hours')</x-forms.input-label>

            <div x-cloak x-show="weeklyHoursIsAuto"
                 class="flex min-h-[2.375rem] items-center gap-2 rounded-control border border-neutral-200
                        bg-neutral-50 px-3 py-2">
                <span class="text-body font-medium tabular-nums text-neutral-900" x-text="weeklyHours + ' h'"></span>
                <span class="ms-auto flex items-center gap-2">
                    <span class="rounded-round bg-neutral-100 px-2 py-0.5 text-caption text-secondary-medium"
                          x-text="hoursDerivation"></span>
                    <button type="button" class="text-caption font-medium text-primary transition hover:underline"
                            x-on:click="startOverride()">@lang('general.composer.override')</button>
                </span>
            </div>

            <div x-cloak x-show="!weeklyHoursIsAuto" class="flex items-start gap-2">
                <div class="flex-1">
                    <x-forms.input id="composer_weekly_hours" type="number" min="0"
                                   x-ref="weeklyHours" x-model="weeklyHoursOverride" placeholder="40" />
                </div>
                <button type="button"
                        class="mt-2 shrink-0 text-caption font-medium text-primary transition hover:underline"
                        x-on:click="clearOverride()">@lang('general.composer.auto')</button>
            </div>

            <x-error-span>{{ $errors->first('form.weekly_hours') }}</x-error-span>
        </div>
    </div>

    {{-- Optional and rarely used, so folded away rather than shown by default. --}}
    <div class="mt-4">
        <button type="button" x-cloak x-show="!showNotes"
                class="inline-flex items-center gap-1.5 text-small font-medium text-primary transition hover:underline"
                x-on:click="showNotes = true">
            <span aria-hidden="true">+</span> @lang('general.composer.add-notes')
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
