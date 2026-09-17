<div wire:key="field-{{ $field }}">
    <x-forms.input-label :for="$field">{{ __('recruitment.'.$field) }}</x-forms.input-label>
    <x-forms.input :id="$field" :type="$inputType" :model="'data.'.$field" />
    <p id="{{ $field }}-error" class="mt-1 text-sm text-red-600">@error('data.'.$field){{ $message }}@enderror</p>
</div>
