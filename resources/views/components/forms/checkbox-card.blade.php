@props([
    'id',
    'model',            // Alpine x-model target for the checkbox, e.g. 'flexDaysPerWeekOn'
    'highlight' => null, // Alpine boolean expression; true paints the required-field accent stripe
])

{{--
    One bordered, tappable card per flexible-schedule/pay option. Replaces a
    handful of near-identical `border + p-3 + checkbox` divs that used to be
    copy-pasted per option (and each nested a plain <label> around
    <x-forms.input type="checkbox">, which already renders its own <label> —
    two <label> elements wrapping one input isn't valid HTML and confuses
    screen readers about the control's name). The slot is the checkbox's own
    label text; `hint` and `fields` are optional extras that only make sense
    once the box is checked.
--}}
<div
    {{ $attributes->class('rounded-control border border-neutral-200 p-3 transition') }}
    @if ($highlight) x-bind:class="({{ $highlight }}) ? 'border-s-4 border-s-primary bg-neutral-50' : ''" @endif
>
    <x-forms.input type="checkbox" x-model="{{ $model }}" id="{{ $id }}" label-class="font-medium text-body text-neutral-800">
        {{ $slot }}
    </x-forms.input>

    @isset($hint)
        {{ $hint }}
    @endisset

    @isset($fields)
        {{ $fields }}
    @endisset
</div>
