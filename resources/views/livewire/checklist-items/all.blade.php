<div class="space-y-8">
    <div class="flex items-center justify-between">
        <x-heading-label>{{ __('general.checklist_items') }}</x-heading-label>
        <span class="text-sm text-gray-500">
            {{ $items->count() }} {{ \Illuminate\Support\Str::plural(__('general.item'), $items->count()) }}
        </span>
    </div>

    @if($items->isEmpty())
        <div class="text-center text-gray-500 italic py-12 bg-gray-50 rounded-lg border border-dashed">
            {{ __('general.no_items') }}
        </div>
    @else
        <div class="overflow-hidden shadow-sm border border-gray-200 rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            @lang('general.employee')
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            @lang('general.item')
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            @lang('general.status')
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            @lang('general.notes')
                        </th>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach ($items as $item)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm text-gray-800">
                                {{ $item->employee->name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-800">
                                {{ $item->item->name }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 inline-flex text-xs font-medium rounded-full
                                    @if(strtolower($item->delivered_status ?? $item->returned_status) === 'delivered')
                                        bg-green-100 text-green-700
                                    @elseif(strtolower($item->delivered_status ?? $item->returned_status) === 'pending')
                                        bg-yellow-100 text-yellow-700
                                    @else
                                        bg-gray-100 text-gray-700
                                    @endif">
                                    {{ ucfirst($item->delivered_status ?? $item->returned_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $item->notes ?: ($item->return_notes ?: '-') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
