@php
    use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

    $isRtl = app()->getLocale() === 'ar';
    $user = auth()->user();
    $activeRole = $user->active_role_name ?? __('None');
    $switchTo = $isRtl ? 'en' : 'ar';
    $localized = LaravelLocalization::getLocalizedURL($switchTo, null, [], true);
    $guideLabel = $isRtl ? 'دليل الاستخدام' : 'User guide';
    $homepageLabel = $isRtl ? 'الموقع الرئيسي' : 'Main website';
    $feedbackLabel = $isRtl ? 'إرسال مشكلة أو ملاحظة' : 'Send problem or feedback';
@endphp

<nav dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
    class="fixed inset-x-0 top-0 z-10 w-screen bg-white md:w-full dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">

    <div class="px-3 py-1 lg:px-5 lg:ps-3">
        <div class="flex items-center justify-end gap-2 md:pe-0 pe-4">
            <button wire:click="$dispatch('sidebar.toggle')" type="button"
                class="p-2 transition bg-transparent border border-transparent cursor-pointer md:hidden me-auto text-heading text-secondary-light hover:text-secondary-dark rounded-base">
                <span class="sr-only">Open sidebar</span>
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10" />
                </svg>
            </button>
            <a href="{{ $localized }}"
                class="inline-flex items-center justify-center h-9 min-w-9 px-2 pt-px text-base font-medium leading-none tracking-wide transition rounded-full text-secondary-medium hover:bg-gray-100 hover:text-secondary-dark focus:outline-none focus:ring-2 focus:ring-gray-200"
                title="{{ $switchTo === 'ar' ? 'العربية' : 'English' }}"
                aria-label="{{ $switchTo === 'ar' ? 'Switch to Arabic' : 'Switch to English' }}">
                {{ $switchTo === 'ar' ? 'AR' : 'EN' }}
            </a>

            <span class="w-px h-6 bg-secondary-soft" aria-hidden="true"></span>

            <a href="https://docs.google.com/document/d/1AcrvWNBzoD-bTem3Gy_35EtDgwD-SXST/edit?usp=sharing&ouid=105248334092459549739&rtpof=true&sd=true"
                target="_blank" rel="noopener"
                class="inline-flex items-center justify-center transition rounded-full size-9 text-secondary-light hover:bg-gray-100 hover:text-secondary-dark focus:outline-none focus:ring-2 focus:ring-gray-200"
                title="{{ $guideLabel }}"
                aria-label="{{ $guideLabel }}">
                <x-icons.user-guide class="size-[24px]" />
            </a>

            <span class="w-px h-6 bg-secondary-soft" aria-hidden="true"></span>

            <a href="https://hcsyria.org/home" target="_blank" rel="noopener"
                class="inline-flex items-center justify-center transition rounded-full size-9 text-secondary-light hover:bg-gray-100 hover:text-secondary-dark focus:outline-none focus:ring-2 focus:ring-gray-200"
                title="{{ $homepageLabel }}"
                aria-label="{{ $homepageLabel }}">
                <x-icons.homepage class="size-5" />
            </a>

            <span class="w-px h-6 bg-secondary-soft" aria-hidden="true"></span>

            <a href="https://docs.google.com/forms/d/e/1FAIpQLSdxNuk6xkFZY1t4vb3kYEKkwMMPM6VnvfGPO-iH1VfTCiEHMQ/viewform"
                target="_blank" rel="noopener"
                class="inline-flex items-center justify-center transition rounded-full size-9 text-secondary-light hover:bg-gray-100 hover:text-secondary-dark focus:outline-none focus:ring-2 focus:ring-gray-200"
                title="{{ $feedbackLabel }}"
                aria-label="{{ $feedbackLabel }}">
                <x-icons.guide class="size-5" />
            </a>

            <span class="w-px h-6 bg-secondary-soft" aria-hidden="true"></span>

            <x-layouts.app.notification />

            @auth
                <div x-data="roleDropdown()" x-init="$store.dir = { rtl: @js($isRtl) }" class="relative ms-3" @keydown.escape.window="open=false">

                    <button @click="toggle()" :aria-expanded="open" aria-haspopup="menu" class="inline-flex items-center justify-center transition rounded-full size-9 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">

                        <span class="relative inline-flex items-center justify-center rounded-full size-9 shrink-0">
                            <img class="object-cover border rounded-full size-8 border-primary"
                                src="{{ $user->profile?->getFirstMediaUrl('avatar') }}" alt="user" />

                            <span
                                class="absolute bottom-0.5 {{ $isRtl ? 'start-0.5' : 'end-0.5' }}
                                         size-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                        </span>
                    </button>

                    <div x-cloak x-show="open" x-transition.duration.160ms.origin.bottom @click.outside="open=false"
                        :class="$store.dir.rtl ? 'end-0' : 'end-0'"
                        class="z-50 absolute top-full mt-2 w-[250px] rounded-3xl border bg-white
                                shadow-[0_25px_80px_-20px_rgba(2,6,23,0.35)] ring-1 ring-black/5"
                        role="menu">

                        <div class="relative px-5 pt-5 pb-4">
                            <div
                                class="pointer-events-none absolute inset-0 opacity-[0.07]
                                    bg-[radial-gradient(1200px_400px_{{ $isRtl ? 'at_90%_-20%' : 'at_10%_-20%' }},theme(colors.primary.500/.6),transparent_40%)]">
                            </div>

                            <div class="relative flex items-center gap-3">
                                <span class="inline-flex items-center justify-center overflow-hidden rounded-full size-14 shrink-0">
                                    <img class="object-cover border rounded-full size-12 border-primary"
                                        src="{{ $user->profile?->getFirstMediaUrl('avatar') }}" alt="user">
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
                            <a href="{{ route('my-profile') }}" wire:navigate
                                class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-800 transition rounded-xl hover:bg-secondary-soft hover:text-primary hover:shadow-sm">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <circle cx="12" cy="8" r="4" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 20c0-4 4-6 8-6s8 2 8 6" />
                                </svg>
                                <span>@lang('general.my-profile')</span>
                            </a>

                            <a href="{{ route('settings.password') }}"
                                class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-800 transition rounded-xl hover:bg-gray-100/90 hover:shadow-sm">
                                <svg class="text-gray-700 size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <rect x="5" y="10" width="14" height="10" rx="2" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10V7a4 4 0 0 1 8 0v3" />
                                </svg>
                                <span>@lang('general.my-account')</span>
                            </a>
                        </div>

                        <div class="h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>

                        {{--                        @if ($user->hasMultipleRoles()) --}}
                        {{--                            <div class="px-5 py-4"> --}}
                        {{--                                <div class="flex items-center justify-between mb-2"> --}}
                        {{--                                    <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide"> --}}
                        {{--                                        {{ __('Switch role') }} --}}
                        {{--                                    </div> --}}

                        {{--                                    <button @click="rolesOpen = !rolesOpen" --}}
                        {{--                                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 --}}
                        {{--                                                   px-3 py-1.5 text-xs font-medium hover:border-secondary-medium --}}
                        {{--                                                   hover:bg-secondary-soft transition"> --}}
                        {{--                                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" --}}
                        {{--                                             stroke-width="1.8"> --}}
                        {{--                                            <path d="M16 3h5v5"/> --}}
                        {{--                                            <path d="M4 20l16-16"/> --}}
                        {{--                                            <path d="M21 16v5h-5"/> --}}
                        {{--                                            <path d="M15 15l6 6"/> --}}
                        {{--                                            <path d="M3 3l6 6"/> --}}
                        {{--                                        </svg> --}}
                        {{--                                        {{ __('Switch') }} --}}
                        {{--                                    </button> --}}
                        {{--                                </div> --}}

                        {{--                                <div x-show="rolesOpen" x-transition.origin.top> --}}
                        {{--                                    <livewire:auth.role-switcher :onlySwitchables="true" :wire:key="'role-switcher-pill'"/> --}}
                        {{--                                </div> --}}

                        {{--                                <p class="mt-2 text-[11px] text-gray-500">{{ __('Choose a role to make it active instantly.') }}</p> --}}
                        {{--                            </div> --}}

                        {{--                            <div class="h-px bg-gray-100"></div> --}}
                        {{--                        @endif --}}

                        <div class="px-2 py-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="flex items-center w-full gap-3 px-3 py-2 text-sm font-semibold text-red-600 transition rounded-xl hover:bg-red-50/80 hover:shadow-sm">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.8">
                                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                                        <path d="M10 17l5-5-5-5" />
                                        <path d="M15 12H3" />
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
