{{-- Every field on this form is required to save a draft (D55); the
     centre is the one exception and passes its own flag. So the star
     defaults to on, and a field that is genuinely optional says so. --}}
<div wire:key="field-{{ $field }}">
    <x-forms.input-label :for="$field" :required="$required ?? true">{{ __('recruitment.'.$field) }}</x-forms.input-label>
    <x-forms.textarea :id="$field" wire:model="data.{{ $field }}" aria-describedby="{{ $field }}-error" aria-invalid="{{ $errors->has('data.'.$field) ? 'true' : 'false' }}" />
    <p id="{{ $field }}-error" class="mt-1 text-sm text-red-600">@error('data.'.$field){{ $message }}@enderror</p>
</div>
