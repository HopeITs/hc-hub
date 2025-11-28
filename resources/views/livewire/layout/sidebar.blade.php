<aside id="cta-button-sidebar"
       class="fixed top-0 left-0 rtl:right-0 z-40 w-64 h-screen pt-4 transition-transform sm:translate-x-0 translate-x-full bg-white border-l border-gray-200"
       aria-label="Sidebar">
    <div class="flex flex-col h-full justify-between">
        <div class="h-full px-3 pb-4 bg-white overflow-y-auto">
            <a href="{{ route('dashboard') }}" class="flex justify-center items-center mt-4 mb-8">
                <x-app-logo class="md:w-[11rem] md:h-auto object-fit"/>
            </a>

            <ul class="space-y-2 font-medium">

                {{-- 1. Employee Dashboard (Seen by all) --}}
                <x-layouts.app.sidebar.single-item :href="route('employee.dashboard')" :active="request()->routeIs('dashboard') || request()->routeIs('employee.dashboard')">
                    <x-slot name="icon"><x-icons.dashboard class="w-5 h-5" /></x-slot>
                    Dashboard
                </x-layouts.app.sidebar.single-item>

                {{-- Statistics (HR + Super Admin only - kept as per logic) --}}
                @activerole('HR', 'hr', 'HR-SYR', 'HR-ALP', 'SUPER ADMIN')
                <x-layouts.app.sidebar.dropdown-nav-list
                    id="statistics"
                    title="Statistics"
                    :active="request()->routeIs('general-statistics') ||
                             request()->routeIs('contract-statistics')">
                    <x-slot name="icon">
                        <x-icons.statistics/>
                    </x-slot>
                    <x-slot name="list">
                        <x-layouts.app.sidebar.nav-item
                            :href="route('general-statistics')"
                            :active="request()->routeIs('general-statistics')">
                            {{ __('general.general-statistics') }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item
                            :href="route('contract-statistics')"
                            :active="request()->routeIs('contract-statistics')">
                            {{ __('general.contract-statistics') }}
                        </x-layouts.app.sidebar.nav-item>
                    </x-slot>
                </x-layouts.app.sidebar.dropdown-nav-list>
                @endactiverole

                <x-layouts.app.sidebar.single-item :href="route('org.chart')" :active="request()->routeIs('org.chart')">
                    <x-slot name="icon"><x-icons.code-fork class="w-5 h-5" /></x-slot>
                    {{ __('general.orgn-chart') }}
                </x-layouts.app.sidebar.single-item>

                {{-- 2. Project Basics --}}
                <x-layouts.app.sidebar.dropdown-nav-list id="basic-program" :title="__('general.project-basics')"
                                                         :active="
                        request()->routeIs('cities.*') ||
                        request()->routeIs('contract-types.*') ||
                        request()->routeIs('leave-types.*') ||
                        request()->routeIs('basic-checklist-items.*') ||
                        request()->routeIs('departments.*') ||
                        request()->routeIs('job-levels.*')
                        ">
                    <x-slot name="icon">
                        <x-icons.home/>
                    </x-slot>
                    <x-slot name="list">
                        {{-- Cities (All) --}}
                        <x-layouts.app.sidebar.nav-item :href="route('cities.index')"
                                                        :active="request()->routeIs('cities.index')">
                            {{ trans_choice('general.city', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        {{-- Contract Types (HR + Super Admin) --}}
                        @activerole('HR', 'hr', 'HR-SYR', 'HR-ALP', 'SUPER ADMIN')
                        <x-layouts.app.sidebar.nav-item :href="route('contract-types.index')"
                                                        :active="request()->routeIs('contract-types.index')">
                            {{ trans_choice('general.contract-type', 2) }}
                        </x-layouts.app.sidebar.nav-item>
                        @endactiverole

                        {{-- Leave Types (All) --}}
                        <x-layouts.app.sidebar.nav-item :href="route('leave-types.index')"
                                                        :active="request()->routeIs('leave-types.index')">
                            {{ trans_choice('general.leave-type', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        {{-- Checklist items (All) --}}
                        <x-layouts.app.sidebar.nav-item :href="route('basic-checklist-items.index')"
                                                        :active="request()->routeIs('basic-checklist-items.index')">
                            {{ trans_choice('general.checklist-items', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        {{-- Job Levels (HR + Super Admin) --}}
                        @activerole('HR', 'hr', 'HR-SYR', 'HR-ALP', 'SUPER ADMIN')
                        <x-layouts.app.sidebar.nav-item :href="route('job-levels.index')"
                                                        :active="request()->routeIs('job-levels.index')">
                            {{ trans_choice('general.job-level', 2) }}
                        </x-layouts.app.sidebar.nav-item>
                        @endactiverole

                        {{-- Departments (All) --}}
                        <x-layouts.app.sidebar.nav-item :href="route('departments.index')"
                                                        :active="request()->routeIs('departments.index')" wire:navigate>
                            {{ trans_choice('general.department', 2) }}
                        </x-layouts.app.sidebar.nav-item>
                    </x-slot>
                </x-layouts.app.sidebar.dropdown-nav-list>

                {{-- 3. Program Management --}}
                {{-- Dropdown visible to HR & Super Admin --}}
                @activerole('SUPER ADMIN', 'HR', 'hr', 'HR-SYR', 'HR-ALP')
                <x-layouts.app.sidebar.dropdown-nav-list id="program-management"
                                                         :title="__('general.programs-management')" :active="request()->routeIs('programs.*') ||
                        request()->routeIs('positions.*') ||
                        request()->routeIs('centers.*')">
                    <x-slot name="icon">
                        <x-icons.layers class="w-5 h-5"/>
                    </x-slot>

                    <x-slot name="list">
                        <x-layouts.app.sidebar.nav-item :href="route('programs.index')"
                                                        :active="request()->routeIs('programs.*')">
                            {{ trans_choice('general.program', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item :href="route('positions.index')"
                                                        :active="request()->routeIs('positions.*')">
                            {{ __('general.positions') }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item :href="route('centers.index')"
                                                        :active="request()->routeIs('centers.*')" wire:navigate>
                            {{ trans_choice('general.center', 2) }}
                        </x-layouts.app.sidebar.nav-item>
                    </x-slot>
                </x-layouts.app.sidebar.dropdown-nav-list>
                @endactiverole

                {{-- Centers for everyone else (Single item) --}}
                @notactiverole('SUPER ADMIN', 'HR', 'hr', 'HR-SYR', 'HR-ALP')
                <x-layouts.app.sidebar.single-item :href="route('centers.index')" :active="request()->routeIs('centers.*')">
                    <x-slot name="icon"><x-icons.layers class="w-5 h-5" /></x-slot>
                    {{ trans_choice('general.center', 2) }}
                </x-layouts.app.sidebar.single-item>
                @endnotactiverole

                {{-- 4. Employee Management (Super Admin + HR) --}}
                @activerole('SUPER ADMIN', 'HR', 'hr', 'HR-SYR', 'HR-ALP')
                <x-layouts.app.sidebar.dropdown-nav-list id="employees-management"
                                                         :title="trans_choice('general.employee-management', 2)"
                                                         :active="request()->routeIs('employees.*') ||
                            request()->routeIs('contracts.*') ||
                            request()->routeIs('default-deliverables.*')">
                    <x-slot name="icon">
                        <x-icons.users/>
                    </x-slot>
                    <x-slot name="list">
                        <x-layouts.app.sidebar.nav-item :href="route('employees.index')"
                                                        :active="request()->routeIs('employees.*')">
                            {{ trans_choice('general.employee', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item :href="route('default-deliverables.index')"
                                                        :active="request()->routeIs('default-deliverables.index')">
                            {{ trans_choice('general.default-deliverables', 2) }}
                        </x-layouts.app.sidebar.nav-item>

                        <x-layouts.app.sidebar.nav-item :href="route('contracts.index')"
                                                        :active="request()->routeIs('contracts.*')">
                            {{ trans_choice('general.contracts', 2) }}
                        </x-layouts.app.sidebar.nav-item>
                    </x-slot>
                </x-layouts.app.sidebar.dropdown-nav-list>
                @endactiverole

                {{-- 5. Leave Management (Holidays and leaves) --}}
                {{-- Visible to: HR, Super Admin, AND Managers --}}
                @activerole('HR', 'hr', 'HR-SYR', 'HR-ALP', 'SUPER ADMIN', 'Manager', 'manager', 'General Manager', 'general manager')
                <x-layouts.app.sidebar.dropdown-nav-list id="leaves-management"
                                                         title="Leaves Management"
                                                         :active="(request()->routeIs('leaves.all') || request()->routeIs('leaves.balance'))">
                    <x-slot name="icon">
                        <x-icons.calendar class="w-5 text-gray-400 h-5"/>
                    </x-slot>
                    <x-slot name="list">
                        {{-- All Leaves: Managers, HRs, Super Admin --}}
                        <x-layouts.app.sidebar.nav-item :href="route('leaves.all')"
                                                        :active="request()->routeIs('leaves.all')">
                            {{ __('general.all-leaves')}}
                        </x-layouts.app.sidebar.nav-item>

                        {{-- Balance: ONLY HR + Super Admin (Managers excluded) --}}
                        @activerole('HR', 'hr', 'HR-SYR', 'HR-ALP', 'SUPER ADMIN')
                        <x-layouts.app.sidebar.nav-item :href="route('leaves.balance')"
                                                        :active="request()->routeIs('leaves.balance')">
                            {{ __('general.balance') }}
                        </x-layouts.app.sidebar.nav-item>
                        @endactiverole
                    </x-slot>
                </x-layouts.app.sidebar.dropdown-nav-list>
                @endactiverole

                {{-- My Leaves: Single Item OUTSIDE for ALL --}}
                <x-layouts.app.sidebar.single-item :href="route('leaves.index')" :active="request()->routeIs('leaves.index')">
                    <x-slot name="icon"><x-icons.calendar class="w-5 h-5" /></x-slot>
                    {{ __('general.my-leaves') }}
                </x-layouts.app.sidebar.single-item>

                {{-- 6. Archived Files (All) --}}
                <x-layouts.app.sidebar.dropdown-nav-list id="archived-files" :title="__('general.archived-files')"
                                                         :active="request()->routeIs('my-folders.*') || request()->routeIs('shared-folders.*')">
                    <x-slot name="icon">
                        <x-icons.folder class="w-5 h-5"/>
                    </x-slot>
                    <x-slot name="list">
                        <x-layouts.app.sidebar.nav-item :href="route('my-folders.index')"
                                                        :active="request()->routeIs('my-folders.*')">
                            {{ __('general.my-folders') }}
                        </x-layouts.app.sidebar.nav-item>
                        <x-layouts.app.sidebar.nav-item :href="route('shared-folders.index')"
                                                        :active="request()->routeIs('shared-folders.*')">
                            {{ __('general.shared-folders') }}
                        </x-layouts.app.sidebar.nav-item>
                    </x-slot>
                </x-layouts.app.sidebar.dropdown-nav-list>

                {{-- 7. Checklist Items -> My items (All, Single) --}}
                <x-layouts.app.sidebar.single-item :href="route('checklist-items.index')" :active="request()->routeIs('checklist-items.index')">
                    <x-slot name="icon"><x-icons.clipboard-list class="w-5 h-5" /></x-slot>
                    My items
                </x-layouts.app.sidebar.single-item>

                {{-- 8. News Management (Super Admin, Managers, HRs) --}}
                @activerole('SUPER ADMIN', 'General Manager', 'general manager', 'Manager', 'manager', 'HR', 'hr', 'HR-SYR', 'HR-ALP')
                <x-layouts.app.sidebar.single-item :href="route('news.index')" :active="request()->routeIs('news.*')">
                    <x-slot name="icon"><x-icons.newspaper class="w-5 h-5" /></x-slot>
                    {{ __('general.news-management') }}
                </x-layouts.app.sidebar.single-item>
                @endactiverole

            </ul>
        </div>
    </div>
</aside>
