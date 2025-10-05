<div class="space-y-6">
    <x-heading-label>{{ __('general.checklist_items') }}</x-heading-label>

    @if($items->isEmpty())
        <div class="text-center text-gray-500 italic py-8">
            {{ __('general.no_items') }}
        </div>
    @else
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th>@lang('general.employee')</th>
                    <th>@lang('general.item')</th>
                    <th>@lang('general.status')</th>
                    <th>@lang('general.notes')</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $item->employee->name }}</td>
                        <td>{{ $item->item->name }}</td>
                        <td>{{ ucfirst($item->delivered_status ?? $item->returned_status) }}</td>
                        <td>{{ $item->return_notes ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
