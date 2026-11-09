@props([
    'label',
    'id' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
    'disabled' => false,
    'lockedReason' => null,
    'searchable' => null,
    'searchLabel' => null,
    'value' => null,
    'employees' => false,
    'chip' => false,
    'chipIcon' => null,
])

{{-- x-ui.form-select for several values: the menu stays open while picking, and the trigger names the picks. --}}
<x-ui.form-select
    multiple
    :label="$label"
    :id="$id"
    :placeholder="$placeholder"
    :required="$required"
    :help="$help"
    :disabled="$disabled"
    :locked-reason="$lockedReason"
    :searchable="$searchable"
    :search-label="$searchLabel"
    :value="$value"
    :employees="$employees"
    :chip="$chip"
    :chip-icon="$chipIcon"
    :attributes="$attributes"
>{{ $slot }}</x-ui.form-select>
