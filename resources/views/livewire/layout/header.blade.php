@php
    use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

    $isRtl = app()->getLocale() === 'ar';
    $user = auth()->user();
    $activeRole = $user->active_role_name ?? __('None');
    $switchTo = $isRtl ? 'en' : 'ar';
    $localized = LaravelLocalization::getLocalizedURL($switchTo, null, [], true);
@endphp

<nav dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
     class="fixed inset-x-0 top-0 z-10 w-screen md:w-full  bg-white dark:bg-gray-800 backdrop-blur">

    <div class="px-3 py-1 lg:px-5 lg:ps-3">
        <div class="flex items-center justify-end gap-3 md:pe-0 pe-4">
            <button
                wire:click="$dispatch('sidebar.toggle')"
                type="button"
                class="sm:hidden me-auto text-heading text-secondary-light bg-transparent border border-transparent
           hover:text-secondary-dark transition cursor-pointer rounded-base p-2"
            >
                <span class="sr-only">Open sidebar</span>
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10"/>
                </svg>
            </button>
            <a href="{{ $localized }}"
               class="flex items-center text-xs font-medium mt-1 text-secondary-light hover:text-secondary-dark transition">
                {{ $switchTo === 'ar' ? 'AR' : 'EN' }}
            </a>

            <a href="https://docs.google.com/document/d/1jQ8kl4mo1ARDBO17l-jqXp5hux9wn3--/edit"
               target="_blank" rel="noopener"
               class="pe-1 ps-3 border-x border-e-secondary-soft">
                <x-icons.guide class="text-secondary-light hover:text-secondary-dark me-2 transition"/>
            </a>

            <a href="https://hcsyria.org/home"
               target="_blank" rel="noopener"
               class="pe-3 border-e border-e-secondary-soft">
                <x-icons.homepage class="text-secondary-light hover:text-secondary-dark transition"/>
            </a>

            <x-layouts.app.notification/>

            @auth
                <div x-data="roleDropdown()" x-init="$store.dir = { rtl: @js($isRtl) }"
                     class="relative" @keydown.escape.window="open=false">

                    <button @click="toggle()" :aria-expanded="open"
                            aria-haspopup="menu" class="flex items-center">

                        <span class="relative inline-grid place-items-center rounded-full p-[2px] shrink-0">
                            <img class="size-7.5 border border-primary rounded-full object-cover"
                                 src="{{ $user->profile->getFirstMediaUrl('avatar') }}" alt="user"/>

                            <span class="absolute bottom-0.5 {{ $isRtl ? 'start-1' : 'end-0.5' }}
                                         size-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                        </span>
                    </button>

                    <div x-cloak x-show="open" x-transition.duration.160ms.origin.bottom
                         @click.outside="open=false"
                         :class="$store.dir.rtl ? 'end-0' : 'end-0'"
                         class="z-50 absolute top-full mt-2 w-[250px] rounded-3xl border bg-white
                                shadow-[0_25px_80px_-20px_rgba(2,6,23,0.35)] ring-1 ring-black/5"
                         role="menu">

                        <div class="relative px-5 pt-5 pb-4">
                            <div class="pointer-events-none absolute inset-0 opacity-[0.07]
                                    bg-[radial-gradient(1200px_400px_{{ $isRtl ? 'at_90%_-20%' : 'at_10%_-20%' }},theme(colors.primary.500/.6),transparent_40%)]">
                            </div>

                            <div class="relative flex items-center gap-3">
                                <span class="p-[3px] rounded-full">
                                    <img class="size-12 border border-primary rounded-full object-cover"
                                         src="{{ $user->profile->getFirstMediaUrl('avatar') }}" alt="user">
                                </span>

                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-gray-900 truncate">
                                        {{ $user->profile?->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 truncate">{{ $user->email }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ $user->myRole() }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>

                        <div class="px-2 py-2">
                            <a href="{{ route('settings.password') }}"
                               class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                      text-gray-800 hover:bg-gray-100/90 hover:shadow-sm transition">
                                <svg class="size-4 text-gray-700" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.8">
                                    <circle cx="12" cy="8" r="4"/>
                                    <path d="M4 20c0-4 4-6 8-6s8 2 8 6"/>
                                </svg>
                                <span>@lang('general.my-account')</span>
                            </a>
                        </div>

                        <div class="h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>

{{--                        @if ($user->hasMultipleRoles())--}}
{{--                            <div class="px-5 py-4">--}}
{{--                                <div class="flex items-center justify-between mb-2">--}}
{{--                                    <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">--}}
{{--                                        {{ __('Switch role') }}--}}
{{--                                    </div>--}}

{{--                                    <button @click="rolesOpen = !rolesOpen"--}}
{{--                                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200--}}
{{--                                                   px-3 py-1.5 text-xs font-medium hover:border-primary-400--}}
{{--                                                   hover:bg-primary-50/70 transition">--}}
{{--                                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"--}}
{{--                                             stroke-width="1.8">--}}
{{--                                            <path d="M16 3h5v5"/>--}}
{{--                                            <path d="M4 20l16-16"/>--}}
{{--                                            <path d="M21 16v5h-5"/>--}}
{{--                                            <path d="M15 15l6 6"/>--}}
{{--                                            <path d="M3 3l6 6"/>--}}
{{--                                        </svg>--}}
{{--                                        {{ __('Switch') }}--}}
{{--                                    </button>--}}
{{--                                </div>--}}

{{--                                <div x-show="rolesOpen" x-transition.origin.top>--}}
{{--                                    <livewire:auth.role-switcher :onlySwitchables="true" :wire:key="'role-switcher-pill'"/>--}}
{{--                                </div>--}}

{{--                                <p class="mt-2 text-[11px] text-gray-500">{{ __('Choose a role to make it active instantly.') }}</p>--}}
{{--                            </div>--}}

{{--                            <div class="h-px bg-gray-100"></div>--}}
{{--                        @endif--}}

                        <div class="px-2 py-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-semibold
                                               text-red-600 hover:bg-red-50/80 hover:shadow-sm transition">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.8">
                                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                        <path d="M10 17l5-5-5-5"/>
                                        <path d="M15 12H3"/>
                                    </svg>
                                    @lang('auth.logout')
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endauth
        </div>
    </div>
</nav>

<script>
    function roleDropdown() {
        return {
            open: false,
            rolesOpen: false,
            activeRole: @js($activeRole),
            toggle() {
                this.open = !this.open;
            },
        }
    }
</script>
