{{--
    "My items": everything HR assigned to the signed-in employee. Read-only;
    items arrive from HR, so there is no action to offer. Included by
    ⚡index.blade.php.
--}}
@php
    $items = $this->myItems;
@endphp

@if ($items->isEmpty())
    <x-ui.empty-state icon="clipboard-document-check" :heading="__('hr_checklists.empty.mine_heading')" :body="__('hr_checklists.empty.mine_body')" />
@else
    <x-ui.data-table :caption="__('hr_checklists.lists.mine')">
        <x-slot:head>
            <th>{{ __('hr_checklists.columns.item') }}</th>
            <th class="w-40!">{{ __('hr_checklists.columns.status') }}</th>
            <th class="w-40!">{{ __('hr_checklists.columns.delivered_at') }}</th>
            <th class="w-40!">{{ __('hr_checklists.columns.returned_at') }}</th>
            <th>{{ __('hr_checklists.columns.notes') }}</th>
        </x-slot:head>

        @foreach ($items as $row)
            <tr wire:key="my-item-{{ $row->id }}">
                <x-ui.data-table.cell :label="__('hr_checklists.columns.item')"><span class="font-semibold" dir="auto">{{ $row->item?->name ?? '—' }}</span></x-ui.data-table.cell>
                <x-ui.data-table.cell :label="__('hr_checklists.columns.status')"><x-hr::checklists.status :delivered="$row->delivered_status" :returned="$row->returned_status" /></x-ui.data-table.cell>
                <x-ui.data-table.cell :label="__('hr_checklists.columns.delivered_at')">{{ $row->delivered_at?->translatedFormat(__('formats.date')) ?? '—' }}</x-ui.data-table.cell>
                <x-ui.data-table.cell :label="__('hr_checklists.columns.returned_at')">{{ $row->returned_status === 'received' ? ($row->returned_at?->translatedFormat(__('formats.date')) ?? '—') : '—' }}</x-ui.data-table.cell>
                <x-ui.data-table.cell :label="__('hr_checklists.columns.notes')"><span dir="auto">{{ $row->notes ?: '—' }}</span></x-ui.data-table.cell>
            </tr>
        @endforeach
    </x-ui.data-table>
@endif
