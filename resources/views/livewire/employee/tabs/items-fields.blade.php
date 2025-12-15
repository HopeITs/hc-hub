<div class="space-y-6">
    <x-heading-label>@lang('general.items_from_checklist')</x-heading-label>

    @if(!empty($employeeChecklistItems) && $employeeChecklistItems->isNotEmpty())
        <div class="overflow-x-auto rounded-lg border">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">@lang('general.item')</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">@lang('general.note')</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">@lang('general.status')</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">@lang('general.received_at')</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($employeeChecklistItems as $item)
                        @php
                            $status = match (true) {
                                $item->returned_status === 'received' => __('general.received'),
                                $item->delivered_status === 'delivered' => __('general.delivered'),
                                default => __('general.on_hold'),
                            };

                            $statusColor = match ($status) {
                                __('general.received') => 'bg-blue-100 text-blue-700',
                                __('general.delivered') => 'bg-green-100 text-green-700',
                                default => 'bg-yellow-100 text-yellow-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->item?->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->notes ?: '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 inline-flex text-xs font-medium rounded-full {{ $statusColor }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{-- Fix: Only show date if status is delivered AND date exists --}}
                                {{ $item->delivered_status === 'delivered' && $item->delivered_at ? $item->delivered_at->format('Y-m-d') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center text-gray-500 italic py-8">
            @lang('general.no_items_from_checklist')
        </div>
    @endif

    <div class="flex justify-end pt-4 mt-6 border-t">
        <x-secondary-button :href="route('employees.index')">@lang('general.back_to_list')</x-secondary-button>
    </div>
</div>
