@props(['active' => true])
<td class="px-5 py-2.5 text-gray-500">
    @if ($active)
        <span
            class="bg-success text-white rounded text-[10px] font-medium leading-4 py-0.5 px-1.5 inline-flex items-center badge-xs">
                    <i class="ti ti-point-filled me-1"></i>@lang('general.active')
                </span>
    @else
        <span
            class="bg-danger text-white rounded text-[10px] font-medium leading-4 py-0.5 px-1.5 inline-flex items-center badge-xs">
                    <i class="ti ti-point-filled me-1"></i>@lang('general.inactive')
                </span>
    @endif
</td>
