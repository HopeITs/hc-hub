{{-- Every field on this form is required to save a draft (D55); the
     centre is the one exception and passes its own flag. So the star
     defaults to on, and a field that is genuinely optional says so. --}}
@php $isLocked = in_array($field, $lockedFields ?? [], true); @endphp
<div wire:key="field-{{ $field }}">
    <x-forms.input-label :for="$field" :required="$required ?? true">{{ __('recruitment.'.$field) }}</x-forms.input-label>
    <x-forms.input :id="$field" :type="$inputType" :model="'data.'.$field"
        :disabled="$isLocked"
        :class="$isLocked ? 'cursor-not-allowed border-secondary-soft bg-secondary-soft/60 text-secondary-light' : ''" />
    @if ($isLocked)
        <p class="mt-1 text-xs text-secondary-medium">{{ __('recruitment.field_locked_here') }}</p>
    @endif
    <p id="{{ $field }}-error" class="mt-1 text-sm text-primary">@error('data.'.$field){{ $message }}@enderror</p>
</div>
