@php
    $query = request()->get('search');
@endphp

<div x-data="{ search: '' }" class="relative w-full max-w-lg bg-[var(--color-neutral-50)] rounded-3xl shadow-lg overflow-hidden">

    <div class="flex items-center justify-between px-5 pt-4 pb-3 border-b border-[var(--color-primary-200)] bg-[var(--color-primary-500)] text-white">
        <div class="flex items-center gap-2">
            <div class="flex items-center justify-center h-8 w-8 rounded-lg bg-white/20 text-white shadow-sm">
                <x-icons.users class="w-5 h-5 text-white" />
            </div>
            <h2 class="text-lg font-extrabold tracking-tight">Team Members</h2>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('org.chart') }}" class="text-[11px] font-semibold text-white/90 hover:text-white transition">
                View All →
            </a>
        </div>
    </div>

    <div class="px-5 pt-4 pb-2">
        <div class="relative">
            <input x-model="search"
                   type="text"
                   placeholder="Search team members..."
                   class="w-full text-sm rounded-xl border border-[var(--color-primary-200)] bg-white pl-10 pr-3 py-2 focus:ring-2 focus:ring-[var(--color-primary-400)] focus:border-[var(--color-primary-400)] outline-none transition"
            >
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--color-primary-400)]">
                <x-icons.search class="w-4 h-4" />
            </div>
        </div>
    </div>

    <div class="relative p-5 pt-2 space-y-3 overflow-y-auto max-h-[400px] scroll-smooth
                scrollbar-thin scrollbar-thumb-[var(--color-primary-300)] scrollbar-track-[var(--color-neutral-100)]
                hover:scrollbar-thumb-[var(--color-primary-400)]">

        @forelse($this->teamMembers as $member)
            @php
                $phone = preg_replace('/\D+/', '', $member->personal_phone ?? '');
            @endphp
            <div x-show="!search || '{{ strtolower($member->displayName ?? $member->name) }}'.includes(search.toLowerCase()) || '{{ strtolower($member->positionTitle ?? '') }}'.includes(search.toLowerCase())"
                 class="flex items-center justify-between p-4 bg-white rounded-2xl border border-[var(--color-primary-100)]
                        hover:border-[var(--color-primary-300)] hover:shadow-md transition-all duration-150">

                <div class="flex items-center gap-4 flex-1 min-w-0">
                    <div class="flex items-center justify-center h-14 w-14 flex-shrink-0 rounded-full overflow-hidden
                                ring-2 ring-[var(--color-primary-500)] bg-[var(--color-primary-500)]
                                text-white text-xl font-semibold uppercase shadow-sm">
                        @if($member->getFirstMediaUrl('profile'))
                            <img src="{{ $member->getFirstMediaUrl('profile') }}"
                                 alt="{{ $member->displayName ?? $member->name }}"
                                 class="h-full w-full object-cover">
                        @else
                            {{ collect(explode(' ', $member->displayName ?? $member->name))
                                ->map(fn($n) => Str::substr($n,0,1))
                                ->join('') }}
                        @endif
                    </div>

                    <div class="min-w-0 flex-1 pr-3">
                        <p class="text-sm font-bold text-[var(--color-neutral-800)] leading-tight truncate">
                            {{ $member->displayName ?? $member->name }}
                        </p>
                        <p class="text-[11px] text-[var(--color-neutral-500)] leading-tight truncate">
                            {{ Str::limit($member->positionTitle ?? '—', 30) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-1 flex-shrink-0">
                    <a href="mailto:{{ $member->account->email ?? '#' }}"
                       class="flex items-center justify-center w-8 h-8 rounded-full text-[var(--color-primary-600)]
                              hover:bg-[var(--color-primary-50)] transition"
                       title="Send Email">
                        <x-icons.envelope class="w-4.5 h-4.5" />
                    </a>

                    <a href="tg://resolve?phone={{ $phone }}"
                       class="flex items-center justify-center w-8 h-8 rounded-full text-sky-500 hover:bg-sky-50 transition"
                       title="Chat on Telegram">
                        <x-icons.telegram class="w-5 h-5" />
                    </a>

                    <a href="{{ route('employees.show', ['employee' => $member->username]) }}"
                       class="flex items-center justify-center w-8 h-8 rounded-full
                              text-[var(--color-primary-600)] hover:bg-[var(--color-primary-50)] transition"
                       title="View Employee Details">
                        <x-icons.user class="w-5.5 h-5.5" />
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
</div>
