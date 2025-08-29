<aside id="cta-button-sidebar"
    class="fixed top-0 left-0 rtl:right-0 z-40 w-64 h-screen pt-4 transition-transform sm:translate-x-0 translate-x-full bg-white border-l border-gray-200 shadow-xl"
    aria-label="Sidebar">
    <div class="flex flex-col h-full justify-between">
        <div class="h-full px-3 pb-4 bg-white overflow-y-auto">
            <a href="{{ route('home') }}" class="flex justify-center items-center mt-4 mb-8">
                <x-app-logo class="md:w-40 md:h-16" />
            </a>
            <ul class="space-y-2 font-medium">
                <x-layouts.app.sidebar.dropdown-nav-list id="basic-program" :title="__('general.project-basics')" :active="request()->routeIs('dashboard') ||
                    request()->routeIs('cities.*') ||
                    request()->routeIs('contract-types.*') ||
                    request()->routeIs('leave-types.*') ||
                    request()->routeIs('checklist-items.*') ||
                    request()->routeIs('departments.*') ||
                    request()->routeIs('job-levels.*')">

                    <x-slot name="icon">
                        <x-icons.home />
                    </x-slot>
                    <x-slot name="list">
                        <x-layouts.app.sidebar.nav-item :href="route('cities.index')" :active="request()->routeIs('cities.index') || request()->routeIs('dashboard')">
                            {{ trans_choice('general.city', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item :href="route('contract-types.index')" :active="request()->routeIs('contract-types.index')">
                            {{ trans_choice('general.contract-type', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item :href="route('leave-types.index')" :active="request()->routeIs('leave-types.index')">
                            {{ trans_choice('general.leave-type', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item :href="route('checklist-items.index')" :active="request()->routeIs('checklist-items.index')">
                            {{ trans_choice('general.checklist-items', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item :href="route('job-levels.index')" :active="request()->routeIs('job-levels.index')">
                            {{ trans_choice('general.job-level', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item :href="route('departments.index')" :active="request()->routeIs('departments.index')" wire:navigate>
                            {{ trans_choice('general.department', 2) }}
                        </x-layouts.app.sidebar.nav-item>
                    </x-slot>

                </x-layouts.app.sidebar.dropdown-nav-list>

                <x-layouts.app.sidebar.dropdown-nav-list id="program-management" :title="__('general.programs-management')" :active="request()->routeIs('programs.*') ||

                    request()->routeIs('centers.*')">

                    <x-slot name="icon">
                        <x-icons.layers />
                    </x-slot>

                    <x-slot name="list">
                        <x-layouts.app.sidebar.nav-item :href="route('programs.index')" :active="request()->routeIs('programs.*')">
                            {{ trans_choice('general.program', 2) }}
                        </x-layouts.app.sidebar.nav-item>



                        <x-layouts.app.sidebar.nav-item :href="route('centers.index')" :active="request()->routeIs('centers.index')" wire:navigate>
                            {{ trans_choice('general.center', 2) }}
                        </x-layouts.app.sidebar.nav-item>
                    </x-slot>
                </x-layouts.app.sidebar.dropdown-nav-list>

                <x-layouts.app.sidebar.dropdown-nav-list id="employees-management" :title="trans_choice('general.employee-management', 2)"
                    :active="request()->routeIs('employees.*')">

                    <x-slot name="icon">
                        <x-icons.users />
                    </x-slot>
                    <x-slot name="list">
                        <x-layouts.app.sidebar.nav-item :href="route('employees.index')" :active="request()->routeIs('employees.*')">
                            {{ trans_choice('general.employee', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item :href="route('default-deliverables.index')" :active="request()->routeIs('default-deliverables.index')">
                            {{ trans_choice('general.default-deliverables', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item :href="route('contracts.index')" :active="request()->routeIs('contracts.*')">
                            {{ trans_choice('general.contracts', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                    </x-slot>
                </x-layouts.app.sidebar.dropdown-nav-list>

                <li>
                    <a href="{{ route('archived-files.index') }}"
                        class="flex items-center p-2 rounded-lg text-gray-900 hover:bg-gray-100 group @if (request()->routeIs('archived-files.*')) bg-gray-100 @endif">
                        <x-icons.folder
                            class="w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900" />
                        <span class="ms-3">{{ __('general.archived-files') }}</span>
                    </a>
                </li>

            </ul>
        </div>

        <div class="px-3 pb-4">
            <x-layouts.app.sidebar.profile-card :user="$user" />
        </div>
    </div>
</aside>
