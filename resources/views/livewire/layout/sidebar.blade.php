<aside id="cta-button-sidebar"
    class="fixed top-0 left-0 rtl:right-0 z-40 w-64 h-screen pt-4 transition-transform sm:translate-x-0 translate-x-full bg-white border-l border-gray-200 shadow-xl"
    aria-label="Sidebar">
    <div class="flex flex-col h-full justify-between">
        <div class="h-full px-3 pb-4 bg-white overflow-y-auto">
            <a href="{{ route('home') }}" class="flex justify-center items-center mt-4 mb-8">
                <x-app-logo class="md:w-[11rem] md:h-auto object-fit" />
            </a>

            <ul class="space-y-2 font-medium">

                @activerole('SUPER ADMIN')
                <x-layouts.app.sidebar.dropdown-nav-list id="dashboards" :title="__('general.dashboards')"
                                                         >
                    <x-slot name="icon"><x-icons.users /></x-slot>
                    <x-slot name="list">
                        <x-layouts.app.sidebar.nav-item
                            :href="route('employee.dashboard')"
                            :active="request()->routeIs('employee.dashboard')">
                            {{ __('general.employee-dashboard') }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item
                            :href="route('hr.dashboard')"
                            :active="request()->routeIs('hr.dashboard')">
                            {{ __('general.hr-dashboard') }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item
                            :href="route('manager.dashboard')"
                            :active="request()->routeIs('manager.dashboard')">
                            {{ __('general.manager-dashboard') }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item
                            :href="route('general-admin.dashboard')"
                            :active="request()->routeIs('general-admin.dashboard')">
                            {{ __('general.general-admin-dashboard') }}
                        </x-layouts.app.sidebar.nav-item>
                    </x-slot>
                </x-layouts.app.sidebar.dropdown-nav-list>
                @endactiverole

                @activerole('hr', 'general manager', 'SUPER ADMIN')
                    <x-layouts.app.sidebar.dropdown-nav-list id="basic-program" :title="__('general.project-basics')" :active="request()->routeIs('dashboard') ||
                        request()->routeIs('cities.*') ||
                        request()->routeIs('contract-types.*') ||
                        request()->routeIs('leave-types.*') ||
                        request()->routeIs('basic-checklist-items.*') ||
                        request()->routeIs('departments.*') ||
                        request()->routeIs('job-levels.*')">
                        <x-slot name="icon"><x-icons.home /></x-slot>
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
                            <x-layouts.app.sidebar.nav-item :href="route('basic-checklist-items.index')" :active="request()->routeIs('basic-checklist-items.index')">
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
                @endactiverole

                @activerole('general manager', 'hr', 'SUPER ADMIN')
                    <x-layouts.app.sidebar.dropdown-nav-list id="program-management" :title="__('general.programs-management')" :active="request()->routeIs('programs.*') ||
                        request()->routeIs('positions.*') ||
                        request()->routeIs('org.chart') ||
                        request()->routeIs('centers.*')">

                        <x-slot name="icon">
                            <x-icons.layers class="w-5 h-5" />
                        </x-slot>

                        <x-slot name="list">
                            <x-layouts.app.sidebar.nav-item :href="route('programs.index')" :active="request()->routeIs('programs.*')">
                                {{ trans_choice('general.program', 2) }}
                            </x-layouts.app.sidebar.nav-item>

                            <x-layouts.app.sidebar.nav-item :href="route('positions.index')" :active="request()->routeIs('positions.*')">
                                {{ __('general.positions') }}
                            </x-layouts.app.sidebar.nav-item>
                            <x-layouts.app.sidebar.nav-item :href="route('org.chart')" :active="request()->routeIs('org.chart')">
                                {{ __('general.orgn-chart') }}
                            </x-layouts.app.sidebar.nav-item>

                            <x-layouts.app.sidebar.nav-item :href="route('centers.index')" :active="request()->routeIs('centers.index')" wire:navigate>
                                {{ trans_choice('general.center', 2) }}
                            </x-layouts.app.sidebar.nav-item>
                        </x-slot>
                    </x-layouts.app.sidebar.dropdown-nav-list>
                @endactiverole

                @activerole('manager', 'hr', 'general manager', 'SUPER ADMIN')
                    <x-layouts.app.sidebar.dropdown-nav-list id="employees-management" :title="trans_choice('general.employee-management', 2)"
                        :active="request()->routeIs('employees.*') ||
                            request()->routeIs('contracts.*') ||
                            request()->routeIs('default-deliverables.*')">
                        <x-slot name="icon"><x-icons.users /></x-slot>
                        <x-slot name="list">
                            <x-layouts.app.sidebar.nav-item :href="route('employees.index')" :active="request()->routeIs('employees.*')">
                                {{ trans_choice('general.employee', 2) }}
                            </x-layouts.app.sidebar.nav-item>
                            @activerole('hr', 'SUPER ADMIN')
                                <x-layouts.app.sidebar.nav-item :href="route('default-deliverables.index')" :active="request()->routeIs('default-deliverables.index')">
                                    {{ trans_choice('general.default-deliverables', 2) }}
                                </x-layouts.app.sidebar.nav-item>
                            @endactiverole
                            <x-layouts.app.sidebar.nav-item :href="route('contracts.index')" :active="request()->routeIs('contracts.*')">
                                {{ trans_choice('general.contracts', 2) }}
                            </x-layouts.app.sidebar.nav-item>
                        </x-slot>
                    </x-layouts.app.sidebar.dropdown-nav-list>
                @endactiverole

                <x-layouts.app.sidebar.dropdown-nav-list id="holidays-management" :title="__('general.holidays-and-leaves')" :active="request()->routeIs('leaves.*')">
                    <x-slot name="icon">
                        <x-icons.calendar class="w-5 h-5" />
                    </x-slot>
                    <x-slot name="list">
                        <x-layouts.app.sidebar.nav-item :href="route('leaves.index')" :active="request()->routeIs('leaves.index')">
                            {{ __('general.my-leaves') }}
                        </x-layouts.app.sidebar.nav-item>

                    </x-slot>
                </x-layouts.app.sidebar.dropdown-nav-list>

                @activerole('employee', 'hr', 'manager', 'general manager', 'SUPER ADMIN')
                    <x-layouts.app.sidebar.dropdown-nav-list id="archived-files" :title="__('general.archived-files')" :active="request()->routeIs('my-folders.*') || request()->routeIs('shared-folders.*')">
                        <x-slot name="icon"><x-icons.folder class="w-5 h-5" /></x-slot>
                        <x-slot name="list">
                            <x-layouts.app.sidebar.nav-item :href="route('my-folders.index')" :active="request()->routeIs('my-folders.*')">
                                {{ __('general.my-folders') }}
                            </x-layouts.app.sidebar.nav-item>
                            <x-layouts.app.sidebar.nav-item :href="route('shared-folders.index')" :active="request()->routeIs('shared-folders.*')">
                                {{ __('general.shared-folders') }}
                            </x-layouts.app.sidebar.nav-item>
                        </x-slot>
                    </x-layouts.app.sidebar.dropdown-nav-list>
                @endactiverole

                @activerole('SUPER ADMIN', 'hr', 'General Manager', 'manager', 'Manager', 'Employee')
                    <div wire:ignore x-data="{ open: {{ request()->routeIs('checklist-items.*') ? 'true' : 'false' }} }">
                        <x-layouts.app.sidebar.dropdown-nav-list
                            id="checklist-items"
                            :title="__('general.checklist_items')"
                            :active="request()->routeIs('checklist-items.*')"
                            wire:navigate.hover
                        >
                            <x-slot name="icon"><x-icons.clipboard-list class="w-5 h-5" /></x-slot>
                            <x-slot name="list">
                                @if ($isDeliverResponsible)
                                    <x-layouts.app.sidebar.nav-item :href="route('checklist-items.pending-delivers')" :active="request()->routeIs('checklist-items.pending-delivers')">
                                        {{ __('general.pending-delivers') }}
                                        @livewire('checklist-items.pending-delivers-counter')
                                    </x-layouts.app.sidebar.nav-item>

                                    <x-layouts.app.sidebar.nav-item :href="route('checklist-items.pending-returns')" :active="request()->routeIs('checklist-items.pending-returns')">
                                        {{ __('general.pending_returns') }}
                                        @livewire('checklist-items.pending-returns-counter')
                                    </x-layouts.app.sidebar.nav-item>

                                    <x-layouts.app.sidebar.nav-item :href="route('checklist-items.done-requests')" :active="request()->routeIs('checklist-items.done-requests')">
                                        {{ __('general.done_requests') }}
                                    </x-layouts.app.sidebar.nav-item>
                                @endif

                                <x-layouts.app.sidebar.nav-item :href="route('checklist-items.index')" :active="request()->routeIs('checklist-items.index')">
                                    {{ __('general.my-items') }}
                                </x-layouts.app.sidebar.nav-item>
                            </x-slot>
                        </x-layouts.app.sidebar.dropdown-nav-list>
                    </div>
                @endactiverole

            </ul>
        </div>

        <div class="px-3 pb-4">
            <x-layouts.app.sidebar.profile-card :user="$user" />
        </div>
    </div>
</aside>
