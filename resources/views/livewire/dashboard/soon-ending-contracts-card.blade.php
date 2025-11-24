@php
    $locale = app()->getLocale();
    $titleField = 'title_' . $locale;
@endphp

<div class="w-full h-full bg-white rounded-3xl shadow-sm overflow-hidden">

    <div class="flex items-center gap-2 px-6 pt-6 pb-4">
        <h2 class="text-lg font-semibold text-[var(--color-neutral-800)]">
            Contracts Ending Soon
        </h2>
        <x-icons.document-text class="w-5 h-5 text-primary"/>
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

                    <a href="{{ $contract->view_route }}"
                       class="text-[12px] mt-0.5 text-secondary-light font-light underline-offset-2 hover:underline">
                        View
                    </a>
                </div>

            </div>
        @empty
            <div class="text-center p-4 text-sm text-[var(--color-neutral-500)]">
                No active contracts are set to end in the next 30 days.
            </div>
        @endforelse
    </div>
</div>
