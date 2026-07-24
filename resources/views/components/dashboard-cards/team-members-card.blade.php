@php
    $query = request()->get('search');
    $locale = app()->getLocale();
    $titleCol = $locale === 'ar' ? 'title_ar' : 'title_en';
@endphp

<div x-data="{ search: '' }"
    class="relative w-full h-full bg-white rounded-surface shadow-e1 overflow-hidden flex flex-col">

    <!-- Header -->
    <div class="flex items-center justify-between gap-6 px-6 pt-4 pb-4">
        <h2 class="flex items-center gap-2 text-body font-semibold text-secondary-medium whitespace-nowrap">
            @lang('general.contact-the-team')
        </h2>

        <div class="relative flex-1">
            <input x-model="search" type="text" placeholder="{{ __('general.search') }}"
                class="w-full h-6 text-small rounded-full border border-neutral-300 bg-white
                       ps-8 pt-2 pe-3 py-3 placeholder-secondary-light text-neutral-800
                       focus:border-neutral-400 focus:ring-0 outline-none transition">
            <div class="absolute left-2 top-[13px] -translate-y-1/2 text-secondary-light pointer-events-none">
                <x-icons.search class="w-2 h-2" />
            </div>
        </div>
    </div>

    <!-- Team List -->
    <div
        class="relative px-6 pt-2 space-y-3 overflow-y-auto flex-grow
                scrollbar-thin scrollbar-thumb-[var(--color-neutral-300)] scrollbar-track-transparent
                hover:scrollbar-thumb-[var(--color-secondary-medium)] max-h-[440px]">

        @forelse($this->teamMembers as $member)
            @php
                // Phone normalization
                $phone = preg_replace('/\D+/', '', $member->personal_phone ?? '');

                // Localized job title for each member
                $memberContract = $member->latestActiveContract();
                $memberTitle = $memberContract?->position?->$titleCol ?? ($memberContract?->position?->title_en ?? '—');
            @endphp

            <!-- Member Row -->
            <div x-show="
                    !search ||
                    '{{ strtolower($member->displayName ?? $member->name) }}'.includes(search.toLowerCase()) ||
                    '{{ strtolower($memberTitle) }}'.includes(search.toLowerCase())
                "
                x-transition
                class="flex items-center justify-between py-2 border-b border-[var(--color-neutral-200)] last:border-0">

                <!-- Avatar + Name + Title -->
                <div class="flex items-center flex-1 min-w-0 gap-3">

                    <!-- Avatar -->
                    <div
                        class="flex items-center justify-center h-10 w-10 flex-shrink-0 rounded-full overflow-hidden
                                bg-[var(--color-primary)] text-white font-semibold uppercase shadow-e1">
                        <img src="{{ $member->getFirstMediaUrl('avatar') }}"
                            alt="{{ $member->displayName ?? $member->name }}" class="object-fit w-full h-full">
                    </div>

                    <!-- Name + Position -->
                    <div class="min-w-0">
                        <p class="text-caption font-bold text-secondary-medium truncate">
                            {{ $member->displayName ?? $member->name }}
                        </p>

                        <p class="text-small font-light truncate text-secondary-light">
                            {{ Str::limit($memberTitle, 40) }}
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center flex-shrink-0 gap-2 ms-4">

                    <a href="{{ route('employees.show', ['employee' => $member?->username]) }}" title="View Profile">
                        <x-icons.user class="transition text-secondary-light hover:text-primary" />
                    </a>

                    <a href="tel:{{ $phone }}" title="Call">
                        <x-icons.phone class="text-secondary-light ml-1 hover:text-primary transition" />
                    </a>

                    <a href="tg://resolve?phone={{ $phone }}"
                        class="transition text-secondary-light hover:text-primary" title="Chat on Telegram">
                        <x-icons.telegram class="transition text-secondary-light hover:text-primary" />
                    </a>

                    <a href="mailto:{{ $member->account->email ?? '#' }}"
                        class="transition text-secondary-light hover:text-primary" title="Send Email">
                        <x-icons.envelope class="transition text-secondary-light ms-1 hover:text-primary" />
                    </a>

                </div>
            </div>

        @empty
            <div class="flex flex-col items-center justify-center h-full text-body font-light text-secondary-light">
                <span>@lang('general.no-available-data')</span>
            </div>
        @endforelse

    </div>

    <!-- Footer -->
    <div class="border-t border-[var(--color-neutral-200)] bg-white px-6 py-3 text-center mt-auto">
        <a href="{{ route('org.chart') }}"
            class="text-small font-semibold text-[var(--color-primary)] hover:underline">
            @lang('general.view-all') →
        </a>
    </div>
</div>
