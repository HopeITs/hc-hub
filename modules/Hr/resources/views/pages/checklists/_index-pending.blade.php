{{--
    A pending list: items waiting for the signed-in employee to hand over
    ($confirm = markDelivered) or to take back ($confirm = markReceived). Each
    row takes an optional note and one confirm button. Included by
    ⚡index.blade.php with $rows, $confirm, $confirmLabel, $personLabel,
    $emptyHeading and $emptyBody.
--}}
@if ($rows->isEmpty())
    <x-ui.empty-state icon="check-circle" :heading="$emptyHeading" :body="$emptyBody" />
@else
    <x-ui.data-table :caption="$confirmLabel">
        <x-slot:head>
            <th>{{ $personLabel }}</th>
            <th>{{ __('hr_checklists.columns.item') }}</th>
            <th class="w-36!">{{ __('hr_checklists.columns.requested_on') }}</th>
            <th>{{ __('hr_checklists.columns.note') }}</th>
            <th class="w-44!"><span class="sr-only">{{ __('common.actions') }}</span></th>
        </x-slot:head>

        @foreach ($rows as $row)
            @php
                $itemName = $row->item?->name ?? '—';
            @endphp
            <tr wire:key="pending-{{ $confirm }}-{{ $row->id }}">
                <x-ui.data-table.cell :label="$personLabel"><span class="font-semibold" dir="auto">{{ $row->employee?->name ?? '—' }}</span></x-ui.data-table.cell>
                <x-ui.data-table.cell :label="__('hr_checklists.columns.item')"><span dir="auto">{{ $itemName }}</span></x-ui.data-table.cell>
                <x-ui.data-table.cell :label="__('hr_checklists.columns.requested_on')">{{ $row->created_at?->translatedFormat(__('formats.date')) ?? '—' }}</x-ui.data-table.cell>
                <x-ui.data-table.cell :label="__('hr_checklists.columns.note')">
                    <input
                        type="text"
                        wire:model="notes.{{ $row->id }}"
                        wire:key="note-{{ $confirm }}-{{ $row->id }}"
                        maxlength="1000"
                        dir="auto"
                        placeholder="{{ __('hr_checklists.add_note') }}"
                        aria-label="{{ __('hr_checklists.note_for', ['item' => $itemName]) }}"
                        class="h-9 w-full min-w-40 rounded-lg border border-secondary-light bg-white px-3 text-sm text-secondary-dark outline-none focus:border-secondary-medium dark:border-field-line dark:bg-surface-2 dark:text-ink"
                    >
                </x-ui.data-table.cell>
                <td class="px-3.5 py-2.5">
                    <span class="flex items-center justify-end">
                        <x-ui.button tone="secondary" size="sm" icon="check" wire:click="{{ $confirm }}({{ $row->id }})" wire:loading.attr="disabled" wire:target="{{ $confirm }}({{ $row->id }})" data-test="{{ $confirm }}-{{ $row->id }}">
                            {{ $confirmLabel }}
                        </x-ui.button>
                    </span>
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>
@endif
