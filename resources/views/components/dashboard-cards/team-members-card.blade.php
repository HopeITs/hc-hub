@php
    $query = request()->get('search');
@endphp

<div x-data="{ search: '' }"
     class="relative w-full h-full min-w-sm bg-white rounded-xl shadow-lg overflow-hidden flex flex-col">

    <!-- Header -->
    <div class="flex items-center justify-between gap-6 px-6 py-8">
        <h2 class="text-sm font-semibold text-secondary-medium flex items-center gap-2 whitespace-nowrap">
            Contact The Team
        </h2>

        <div class="relative flex-1">
            <input
                x-model="search"
                type="text"
                placeholder="Search"
                class="w-full h-6 text-xs rounded-full border border-gray-300 bg-white
                       pl-8 pt-2.5 pr-3 py-3 placeholder-secondary-light text-gray-700
                       focus:border-gray-400 focus:ring-0 outline-none transition">
            <div class="absolute left-2 top-[13px] -translate-y-1/2 text-gray-400 pointer-events-none">
                <x-icons.search class="w-2 h-2"/>
            </div>
        </div>
    </div>

    <!-- Scrollable section (fixed height so it doesn’t shrink when filtering) -->
    <div class="relative px-6 pt-2 space-y-3 overflow-y-auto flex-grow
                scrollbar-thin scrollbar-thumb-[var(--color-neutral-300)] scrollbar-track-transparent
                hover:scrollbar-thumb-[var(--color-primary-300)] min-h-[440px] max-h-[440px]">

        @forelse($this->teamMembers as $member)
            @php
                $phone = preg_replace('/\D+/', '', $member->personal_phone ?? '');
            @endphp

            <div
                x-show="!search || '{{ strtolower($member->displayName ?? $member->name) }}'
                    .includes(search.toLowerCase()) ||
                    '{{ strtolower($member->positionTitle ?? '') }}'
                    .includes(search.toLowerCase())"
                x-transition
                class="flex items-center justify-between py-2 border-b border-[var(--color-neutral-200)] last:border-0">

                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="flex items-center justify-center h-10 w-10 flex-shrink-0 rounded-full overflow-hidden
                                bg-[var(--color-primary-500)] text-white font-semibold uppercase shadow-sm">
                        @if($member->getFirstMediaUrl('profile'))
                            <img src="{{ $member->getFirstMediaUrl('profile') }}"
                                 alt="{{ $member->displayName ?? $member->name }}"
                                 class="h-full w-full object-cover">
                        @else
                            {{ collect(explode(' ', $member->displayName ?? $member->name))
                                ->map(fn($n) => Str::substr($n, 0, 1))
                                ->join('') }}
                        @endif
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-bold text-secondary-medium truncate">
                            {{ $member->displayName ?? $member->name }}
                        </p>
                        <p class="text-2xs font-light text-secondary-light truncate">
                            {{ Str::limit($member->positionTitle ?? '—', 40) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 ml-4 flex-shrink-0">
                    <a href="{{ route('employees.show', ['employee' => $member->username]) }}"
                       class="text-secondary-light hover:text-primary transition"
                       title="View Profile">
                        <x-icons.user class="w-5 h-5"/>
                    </a>

                    <a href="tel:{{ $phone }}"
                       class="text-secondary-light hover:text-primary transition"
                       title="Call">
                        <x-icons.phone class="w-3 h-3"/>
                    </a>

                    <a href="tg://resolve?phone={{ $phone }}"
                       class="text-secondary-light hover:text-primary transition"
                       title="Chat on Telegram">
                        <x-icons.telegram class="w-3.5 h-3.5"/>
                    </a>

                    <a href="mailto:{{ $member->account->email ?? '#' }}"
                       class="text-secondary-light hover:text-primary transition"
                       title="Send Email">
                        <x-icons.envelope class="w-2 h-2"/>
                    </a>

                </div>

            </div>
        @empty
            <div class="flex flex-col items-center justify-center text-[var(--color-neutral-500)] text-sm py-10">
                <div class="text-3xl mb-1">👤</div>
                <p class="font-medium">No team members found</p>
            </div>
        @endforelse
    </div>

    <!-- Footer flush at bottom -->
    <div class="border-t border-[var(--color-neutral-200)] bg-[var(--color-neutral-50)] px-6 py-3 text-center mt-auto">
        <a href="{{ route('org.chart') }}"
           class="text-[12px] font-semibold text-[var(--color-primary-500)] hover:underline">
            View All →
        </a>
    </div>
</div>
