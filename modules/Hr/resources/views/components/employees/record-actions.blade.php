@props(['kind', 'id', 'name' => '', 'file' => null, 'editable' => false])

{{-- The actions at the end of a record row on the profile page: the row's file, then edit and remove for HR. --}}
<span {{ $attributes->class('flex shrink-0 items-center gap-1') }}>
    @if ($file !== null)
        <x-ui.button tone="ghost" size="sm" icon="paper-clip" :href="route('hr.employees.files.download', ['media' => $file])" :aria-label="__('common.download_name', ['name' => $name])" />
    @endif
    @if ($editable)
        <x-ui.button tone="ghost" size="sm" icon="pencil-square" wire:click="openRecord('{{ $kind }}', {{ (int) $id }})" :aria-label="__('common.edit_name', ['name' => $name])" />
        <x-ui.button tone="ghost" size="sm" icon="trash" wire:click="confirmRecordDelete('{{ $kind }}', {{ (int) $id }})" :aria-label="__('common.delete_name', ['name' => $name])" />
    @endif
</span>
