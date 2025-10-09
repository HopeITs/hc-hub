<div class="space-y-8">
    <div class="flex justify-between items-center">
        <x-heading-label>{{ __('general.done_requests') }}</x-heading-label>
        <span class="text-sm text-gray-500">
            {{ $items->count() }} {{ \Illuminate\Support\Str::plural(__('general.request'), $items->count()) }}
        </span>
    </div>

    @if($items->isEmpty())
        <div class="text-center text-gray-400 italic py-12 border border-dashed border-gray-300 rounded-lg bg-gray-50">
            {{ __('general.no_done_requests') }}
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
            <table class="min-w-full text-sm text-left text-gray-700">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3">{{ __('general.employee') }}</th>
                        <th class="px-4 py-3">{{ __('general.action') }}</th>
                        <th class="px-4 py-3">{{ __('general.item') }}</th>
                        <th class="px-4 py-3">{{ __('general.counterparty') }}</th>
                        <th class="px-4 py-3">{{ __('general.date') }}</th>
                        <th class="px-4 py-3">{{ __('general.note') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach ($items as $item)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-medium">{{ $item['employee'] }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 inline-flex text-xs font-medium rounded-full
                                    @if($item['action'] === 'Delivered')
                                        bg-blue-100 text-blue-700
                                    @else
                                        bg-green-100 text-green-700
                                    @endif">
                                    {{ $item['action'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $item['item'] }}</td>
                            <td class="px-4 py-3">{{ $item['counterparty'] }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $item['date'] }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $item['notes'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
