<div class="space-y-8">
    <div class="flex justify-between items-center">
        <x-heading-label>{{ __('general.pending_checklist_requests') }}</x-heading-label>
        @if (session()->has('message'))
            <span class="text-green-600 text-sm font-medium">{{ session('message') }}</span>
        @endif
    </div>

    @if($pendingItems->isEmpty())
        <div class="text-center text-gray-400 italic py-12 border border-dashed border-gray-300 rounded-lg bg-gray-50">
            {{ __('general.no_pending_requests') }}
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
            <table class="min-w-full text-sm text-left text-gray-700">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3">{{ __('general.employee') }}</th>
                        <th class="px-4 py-3">{{ __('general.item') }}</th>
                        <th class="px-4 py-3">{{ __('general.request_date') }}</th>
                        <th class="px-4 py-3">{{ __('general.note') }}</th>
                        <th class="px-4 py-3 text-center w-40"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach ($pendingItems as $item)
                        <tr class="hover:bg-gray-50 transition" wire:key="pending-deliver-{{ $item->id }}">
                            <td class="px-4 py-3 font-medium">{{ $item->employee->name }}</td>
                            <td class="px-4 py-3">{{ $item->item->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $item->created_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <input type="text"
                                    wire:model.defer="notes.{{ $item->id }}"
                                    wire:key="note-input-{{ $item->id }}"
                                    placeholder="{{ __('general.add_note') }}"
                                    class="w-full rounded-md border-red-500 text-sm focus:border-red-600 focus:ring-red-600">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button wire:click="markDelivered({{ $item->id }})"
                                    class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition">
                                    {{ __('general.mark_as_delivered') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
