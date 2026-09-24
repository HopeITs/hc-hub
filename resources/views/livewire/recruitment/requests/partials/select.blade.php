{{-- Every field on this form is required to save a draft (D55); the
     centre is the one exception and passes its own flag. So the star
     defaults to on, and a field that is genuinely optional says so. --}}
@php $isLocked = in_array($field, $lockedFields ?? [], true); @endphp
<div wire:key="field-{{ $field }}">
    <x-forms.input-label :for="$field" :required="$required ?? true">{{ __('recruitment.'.$label) }}</x-forms.input-label>
    <select id="{{ $field }}" wire:model.live="data.{{ $field }}" @required($required ?? true)
        @disabled($isLocked)
        aria-invalid="{{ $errors->has('data.'.$field) ? 'true' : 'false' }}" aria-describedby="{{ $field }}-error"
        class="w-full rounded-control border px-3 py-2 focus:border-primary focus:ring-primary
            {{ $isLocked ? 'cursor-not-allowed border-neutral-200 bg-neutral-100 text-neutral-400' : 'border-neutral-300 bg-white text-neutral-900' }}">
        <option value="">{{ __('recruitment.choose') }}</option>
        @foreach ($options as $value => $option)<option value="{{ $value }}">{{ $option }}</option>@endforeach
    </select>
    @if ($isLocked)
        <p class="mt-1 text-xs text-secondary-medium">{{ __('recruitment.field_locked_here') }}</p>
    @endif
    <p id="{{ $field }}-error" class="mt-1 text-sm text-red-600">@error('data.'.$field){{ $message }}@enderror</p>
</div>
