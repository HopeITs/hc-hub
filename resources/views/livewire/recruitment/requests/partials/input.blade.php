{{-- Every field on this form is required to save a draft (D55); the
     centre is the one exception and passes its own flag. So the star
     defaults to on, and a field that is genuinely optional says so. --}}
<div wire:key="field-{{ $field }}">
    <x-forms.input-label :for="$field" :required="$required ?? true">{{ __('recruitment.'.$field) }}</x-forms.input-label>
    <x-forms.input :id="$field" :type="$inputType" :model="'data.'.$field" />
    <p id="{{ $field }}-error" class="mt-1 text-sm text-red-600">@error('data.'.$field){{ $message }}@enderror</p>
</div>
