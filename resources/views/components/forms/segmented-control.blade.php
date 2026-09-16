@props([
    'name',
    'model',
    'options',   // [value => label]
    'ariaLabel',
])

{{--
    A 2-way radio toggle styled as pressable segments rather than dots — the
    schedule-mode / pay-mode pattern. Fixed at two columns (both call sites
    are Fixed/Flexible toggles); a class built from count($options) would be
    invisible to Tailwind's static scanner, so this stays a literal utility
    rather than a computed one. The radios stay real inputs (peer-checked
    drives the visual state) so this keeps native keyboard and screen-reader
    semantics instead of reinventing them in Alpine.
--}}
<div {{ $attributes->class('mt-1 grid grid-cols-2 gap-2') }}
     role="radiogroup" aria-label="{{ $ariaLabel }}">
    @foreach ($options as $value => $label)
        <label class="cursor-pointer">
            <input type="radio" class="sr-only peer" name="{{ $name }}"
                   value="{{ $value }}" x-model="{{ $model }}" />
            <span class="flex min-h-[2.625rem] items-center justify-center rounded-control
                         border-2 border-neutral-200 bg-white px-2 py-2 text-body text-neutral-800
                         shadow-e1 transition
                         hover:border-neutral-400
                         peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white
                         peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2
                         peer-focus-visible:outline-primary">
                <span class="font-semibold truncate">{{ $label }}</span>
            </span>
        </label>
    @endforeach
</div>
