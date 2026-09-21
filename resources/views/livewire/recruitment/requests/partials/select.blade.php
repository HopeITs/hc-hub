<div wire:key="field-{{ $field }}">
    <x-forms.input-label :for="$field" :required="$required">{{ __('recruitment.'.$label) }}</x-forms.input-label>
    <select id="{{ $field }}" wire:model.live="data.{{ $field }}" @required($required)
        aria-invalid="{{ $errors->has('data.'.$field) ? 'true' : 'false' }}" aria-describedby="{{ $field }}-error"
        class="w-full rounded-control border border-neutral-300 bg-white px-3 py-2 text-neutral-900 focus:border-primary focus:ring-primary">
        <option value="">{{ __('recruitment.choose') }}</option>
        @foreach ($options as $value => $option)<option value="{{ $value }}">{{ $option }}</option>@endforeach
    </select>
    <p id="{{ $field }}-error" class="mt-1 text-sm text-red-600">@error('data.'.$field){{ $message }}@enderror</p>
</div>
