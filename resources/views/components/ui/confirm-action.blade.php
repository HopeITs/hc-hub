@props([
    'title',
    'message' => null,
    'confirm',
    'confirmLabel',
    'confirmTest' => null,
    'tone' => 'danger',
    'icon' => null,
    'record' => null,
    'recordMeta' => null,
    'permanent' => false,
])

@php
    /*
     * A trigger that asks first: the slot is the button, a click opens its own
     * x-ui.confirm-modal, and only the modal's action runs `confirm` (a Livewire
     * call such as "removeMember(5)"). It replaces the browser's own
     * confirm prompt, so every confirmation looks and behaves the same. The dialog name
     * follows the call, so a row's dialog keeps its name across re-renders.
     */
    $modalName = 'confirm-'.substr(md5($confirm), 0, 10);
@endphp

<span class="contents" x-on:click="$flux.modal(@js($modalName)).show()" aria-haspopup="dialog">{{ $slot }}</span>

<x-ui.confirm-modal
    :name="$modalName"
    :title="$title"
    :message="$message"
    :confirm="$confirm"
    :confirm-label="$confirmLabel"
    :confirm-test="$confirmTest"
    :tone="$tone"
    :icon="$icon"
    :record="$record"
    :record-meta="$recordMeta"
    :permanent="$permanent"
/>
