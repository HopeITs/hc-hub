<aside id="cta-button-sidebar"
       class="fixed bg-white top-0 right-auto border-e-1 shadow-xl    md:right-0 z-40 w-60 h-screen pt-4 transition-transform -translate-x-full border-r border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
       aria-label="Sidebar">
    <div class="flex flex-col h-full justify-between">
        <div class="h-full px-3 pb-4 bg-white dark:bg-gray-800">
            <a href="{{route('home')}}" class="flex justify-center items-center mt-4 mb-8">
                <x-app-logo class="md:w-40 md:h-16"/>
            </a>
            <ul class="space-y-2 font-medium">
                <x-layouts.app.sidebar.dropdown-nav-list id="basic-project" :title="__('general.project-basics')"
                                                         :active="request()->routeIs('dashboard') || request()->routeIs('contract-types.*') || request()->routeIs('leave-types.*') || request()->routeIs('checklist-items.*')">
                    <x-slot name="icon">
                        <svg class="size-4 text-secondary-dark" aria-hidden="true"
                             xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="1.9"
                                  d="m4 12 8-8 8 8M6 10.5V19a1 1 0 0 0 1 1h3v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h3a1 1 0 0 0 1-1v-8.5"/>
                        </svg>

                    </x-slot>
                    <x-slot name="list">
                        {{--                @if (auth()->check() && auth()->user()->hasRole('Super Admin'))--}}
                        <x-layouts.app.sidebar.nav-item :href="route('cities.index')"
                                                        :active="request()->routeIs('cities.index') || request()->routeIs('dashboard') "
                                                        wire:navigate>{{ trans_choice('general.city', 2) }}</x-layouts.app.sidebar.nav-item>
                        {{--                @endif--}}
                        <x-layouts.app.sidebar.nav-item :href="route('contract-types.index')"
                                                        :active="request()->routeIs('contract-types.index') "
                                                        wire:navigate>{{ trans_choice('general.contract-type', 2) }}</x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item>{{ trans_choice('general.leave-type', 2) }}</x-layouts.app.sidebar.nav-item>
                        <x-layouts.app.sidebar.nav-item>{{ trans_choice('general.checklist-items', 2) }}</x-layouts.app.sidebar.nav-item>
                        <x-layouts.app.sidebar.nav-item>{{ trans_choice('general.job-level', 2) }}</x-layouts.app.sidebar.nav-item>
                        <x-layouts.app.sidebar.nav-item>{{ trans_choice('general.department', 2) }}</x-layouts.app.sidebar.nav-item>
                    </x-slot>

                </x-layouts.app.sidebar.dropdown-nav-list>

            </ul>
        </div>

        <div>
            <x-layouts.app.sidebar.profile-card :user="$user"/>
        </div>


    </div>


</aside>

