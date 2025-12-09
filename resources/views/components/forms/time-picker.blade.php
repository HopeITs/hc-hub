@props([
    'id',
    'wireModel',
    'format' => 'HH:mm',
])

@php($pickerId = $id . '_picker')
<x-forms.input id="{{ $id }}" type="hidden" wire:model.defer="{{ $wireModel }}" />
<div class="mt-2" wire:ignore>
    <clock-timepicker id="{{ $pickerId }}" format="{{ $format }}"></clock-timepicker>
</div>

<script type="module">
    if (!customElements.get('clock-timepicker')) {
        import('https://unpkg.com/clock-timepicker/dist/clock-timepicker.js');
    }

    (function bindClock(instanceId, pickerId) {
        function __bind() {
            var hidden = document.getElementById(instanceId);
            var picker = document.getElementById(pickerId);
            if (!hidden || !picker || picker.dataset.bound === '1') return;

            if (hidden.value) picker.value = hidden.value;

            function setHidden(val) {
                hidden.value = val || '';
                hidden.dispatchEvent(new Event('input', { bubbles: true }));
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
            }

            picker.addEventListener('change', function () { setHidden(picker.value || ''); });
            picker.addEventListener('input', function () { setHidden(picker.value || ''); });

            picker.dataset.bound = '1';
        }

        __bind();
        document.addEventListener('livewire:load', function () {
            __bind();
            if (window.Livewire && Livewire.hook) {
                Livewire.hook('message.processed', __bind);
            }
        });
    })(@json($id), @json($pickerId));
</script>

<style>
    clock-timepicker { --clock-timepicker-accent-color: #be1f24 }
</style>
