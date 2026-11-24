{{--
    Study Zone's "Friday is considered a day off…" sentence, printed only when
    HR ticked it on the contract form. The day is the weekend leave pricing
    uses (see WeekendClause); with none known, nothing is printed.
--}}
@props(['contract'])

@use('Modules\Hr\Support\Contracts\WeekendClause')

@php
    $weekendDays = app(WeekendClause::class)->daysForContract($contract);
@endphp

@if ($weekendDays !== [])
    @php
        $weekendSentences = WeekendClause::sentences($weekendDays);
    @endphp

    <x-hr::contract-legal.dual class="mt-1" :en="e($weekendSentences['en'])" :ar="e($weekendSentences['ar'])" data-test="weekend-clause" />
@endif
