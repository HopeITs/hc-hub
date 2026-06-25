<div class="relative" x-data="{
    rtl: {{ app()->getLocale() === 'ar' ? 'true' : 'false' }},
    open: @entangle('open').live
}" x-cloak>
    <aside x-cloak @click.outside="open = false"
        class="fixed top-0 z-40 h-full pt-4 bg-white shadow-[4px_0_20px_-18px_rgba(0,0,0,0.35)] transition-[width,transform] duration-300 md:translate-x-0"
        :class="[
            sidebarCollapsed ? 'md:w-20' : 'md:w-64',
            open ? 'w-64 translate-x-0' : 'w-64',
            !open && !rtl ? '-translate-x-full' : '',
            !open && rtl ? 'translate-x-full' : '',
        ]"
    >
        <button type="button"
                x-on:click="$dispatch('sidebar-collapse-toggle')"
                class="absolute z-50 items-center justify-center hidden transition bg-white border border-gray-200 rounded-full shadow-md top-7 size-8 text-secondary-medium hover:border-secondary-light hover:bg-secondary-soft hover:text-primary md:flex"
                :class="rtl ? '-left-4' : '-right-4'"
                :title="sidebarCollapsed ? @js(__('general.expand-sidebar')) : @js(__('general.collapse-sidebar'))">
            <svg class="transition-transform duration-300 size-4"
                 :class="sidebarCollapsed ? (rtl ? 'rotate-0' : 'rotate-180') : (rtl ? 'rotate-180' : 'rotate-0')"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/>
            </svg>
        </button>

        <div class="flex flex-col justify-between h-full">
            <div class="h-full px-3 pb-4 overflow-x-hidden overflow-y-auto bg-white">
                <a href="{{ route('dashboard') }}" class="flex items-center justify-center h-16 mb-4">
                    <x-app-logo class="w-[11rem] h-auto object-fit" x-show="!sidebarCollapsed || open" />
                    <img src="{{ asset('assets/img/hope-center-icon.svg') }}"
                         alt="{{ config('app.name') }}"
                         x-show="sidebarCollapsed && !open"
                         class="hidden object-contain size-8 md:block" />
                </a>

                <ul class="space-y-2 font-medium" :class="sidebarCollapsed && !open ? 'md:flex md:flex-col md:items-center' : ''">

                    {{-- 1. Employee Dashboard (Seen by all) --}}
                    <x-layouts.app.sidebar.single-item :href="route('dashboard')" :active="request()->routeIs('dashboard') || request()->routeIs('employee.dashboard')">
                        <x-slot name="icon">
                            <x-icons.dashboard class="size-4" />
                        </x-slot>
                        @lang('general.dashboard')
                    </x-layouts.app.sidebar.single-item>

                    {{-- Statistics (HR + super-admin only - kept as per logic) --}}
                    @activerole('HR', 'super-admin')
                        <x-layouts.app.sidebar.dropdown-nav-list id="statistics" title="{{ __('general.statistics') }}"
                            :active="request()->routeIs('statistics.general') ||
                                request()->routeIs('statistics.contract')">
                            <x-slot name="icon">
                                <x-icons.statistics />
                            </x-slot>
                            <x-slot name="list">

                                <x-layouts.app.sidebar.nav-item :href="route('statistics.general')" :active="request()->routeIs('statistics.general')">
                                    {{ __('general.general-statistics') }}
                                </x-layouts.app.sidebar.nav-item>

                                <x-layouts.app.sidebar.nav-item :href="route('statistics.contract')" :active="request()->routeIs('statistics.contract')">
                                    {{ __('general.contract-statistics') }}
                                </x-layouts.app.sidebar.nav-item>
                            </x-slot>
                        </x-layouts.app.sidebar.dropdown-nav-list>
                    @endactiverole

                    <x-layouts.app.sidebar.single-item :href="route('org.chart')" :active="request()->routeIs('org.chart')">
                        <x-slot name="icon">
                            <x-icons.code-fork class="size-5" />
                        </x-slot>
                        {{ __('general.orgn-chart') }}
                    </x-layouts.app.sidebar.single-item>

                    {{-- 2. Project Basics --}}
                    <x-layouts.app.sidebar.dropdown-nav-list id="basic-program" :title="__('general.project-basics')" :active="request()->routeIs('cities.*') ||
                        request()->routeIs('contract-types.*') ||
                        request()->routeIs('leave-types.*') ||
                        request()->routeIs('annual-leave-rules.*') ||
                        request()->routeIs('basic-checklist-items.*') ||
                        request()->routeIs('departments.*') ||
                        request()->routeIs('course-categories.*') ||
                        request()->routeIs('job-levels.*')  ||   request()->routeIs('termination-reasons.*')  "
                        >
                        <x-slot name="icon">
                            <x-icons.home />
                        </x-slot>
                        <x-slot name="list">
                            {{-- Cities (All) --}}
                            <x-layouts.app.sidebar.nav-item :href="route('cities.index')" :active="request()->routeIs('cities.index')">
                                {{ trans_choice('general.city', 2) }}
                            </x-layouts.app.sidebar.nav-item>
                            {{-- Contract Types (HR + super-admin) --}}
                            @activerole('HR', 'super-admin')
                                <x-layouts.app.sidebar.nav-item :href="route('contract-types.index')" :active="request()->routeIs('contract-types.index')">
                                    {{ trans_choice('general.contract-type', 2) }}
                                </x-layouts.app.sidebar.nav-item>
                            @endactiverole

                            {{-- Leave Types (All) --}}
                            <x-layouts.app.sidebar.nav-item :href="route('leave-types.index')" :active="request()->routeIs('leave-types.index')">
                                {{ trans_choice('general.leave-type', 2) }}
                            </x-layouts.app.sidebar.nav-item>
                            @activerole('HR', 'super-admin')
                                <x-layouts.app.sidebar.nav-item :href="route('annual-leave-rules.index')" :active="request()->routeIs('annual-leave-rules.*')" wire:navigate>
                                    {{ __('general.annual_leave_rules') }}
                                </x-layouts.app.sidebar.nav-item>
                                   <x-layouts.app.sidebar.nav-item :href="route('termination-reasons.index')" :active="request()->routeIs('termination-reasons.*')" wire:navigate>
                                    {{ __('general.termination_reasons') }}
                                </x-layouts.app.sidebar.nav-item>
                            @endactiverole

                            {{-- Checklist items (All) --}}
                            <x-layouts.app.sidebar.nav-item :href="route('basic-checklist-items.index')" :active="request()->routeIs('basic-checklist-items.index')">
                                {{ trans_choice('general.checklist-items', 2) }}
                            </x-layouts.app.sidebar.nav-item>
                            {{-- Job Levels (HR + super-admin) --}}
                            @activerole('HR', 'super-admin')
                                <x-layouts.app.sidebar.nav-item :href="route('job-levels.index')" :active="request()->routeIs('job-levels.index')">
                                    {{ trans_choice('general.job-level', 2) }}
                                </x-layouts.app.sidebar.nav-item>
                            @endactiverole

                            {{-- Departments (All) --}}
                            <x-layouts.app.sidebar.nav-item :href="route('departments.index')" :active="request()->routeIs('departments.index')" wire:navigate>
                                {{ trans_choice('general.department', 2) }}
                            </x-layouts.app.sidebar.nav-item>
                            <x-layouts.app.sidebar.nav-item :href="route('course-categories.index')" :active="request()->routeIs('course-categories.index')" wire:navigate>
                                {{ trans_choice('general.course-category', 2) }}
                            </x-layouts.app.sidebar.nav-item>
                        </x-slot>
                    </x-layouts.app.sidebar.dropdown-nav-list>


                    {{-- 3. Program Management --}}
                    {{-- Dropdown visible to HR & super-admin --}}
                    @activerole('super-admin', 'HR', 'manager', 'general-manager')
                        <x-layouts.app.sidebar.dropdown-nav-list id="program-management" :title="__('general.programs-management')"
                            :active="request()->routeIs('programs.*') ||
                                request()->routeIs('positions.*') ||
                                request()->routeIs('centers.*')">

                            <x-slot name="icon">
                                <x-icons.layers />
                            </x-slot>

                            <x-slot name="list">
                                <x-layouts.app.sidebar.nav-item :href="route('programs.index')" :active="request()->routeIs('programs.*')">
                                    {{ trans_choice('general.program', 2) }}
                                </x-layouts.app.sidebar.nav-item>
                                @activerole('super-admin', 'HR')
                                    <x-layouts.app.sidebar.nav-item :href="route('positions.index')" :active="request()->routeIs('positions.*')">
                                        {{ __('general.positions') }}
                                    </x-layouts.app.sidebar.nav-item>
                                @endactiverole

                                <x-layouts.app.sidebar.nav-item :href="route('centers.index')" :active="request()->routeIs('centers.*')" wire:navigate>
                                    {{ trans_choice('general.center', 2) }}
                                </x-layouts.app.sidebar.nav-item>
                            </x-slot>
                        </x-layouts.app.sidebar.dropdown-nav-list>
                    @endactiverole

                    {{-- Centers for everyone else (Single item) --}}
                    @notactiverole('super-admin', 'HR')
                        <x-layouts.app.sidebar.single-item :href="route('centers.index')" :active="request()->routeIs('centers.*')">
                            <x-slot name="icon">
                                <x-icons.layers class="size-5" />
                            </x-slot>
                            {{ trans_choice('general.center', 2) }}
                        </x-layouts.app.sidebar.single-item>
                    @endnotactiverole

                    {{-- 4. Employee Management (super-admin + HR) --}}
                    @activerole('super-admin', 'HR', 'general-manager')
                        <x-layouts.app.sidebar.dropdown-nav-list id="employees-management" :title="trans_choice('general.employee-management', 2)"
                            :active="request()->routeIs('employees.*') ||
                                request()->routeIs('contracts.*') ||
                                request()->routeIs('default-deliverables.*')">
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
                    @endactiverole

                    {{-- 5. Leave Management (Holidays and leaves) --}}
                    {{-- Visible to: HR, super-admin, AND Managers --}}
                    @activerole('HR', 'super-admin', 'manager', 'general-manager')
                        <x-layouts.app.sidebar.dropdown-nav-list id="leaves-management"
                            title="{{ __('general.leaves-management') }}" :active="request()->routeIs('leaves.all') || request()->routeIs('leaves.balance')">
                            <x-slot name="icon">
                                <x-icons.calendar />
                            </x-slot>
                            <x-slot name="list">
                                {{-- All Leaves: Managers, HRs, super-admin --}}
                                <x-layouts.app.sidebar.nav-item :href="route('leaves.all')" :active="request()->routeIs('leaves.all')">
                                    {{ __('general.all-leaves') }}
                                </x-layouts.app.sidebar.nav-item>

                                {{-- Balance: ONLY HR + super-admin (Managers excluded) --}}
                                @activerole('HR', 'super-admin')
                                    <x-layouts.app.sidebar.nav-item :href="route('leaves.balance')" :active="request()->routeIs('leaves.balance')">
                                        {{ __('general.balance') }}
                                    </x-layouts.app.sidebar.nav-item>
                                @endactiverole
                            </x-slot>
                        </x-layouts.app.sidebar.dropdown-nav-list>
                    @endactiverole

                    {{-- My Leaves: Single Item OUTSIDE for ALL --}}
                    <x-layouts.app.sidebar.single-item :href="route('leaves.index')" :active="request()->routeIs('leaves.index')">
                        <x-slot name="icon">
                            <x-icons.my-leaves />

                        </x-slot>
                        {{ __('general.my-leaves') }}
                    </x-layouts.app.sidebar.single-item>

                    {{-- 6. Archived Files (All) --}}
                    <x-layouts.app.sidebar.dropdown-nav-list id="archived-files" :title="__('general.archived-files')" :active="request()->routeIs('my-folders.*') || request()->routeIs('shared-folders.*')">
                        <x-slot name="icon">
                            <x-icons.archived />
                        </x-slot>
                        <x-slot name="list">


                            <x-layouts.app.sidebar.nav-item :href="route('my-folders.index')" :active="request()->routeIs('my-folders.*')">
                                {{ __('general.my-folders') }}
                            </x-layouts.app.sidebar.nav-item>


                            <x-layouts.app.sidebar.nav-item :href="route('shared-folders.index')" :active="request()->routeIs('shared-folders.*')">
                                {{ __('general.shared-folders') }}
                            </x-layouts.app.sidebar.nav-item>
                        </x-slot>
                    </x-layouts.app.sidebar.dropdown-nav-list>


                    @if ($isDeliverResponsible)
                        <x-layouts.app.sidebar.dropdown-nav-list id="checklist-items" :title="__('general.checklist_items')"
                            :active="request()->routeIs('checklist-items.*')">
                            <x-slot name="icon">
                                <x-icons.clipboard-list class="size-4" />
                            </x-slot>
                            <x-slot name="list">

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


                            </x-slot>
                        </x-layouts.app.sidebar.dropdown-nav-list>
                    @endif



                    {{-- 7. Checklist Items -> My items (All, Single) --}}
                    <x-layouts.app.sidebar.single-item :href="route('checklist-items.index')" :active="request()->routeIs('checklist-items.index')">
                        <x-slot name="icon">
                            <x-icons.clipboard-list class="size-4" />
                        </x-slot>
                        @lang('general.my-items')
                    </x-layouts.app.sidebar.single-item>

                    {{-- 8. News Management (super-admin, Managers, HRs) --}}
                    @activerole('HR', 'super-admin', 'general-manager', 'manager')
                        <x-layouts.app.sidebar.single-item :href="route('news.index')" :active="request()->routeIs('news.*')">
                            <x-slot name="icon">
                                <x-icons.newspaper class="size-4" />
                            </x-slot>
                            {{ __('general.news-management') }}
                        </x-layouts.app.sidebar.single-item>
                    @endactiverole


                    @activerole('super-admin', 'HR')
                        <x-layouts.app.sidebar.single-item :href="route('badges.index')" :active="request()->routeIs('badges.*')">
                            <x-slot name="icon" class="text-secondary-light">
                                <x-icons.badges class="size-4" />
                            </x-slot>
                            {{ __('general.all-badges') }}
                        </x-layouts.app.sidebar.single-item>
                    @endactiverole

                    @activerole('super-admin')
                        <x-layouts.app.sidebar.single-item :href="route('activity-log.index')" :active="request()->routeIs('activity-log.*')">
                            <x-slot name="icon">
                                <x-icons.activity-log class="size-4" />
                            </x-slot>
                            {{ __('general.activity-log') }}
                        </x-layouts.app.sidebar.single-item>
                    @endactiverole

                </ul>
            </div>
        </div>
    </aside>
    <div x-show="open" x-transition.opacity @click="open = false"
        class="fixed inset-0 z-30 bg-secondary-light/50 md:hidden" x-cloak></div>
</div>
