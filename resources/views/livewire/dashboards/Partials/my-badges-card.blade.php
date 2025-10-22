@php
    $locale = app()->getLocale();
    $nameCol = "name_{$locale}";

    $badgeColors = [
        'unknown' => 'bg-gray-100 text-gray-600 border-gray-300',
        'reward' => 'bg-green-100 text-green-700 border-green-300',
        'warning' => 'bg-red-100 text-red-700 border-red-300',
        'bonus' => 'bg-blue-100 text-blue-700 border-blue-300',

    ];

    $badgeTypes = [
        0 => 'Unknown',
        1 => 'Warning',
        2 => 'Reward',
        3 => 'Bonus',
    ];
@endphp

<div class="w-full max-w-sm bg-white rounded-2xl shadow-md border border-[var(--color-neutral-200)] overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-3 flex items-center justify-between bg-[var(--color-primary)] text-white">
        <h2 class="text-lg font-bold tracking-wide">{{ __('My Badges') }}</h2>
        <span class="text-xs bg-white/20 px-2.5 py-0.5 rounded-full">
            {{ $myBadges->count() }} {{ __('Badges') }}
        </span>
    </div>

    <!-- Scrollable Content -->
    <div class="max-h-72 overflow-y-auto divide-y divide-[var(--color-neutral-200)] scrollbar-thin scrollbar-thumb-[var(--color-neutral-300)] scrollbar-track-transparent">
        @forelse ($myBadges as $badge)
            @php
                $typeName = $badgeTypes[$badge->type] ?? __('Unknown');
                $color = $badgeColors[strtolower($typeName)] ?? 'bg-[var(--color-neutral-100)] text-[var(--color-neutral-700)] border-[var(--color-neutral-200)]';
            @endphp

            <div class="flex items-start justify-between px-6 py-4 hover:bg-[var(--color-neutral-100)] transition-colors duration-200">
                <!-- Left Section -->
                <div class="flex flex-col h-12 justify-between">
                    <span class="text-[var(--color-neutral-900)] font-semibold text-sm">
                        {{ $badge->employee?->$nameCol ?? __('Unknown Employee') }}
                    </span>
                    <p class="text-xs text-[var(--color-neutral-600)] italic">
                        “{{ $badge->reason ?? __('No reason provided') }}”
                    </p>

                </div>

                <!-- Right Section -->
                <div class="flex flex-col h-12 justify-between">

                    <span class="mt-1 inline-block px-2.5 py-0.5 text-xs font-semibold border rounded-full {{ $color }}">
                        {{ $typeName }}
                    </span>

                    <div class="text-right text-xs text-[var(--color-neutral-600)] font-medium whitespace-nowrap">
                        {{ optional($badge->date)->format('d M Y') }}
                    </div>



                </div>

            </div>
        @empty
            <div class="text-center py-10 text-[var(--color-neutral-400)] text-sm">
                {{ __('No badges found.') }}
            </div>
        @endforelse
    </div>
</div>
