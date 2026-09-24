{{--
    A picker on the request form.

    Every field on this form is required to save a draft (D55); the centre is
    the one exception and passes its own flag, so the star defaults to on.

    The control is the searchable select the rest of the application uses. A
    plain dropdown of two hundred positions is a scroll, not a choice — and a
    form that looks different from every other form in the system reads as a
    different system.

    A locked field is shown and disabled rather than hidden: after an approver
    asks for changes, only what they named may be edited, and a field that
    silently vanished would leave the requester wondering what happened to it.
--}}
@php
    $isLocked = in_array($field, $lockedFields ?? [], true);

    // The searchable select keeps its own DOM under wire:ignore, so Livewire
    // never rewrites the options inside it. Lists that depend on another
    // answer — centres on the chosen city, vacancies on the chosen post —
    // would go on offering the old ones for ever. Folding the options into the
    // key makes Livewire replace the whole control when they change, which
    // re-initialises the select against the new list.
    $optionsKey = md5(json_encode(array_keys($options)));
@endphp
<div wire:key="field-{{ $field }}-{{ $optionsKey }}">
    <x-forms.input-label :for="$field" :required="$required ?? true">{{ __('recruitment.'.$label) }}</x-forms.input-label>

    <x-forms.select2-input
        :id="$field"
        wire:model.live="data.{{ $field }}"
        :disabled="$isLocked"
        aria-invalid="{{ $errors->has('data.'.$field) ? 'true' : 'false' }}"
        aria-describedby="{{ $field }}-error">
        <option value="">{{ __('recruitment.choose') }}</option>
        @foreach ($options as $value => $option)
            <option value="{{ $value }}">{{ $option }}</option>
        @endforeach
    </x-forms.select2-input>

    @if ($isLocked)
        <p class="mt-1 text-xs text-secondary-medium">{{ __('recruitment.field_locked_here') }}</p>
    @endif
    <p id="{{ $field }}-error" class="mt-1 text-sm text-red-600">@error('data.'.$field){{ $message }}@enderror</p>
</div>
