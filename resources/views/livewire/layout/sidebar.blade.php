<aside id="logo-sidebar" class="fixed top-0 right-auto  md:right-0 z-40 w-60 h-screen pt-24 transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700" aria-label="Sidebar" inert>
    <div class="h-full px-3 pb-4 overflow-y-auto bg-white dark:bg-gray-800">
        <ul class="space-y-2 font-medium">
            <x-layouts.app.sidebar.dropdown-nav-list :title="__('general.project-basics')"  :active="request()->routeIs('cities.index') || request()->routeIs('contract-types.*') || request()->routeIs('leave-types.*') || request()->routeIs('checklist-items.*')">
{{--                @if (auth()->check() && auth()->user()->hasRole('Super Admin'))--}}
                    <x-layouts.app.sidebar.nav-item  :href="route('cities.index')" :active="request()->routeIs('cities.index')" wire:navigate>{{ trans_choice('general.city', 2) }}</x-layouts.app.sidebar.nav-item>
{{--                @endif--}}
                <x-layouts.app.sidebar.nav-item>{{ trans_choice('general.contract-type', 2) }}</x-layouts.app.sidebar.nav-item>
                <x-layouts.app.sidebar.nav-item>{{ trans_choice('general.leave-type', 2) }}</x-layouts.app.sidebar.nav-item>
                <x-layouts.app.sidebar.nav-item>{{ trans_choice('general.checklist-items', 2) }}</x-layouts.app.sidebar.nav-item>
            </x-layouts.app.sidebar.dropdown-nav-list>

        </ul>
    </div>
</aside>
