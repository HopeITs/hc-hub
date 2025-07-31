@props([
    'sortable' => null,
    'direction' => null
])

<th {{$attributes->merge([
    'class' => 'text-sm px-5 py-2.5 bg-gray-200 rtl:text-right text-left text-gray-900 border-secondary-medium'

])}}
>
    @unless($sortable)
        <span class="text-right text-xs leading-4 font-medium text-secondary-medium uppercase tracking-wider">
              {{$slot}}
        </span>
    @else
        <button
            {{$attributes->except('class') }} class="flex items-center space-x-1 text-right text-xs leading-4 font-medium">
            <span>{{$slot}}</span>

            <span>
            @if($direction === 'asc')
                    <svg class="size-3 text-gray-800 dark:text-white" aria-hidden="true"
                         xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="m8 10 4 4 4-4"/>
                    </svg>

                @elseif($direction === 'desc')
                    <svg class="size-3 text-gray-800 dark:text-white" aria-hidden="true"
                         xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="m16 14-4-4-4 4"/>
                    </svg>

                @else
                    <svg class="size-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300 text-secondary-dark " aria-hidden="true"
                         xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="m8 10 4 4 4-4"/>
                    </svg>
                @endif
        </span>
        </button>

    @endif

</th>
