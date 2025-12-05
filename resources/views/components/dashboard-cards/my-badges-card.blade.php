@php
    $locale = app()->getLocale();
    $nameCol = "name_{$locale}";

    $badgeColors = [
        'unknown' => 'text-[#58595B]',
        'reward' => 'text-green-600',
        'warning' => 'text-primary',
        'bonus' => 'text-blue-600',
    ];

    $badgeTypes = [
        0 => 'Unknown',
        1 => 'Warning',
        2 => 'Reward',
        3 => 'Bonus',
    ];
@endphp

<div class="w-full h-full max-w-md bg-white rounded-3xl shadow-sm overflow-hidden flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between px-4 md:px-6 pt-4 pb-6 md:pt-5 md:pb-10 flex-shrink-0">
        <div class="flex items-center gap-2 md:gap-3">
            <h3 class="sm:text-[20px]  md:text-sm font-semibold text-[#58595B]">
                {{ __('general.badges-warnings') }}
            </h3>

            <span class="text-secondary-light sm:text-[20px] md:text-sm font-medium">
                {{ $myBadges->count() }}
            </span>

            <div class="text-primary w-4 h-4 ">
                <img src="{{ asset('assets/icons/badges.svg') }}" alt="Badges icon">
            </div>
        </div>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto px-4 md:px-6 pb-4 space-y-2
                scrollbar-thin scrollbar-thumb-[var(--color-primary-300)]
                scrollbar-track-[var(--color-neutral-100)]
                hover:scrollbar-thumb-[var(--color-primary-400)]">

        @forelse ($myBadges as $badge)
            @php
                $typeName = $badgeTypes[$badge->type] ?? 'Unknown';
                $colorClass = $badgeColors[strtolower($typeName)] ?? 'text-primary';
            @endphp

            <div class="flex justify-between items-center pb-2 border-b border-b-secondary-light"
                 wire:key="badge-{{ $badge->id }}">

                <p class=" sm:text-[14px] md:text-[11px] font-bold uppercase {{ $colorClass }}">
                    {{ $badge->reason ?? __('No reason provided') }}
                </p>

                <span class=" text-[10px] sm:text-[10px] md:text-[11px] text-[#888]">
                    {{ optional($badge->date)->format('d/m/Y') }}
                </span>
            </div>
        @empty
            <div class="text-center text-[10px] md:text-[11px] italic text-secondary-light py-4">
                {{ __('No badges found.') }}
            </div>
        @endforelse
    </div>
</div>
