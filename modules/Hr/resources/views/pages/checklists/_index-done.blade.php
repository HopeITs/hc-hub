{{--
    "Done requests": the hand-overs and returns the viewer's service
    department (IT, Communication, HR) has completed. A received row reads as
    the receiver taking the item back from the employee; a delivered row as the
    deliverer handing it to the employee. Included by ⚡index.blade.php.
--}}
@php
    $rows = $this->doneRequests;
@endphp

@if ($this->departmentContext === null)
    <x-ui.empty-state icon="building-office-2" :heading="__('hr_checklists.empty.done_no_department_heading')" :body="__('hr_checklists.empty.done_no_department_body')" />
@elseif ($rows->isEmpty())
    <x-ui.empty-state icon="clipboard-document-check" :heading="$filtersActive ? __('hr_checklists.empty.no_matching') : __('hr_checklists.empty.done_heading')" :body="$filtersActive ? __('common.change_the_search_or_filters_and_try_again') : __('hr_checklists.empty.done_body')">
        @if ($filtersActive)
            <x-slot:action>
                <x-ui.button tone="secondary" size="sm" wire:click="clearFilters">{{ __('common.reset_filters') }}</x-ui.button>
            </x-slot:action>
        @endif
    </x-ui.empty-state>
@else
    <x-ui.data-table :caption="__('hr_checklists.lists.done')">
        <x-slot:head>
            <th>{{ __('hr_checklists.columns.done_by') }}</th>
            <th class="w-32!">{{ __('hr_checklists.columns.action') }}</th>
            <th>{{ __('hr_checklists.columns.item') }}</th>
            <th>{{ __('hr_checklists.columns.counterparty') }}</th>
            <th class="w-36!">{{ __('hr_checklists.columns.requested_on') }}</th>
            <th class="w-36!">{{ __('hr_checklists.columns.done_on') }}</th>
            <th>{{ __('hr_checklists.columns.note') }}</th>
        </x-slot:head>

        @foreach ($rows as $row)
            @php
                $received = $row->returned_status === 'received';
                $doneBy = $received ? $row->receiver?->name : $row->delivered?->name;
                $doneOn = $received ? $row->returned_at : $row->delivered_at;
            @endphp
            <tr wire:key="done-{{ $row->id }}">
                <x-ui.data-table.cell :label="__('hr_checklists.columns.done_by')"><span class="font-semibold" dir="auto">{{ $doneBy ?? '—' }}</span></x-ui.data-table.cell>
                <x-ui.data-table.cell :label="__('hr_checklists.columns.action')"><x-hr::checklists.status :delivered="$row->delivered_status" :returned="$received ? 'received' : null" /></x-ui.data-table.cell>
                <x-ui.data-table.cell :label="__('hr_checklists.columns.item')"><span dir="auto">{{ $row->item?->name ?? '—' }}</span></x-ui.data-table.cell>
                <x-ui.data-table.cell :label="__('hr_checklists.columns.counterparty')"><span dir="auto">{{ $row->employee?->name ?? '—' }}</span></x-ui.data-table.cell>
                <x-ui.data-table.cell :label="__('hr_checklists.columns.requested_on')">{{ $row->created_at?->translatedFormat(__('formats.date')) ?? '—' }}</x-ui.data-table.cell>
                <x-ui.data-table.cell :label="__('hr_checklists.columns.done_on')">{{ $doneOn?->translatedFormat(__('formats.date')) ?? '—' }}</x-ui.data-table.cell>
                <x-ui.data-table.cell :label="__('hr_checklists.columns.note')"><span dir="auto">{{ $row->notes ?: '—' }}</span></x-ui.data-table.cell>
            </tr>
        @endforeach
    </x-ui.data-table>

    <div class="mt-4">
        <flux:pagination :paginator="$rows" class="text-secondary-medium! dark:text-ink-muted!" scroll-to="#hr-checklists-heading" />
    </div>
@endif
