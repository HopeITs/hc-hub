<aside id="cta-button-sidebar"
       class="fixed bg-white top-0 right-auto border-e-1 shadow-xl    md:right-0 z-40 w-60 h-screen pt-4 transition-transform -translate-x-full border-r border-gray-200 sm:translate-x-0"
       aria-label="Sidebar">
    <div class="flex flex-col h-full justify-between">
        <div class="h-full px-3 pb-4 bg-white">
            <a href="{{ route('home') }}" class="flex justify-center items-center mt-4 mb-8">
                <x-app-logo class="md:w-40 md:h-16"/>
            </a>
            <ul class="space-y-2 font-medium">
                <x-layouts.app.sidebar.dropdown-nav-list
                    id="basic-project"
                    :title="__('general.project-basics')"
                    :active="
                        request()->routeIs('dashboard') ||
                        request()->routeIs('cities.*') ||
                        request()->routeIs('contract-types.*') ||
                        request()->routeIs('leave-types.*') ||
                        request()->routeIs('checklist-items.*') ||
                        request()->routeIs('departments.*') ||
                        request()->routeIs('job-levels.*')">

                    <x-slot name="icon">
                        <x-icons.home/>
                    </x-slot>
                    <x-slot name="list">
                        <x-layouts.app.sidebar.nav-item
                            :href="route('cities.index')"
                            :active="request()->routeIs('cities.index') || request()->routeIs('dashboard')">
                            {{ trans_choice('general.city', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item
                            :href="route('contract-types.index')"
                            :active="request()->routeIs('contract-types.index')">
                            {{ trans_choice('general.contract-type', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item
                            :href="route('leave-types.index')"
                            :active="request()->routeIs('leave-types.index')">
                            {{ trans_choice('general.leave-type', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item
                            :href="route('checklist-items.index')"
                            :active="request()->routeIs('checklist-items.index')">
                            {{ trans_choice('general.checklist-items', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item
                            :href="route('job-levels.index')"
                            :active="request()->routeIs('job-levels.index')">
                            {{ trans_choice('general.job-level', 2) }}
                        </x-layouts.app.sidebar.nav-item>


                        <x-layouts.app.sidebar.nav-item
                            :href="route('departments.index')"
                            :active="request()->routeIs('departments.index')"
                            wire:navigate>
                            {{ trans_choice('general.department', 2) }}
                        </x-layouts.app.sidebar.nav-item>
                    </x-slot>

                </x-layouts.app.sidebar.dropdown-nav-list>

                {{-- إدارة المشاريع - NEW DROPDOWN --}}
                <x-layouts.app.sidebar.dropdown-nav-list
                    id="project-management"
                    :title="__('general.projects-management')"
                    :active="
                        request()->routeIs('projects.*') ||
                        request()->routeIs('required-positions.*') ||
                        request()->routeIs('centers.*')">

                    <x-slot name="icon">
                        <x-icons.layers/>
                    </x-slot>

                    <x-slot name="list">
                        <x-layouts.app.sidebar.nav-item
                            :href="route('projects.index')"
                            :active="request()->routeIs('projects.index')">
                            {{ trans_choice('general.project', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item
                            :href="route('required-positions.index')"
                            :active="request()->routeIs('required-positions.index')">
                            {{ trans_choice('general.required-positions' , 1) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item
                            :href="route('centers.index')"
                            :active="request()->routeIs('centers.index')"
                            wire:navigate>
                            {{ trans_choice('general.center', 2) }}
                        </x-layouts.app.sidebar.nav-item>
                    </x-slot>
                </x-layouts.app.sidebar.dropdown-nav-list>

                <x-layouts.app.sidebar.dropdown-nav-list
                    id="employees-management"
                    :title="trans_choice('general.employee_management', 2)"
                    :active="request()->routeIs('employees.*')">

                    <x-slot name="icon">
                        <x-icons.users/>
                    </x-slot>
                    <x-slot name="list">
                        <x-layouts.app.sidebar.nav-item
                            :href="route('employees.index')"
                            :active="request()->routeIs('employees.*')">
                            {{ trans_choice('general.employee', 2) }}
                        </x-layouts.app.sidebar.nav-item>
                    </x-slot>

                </x-layouts.app.sidebar.dropdown-nav-list>
            </ul>
        </div>

        <div>
            <x-layouts.app.sidebar.profile-card :user="$user"/>
        </div>


    </div>


</aside>

