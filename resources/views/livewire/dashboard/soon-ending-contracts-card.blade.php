@php
    $locale = app()->getLocale();
    $titleField = 'title_' . $locale;
@endphp

<div class="w-full h-full bg-white rounded-3xl shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] overflow-y-auto">

    <div class="flex items-center gap-2 px-6 pt-6 pb-4">
        <h2 class="text-[18px] font-semibold text-secondary-medium">
            @lang('general.soon-ending-contracts')
        </h2>
        <x-icons.contracts class="mb-0.5 ml-1 text-primary"/>
    </div>

    <div class="px-6 pb-6 max-h-[300px] overflow-y-auto scrollbar-thin
                scrollbar-thumb-[var(--color-primary-300)]
                scrollbar-track-[var(--color-neutral-100)]">

        @forelse ($contracts as $contract)
            <div class="flex items-center justify-between py-4 border-b border-[var(--color-neutral-200)] last:border-0">

                <div class="flex items-center gap-3">

                    <div class="flex items-center justify-center h-11 w-11 flex-shrink-0 rounded-full overflow-hidden
                                bg-[var(--color-primary-500)] text-white font-semibold uppercase shadow-sm">

                        @if($contract->employee_photo)
                            <img src="{{ $contract->employee_photo }}"
                                 alt="{{ $contract->employee_name }}"
                                 class="h-full w-full object-cover">
                        @else
                            {{
                                collect(explode(' ', $contract->employee_name))
                                    ->map(fn($n) => Str::substr($n, 0, 1))
                                    ->join('')
                            }}
                        @endif

                    </div>

                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-[var(--color-neutral-800)] truncate">
                            {{ $contract->employee_name }}
                        </p>

                        <p class="text-xs text-[var(--color-neutral-500)] truncate">
                            {{ $contract->position->$titleField }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-col items-end">
                    <p class="text-sm font-semibold text-primary whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}
                    </p>

                </div>

            </div>
        @empty
            <div class="text-center p-4 text-sm text-[var(--color-neutral-500)]">
                No active contracts are set to end in the next 30 days.
            </div>
        @endforelse
    </div>
</div>
