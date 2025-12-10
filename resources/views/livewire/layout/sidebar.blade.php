<div class="relative" x-data="{
        rtl: {{ app()->getLocale() === 'ar' ? 'true' : 'false' }},
        open: @entangle('open').live
    }"
     x-cloak
>
    <aside
        x-data="{
        rtl: {{ app()->getLocale() === 'ar' ? 'true' : 'false' }},
        open: @entangle('open').live
    }"
        x-cloak
        @click.outside="open = false"
        class="fixed top-0 z-40 w-64 h-full pt-4 bg-white
           transition-transform duration-300
           md:translate-x-0"
        :class="{
            // MOBILE ONLY behavior (< md)
            'translate-x-0': open,
            '-translate-x-full': !open && !rtl,
            'translate-x-full': !open && rtl,
            }"
    >
        <div class="flex flex-col h-full justify-between">
            <div class="h-full px-3 pb-4 bg-white overflow-y-auto">
                <a href="{{ route('dashboard') }}" class="flex justify-center items-center mt-4 mb-8">
                    <x-app-logo class="md:w-[11rem] md:h-auto object-fit"/>
                </a>

                <ul class="space-y-2 font-medium">

                    {{-- 1. Employee Dashboard (Seen by all) --}}
                    <x-layouts.app.sidebar.single-item :href="route('dashboard')"
                                                       :active="request()->routeIs('dashboard') || request()->routeIs('employee.dashboard')">
                        <x-slot name="icon">
                            <x-icons.dashboard class="size-4"/>
                        </x-slot>
                        @lang('general.dashboard')
                    </x-layouts.app.sidebar.single-item>

                    {{-- Statistics (HR + super-admin only - kept as per logic) --}}
                    @activerole('HR','super-admin')
                    <x-layouts.app.sidebar.dropdown-nav-list id="statistics" title="{{__('general.statistics')}}"
                                                             :active="request()->routeIs('statistics.general') || request()->routeIs('statistics.contract')">
                        <x-slot name="icon">
                            <x-icons.statistics/>
                        </x-slot>
                        <x-slot name="list">
                            <x-layouts.app.sidebar.nav-item :href="route('statistics.general')"
                                                            :active="request()->routeIs('statistics.general')">
                                {{ __('general.general-statistics') }}
                            </x-layouts.app.sidebar.nav-item>

                            <x-layouts.app.sidebar.nav-item :href="route('statistics.contract')"
                                                            :active="request()->routeIs('statistics.contract')">
                                {{ __('general.contract-statistics') }}
                            </x-layouts.app.sidebar.nav-item>
                        </x-slot>
                    </x-layouts.app.sidebar.dropdown-nav-list>
                    @endactiverole

                    <x-layouts.app.sidebar.single-item :href="route('org.chart')"
                                                       :active="request()->routeIs('org.chart')">
                        <x-slot name="icon">
                            <x-icons.code-fork class="size-5"/>
                        </x-slot>
                        {{ __('general.orgn-chart') }}
                    </x-layouts.app.sidebar.single-item>

                    {{-- 2. Project Basics --}}
                    <x-layouts.app.sidebar.dropdown-nav-list id="basic-program" :title="__('general.project-basics')"
                                                             :active="request()->routeIs('cities.*') ||
                        request()->routeIs('contract-types.*') ||
                        request()->routeIs('leave-types.*') ||
                        request()->routeIs('basic-checklist-items.*') ||
                        request()->routeIs('departments.*') ||
                        request()->routeIs('job-levels.*')">
                        <x-slot name="icon">
                            <x-icons.home/>
                        </x-slot>
                        <x-slot name="list">
                            {{-- Cities (All) --}}
                            <x-layouts.app.sidebar.nav-item :href="route('cities.index')"
                                                            :active="request()->routeIs('cities.index')">
                                {{ trans_choice('general.city', 2) }}
                            </x-layouts.app.sidebar.nav-item>
                            {{-- Contract Types (HR + super-admin) --}}
                            @activerole('HR','super-admin')
                            <x-layouts.app.sidebar.nav-item :href="route('contract-types.index')"
                                                            :active="request()->routeIs('contract-types.index')">
                                {{ trans_choice('general.contract-type', 2) }}
                            </x-layouts.app.sidebar.nav-item>
                            @endactiverole

                            {{-- Leave Types (All) --}}
                            <x-layouts.app.sidebar.nav-item :href="route('leave-types.index')"
                                                            :active="request()->routeIs('leave-types.index')">
                                {{ trans_choice('general.leave-type', 2) }}
                            </x-layouts.app.sidebar.nav-item>{{-- Checklist items (All) --}}
                            <x-layouts.app.sidebar.nav-item :href="route('basic-checklist-items.index')"
                                                            :active="request()->routeIs('basic-checklist-items.index')">
                                {{ trans_choice('general.checklist-items', 2) }}
                            </x-layouts.app.sidebar.nav-item>
                            {{-- Job Levels (HR + super-admin) --}}
                            @activerole('HR', 'super-admin')
                            <x-layouts.app.sidebar.nav-item :href="route('job-levels.index')"
                                                            :active="request()->routeIs('job-levels.index')">
                                {{ trans_choice('general.job-level', 2) }}
                            </x-layouts.app.sidebar.nav-item>
                            @endactiverole

                            {{-- Departments (All) --}}
                            <x-layouts.app.sidebar.nav-item :href="route('departments.index')"
                                                            :active="request()->routeIs('departments.index')"
                                                            wire:navigate>
                                {{ trans_choice('general.department', 2) }}
                            </x-layouts.app.sidebar.nav-item>
                        </x-slot>
                    </x-layouts.app.sidebar.dropdown-nav-list>


                    {{-- 3. Program Management --}}
                    {{-- Dropdown visible to HR & super-admin --}}
                    @activerole('super-admin', 'HR')
                    <x-layouts.app.sidebar.dropdown-nav-list id="program-management"
                                                             :title="__('general.programs-management')" :active="request()->routeIs('programs.*') ||
                        request()->routeIs('positions.*') ||
                        request()->routeIs('centers.*')">

                        <x-slot name="icon">
                            <x-icons.layers/>
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
                    @notactiverole('super-admin', 'HR')
                    <x-layouts.app.sidebar.single-item :href="route('centers.index')"
                                                       :active="request()->routeIs('centers.*')">
                        <x-slot name="icon">
                            <x-icons.layers class="size-5"/>
                        </x-slot>
                        {{ trans_choice('general.center', 2) }}
                    </x-layouts.app.sidebar.single-item>
                    @endnotactiverole

                    {{-- 4. Employee Management (super-admin + HR) --}}
                    @activerole('super-admin', 'HR', 'hr', 'HR-SYR', 'HR-ALP')
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
                    {{-- Visible to: HR, super-admin, AND Managers --}}
                    @activerole('HR', 'hr', 'HR-SYR', 'HR-ALP', 'super-admin', 'manager', 'manager', 'general-manager', 'general manager')
                    <x-layouts.app.sidebar.dropdown-nav-list id="leaves-management"
                                                             title="{{__('general.leaves-management')}}"
                                                             :active="(request()->routeIs('leaves.all') || request()->routeIs('leaves.balance'))">
                        <x-slot name="icon">
                            <x-icons.calendar/>
                        </x-slot>
                        <x-slot name="list">
                            {{-- All Leaves: Managers, HRs, super-admin --}}
                            <x-layouts.app.sidebar.nav-item :href="route('leaves.all')"
                                                            :active="request()->routeIs('leaves.all')">
                                {{ __('general.all-leaves')}}
                            </x-layouts.app.sidebar.nav-item>

                            {{-- Balance: ONLY HR + super-admin (Managers excluded) --}}
                            @activerole('HR', 'hr', 'HR-SYR', 'HR-ALP', 'super-admin')
                            <x-layouts.app.sidebar.nav-item :href="route('leaves.balance')"
                                                            :active="request()->routeIs('leaves.balance')">
                                {{ __('general.balance') }}
                            </x-layouts.app.sidebar.nav-item>
                            @endactiverole
                        </x-slot>
                    </x-layouts.app.sidebar.dropdown-nav-list>
                    @endactiverole

                    {{-- My Leaves: Single Item OUTSIDE for ALL --}}
                    <x-layouts.app.sidebar.single-item :href="route('leaves.index')"
                                                       :active="request()->routeIs('leaves.index')">
                        <x-slot name="icon">
                            <x-icons.my-leaves/>

                        </x-slot>
                        {{ __('general.my-leaves') }}
                    </x-layouts.app.sidebar.single-item>

                    {{-- 6. Archived Files (All) --}}
                    <x-layouts.app.sidebar.dropdown-nav-list id="archived-files" :title="__('general.archived-files')"
                                                             :active="request()->routeIs('my-folders.*') || request()->routeIs('shared-folders.*')">
                        <x-slot name="icon">
                            <x-icons.archived/>
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
                    <x-layouts.app.sidebar.single-item :href="route('checklist-items.index')"
                                                       :active="request()->routeIs('checklist-items.index')">
                        <x-slot name="icon">
                            <x-icons.clipboard-list class="size-4"/>
                        </x-slot>
                        @lang('general.my-items')
                    </x-layouts.app.sidebar.single-item>

                    {{-- 8. News Management (super-admin, Managers, HRs) --}}
                    @activerole('super-admin', 'general-manager', 'general manager', 'manager', 'manager', 'HR', 'hr', 'HR-SYR', 'HR-ALP')
                    <x-layouts.app.sidebar.single-item :href="route('news.index')"
                                                       :active="request()->routeIs('news.*')">
                        <x-slot name="icon">
                            <x-icons.newspaper class="size-4"/>
                        </x-slot>
                        {{ __('general.news-management') }}
                    </x-layouts.app.sidebar.single-item>
                    @endactiverole


                    @activerole('super-admin', 'HR', 'HR-SYR', 'HR-ALP')
                    <x-layouts.app.sidebar.single-item :href="route('badges.index')"
                                                       :active="request()->routeIs('badges.*')">
                        <x-slot name="icon" class="text-secondary-light">
                            <x-icons.badges class="size-4"/>
                        </x-slot>
                        {{ __('general.all-badges') }}
                    </x-layouts.app.sidebar.single-item>
                    @endactiverole


                </ul>
            </div>
        </div>
    </aside>
    <div
        x-show="open"
        x-transition.opacity
        @click="open = false"
        class="fixed z-30 inset-0 bg-secondary-light/50 md:hidden"
        x-cloak
    ></div>
</div>
