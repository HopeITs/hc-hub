@php
    use Illuminate\Support\Js;

    // Work days are demanded when a contract is written from scratch, but not
    // when an imported one is being edited — those predate the field entirely.
    $requireWorkDays ??= true;

    // Quiet-but-visible control, used by the notes disclosure at the foot.
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

    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <x-forms.input-label for="composer_work_hours_from">@lang('general.work-hours-from')</x-forms.input-label>
            <x-forms.date-input id="composer_work_hours_from" type="time" x-model="workHoursFrom" />
        </div>

        <div>
            <x-forms.input-label for="composer_work_hours_to">@lang('general.work-hours-to')</x-forms.input-label>
            <x-forms.date-input id="composer_work_hours_to" type="time" x-model="workHoursTo" />
        </div>

        <div>
            <x-forms.input-label for="composer_weekly_hours" required>@lang('general.weekly-hours')</x-forms.input-label>

            <div class="relative">
                <x-forms.input
                    id="composer_weekly_hours"
                    type="number"
                    min="0"
                    x-model="weeklyHours"
                    x-bind:placeholder="'40'"
                    class="pe-16"
                />
                <span class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-small text-secondary-medium">
                    {{ app()->getLocale() === 'ar' ? 'س/أسبوع' : 'h/week' }}
                </span>
            </div>

            <x-error-span>{{ $errors->first('form.weekly_hours') }}</x-error-span>
        </div>
    </div>

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
