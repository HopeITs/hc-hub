<!-- Sidebar -->
<div class="sidebar fixed top-0 right-0 bottom-0 w-sidebar bg-white border-l border-borderColor" id="sidebar">

    <div class="modern-profile p-3 pb-0 ">
        <!-- Logo -->
        <div class="sidebar-logo  h-[50px] w-sidebar flex justify-center pt-3 px-3">
            <a href="index.html" class="logo logo-normal">
                <img src="assets/img/logo.svg" alt="Logo">
            </a>
            <a href="index.html" class="logo-small hidden">
                <img src="assets/img/logo-small.svg" alt="Logo">
            </a>
            <a href="index.html" class="dark-logo hidden">
                <img src="assets/img/logo-white.svg" alt="Logo">
            </a>
        </div>
        <!-- /Logo -->
        <div class="text-center rounded bg-light p-3  user-profile  flex flex-col items-center">
            <div class="flex justify-center items-center size-20 online mb-3">
                <img src="assets/img/profiles/avatar-02.jpg" alt="Img" class="rounded-full">
            </div>
            <h6 class="text-xs leading-normal font-normal mb-1">{{$this->user->name ?? 'اسمي'}}</h6>
            <p class="fs-10">{{$this->user->myRole() ?? 'دوري'}}</p>
        </div>

    </div>

    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <x-layouts.app.sidebar.menu-title>@lang('general.basic-management')</x-layouts.app.sidebar.menu-title>
                <x-layouts.app.sidebar.dropdown-nav-list :title="__('general.project-basics')"  :active="request()->routeIs('cities.index') || request()->routeIs('contract-types.*') || request()->routeIs('leave-types.*') || request()->routeIs('checklist-items.*')">
                    @if (auth()->check() && auth()->user()->hasRole('Super Admin'))
                    <x-layouts.app.sidebar.nav-item  :href="route('cities.index')" :active="request()->routeIs('cities.index')" wire:navigate>{{ trans_choice('general.city', 2) }}</x-layouts.app.sidebar.nav-item>
                 @endif
                    <x-layouts.app.sidebar.nav-item>{{ trans_choice('general.contract-type', 2) }}</x-layouts.app.sidebar.nav-item>
                    <x-layouts.app.sidebar.nav-item>{{ trans_choice('general.leave-type', 2) }}</x-layouts.app.sidebar.nav-item>
                    <x-layouts.app.sidebar.nav-item>{{ trans_choice('general.checklist-items', 2) }}</x-layouts.app.sidebar.nav-item>
                </x-layouts.app.sidebar.dropdown-nav-list>

                <x-layouts.app.sidebar.single-item>
                    @lang('general.projects-management')
                </x-layouts.app.sidebar.single-item>

            </ul>
        </div>
    </div>
</div>
<!-- /Sidebar -->
