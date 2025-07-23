<!-- Sidebar -->
<div class="sidebar fixed top-0 right-0 bottom-0 w-sidebar bg-white border-l border-borderColor" id="sidebar">

    <div class="modern-profile p-3 pb-0 ">
        <!-- Logo -->
        <div class="sidebar-logo fixed h-[50px] w-sidebar flex justify-center pt-3 px-3">
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
        <div class="text-center rounded bg-light p-3 mb-4 user-profile mt-4">
            <div class="flex justify-center items-center size-[45px] online mb-3">
                <img src="assets/img/profiles/avatar-02.jpg" alt="Img" class="rounded-full">
            </div>
            <h6 class="text-xs leading-normal font-normal mb-1">Adrian Herman</h6>
            <p class="fs-10">System Admin</p>
        </div>

    </div>
    <div class="sidebar-header p-3 pb-0 pt-2 hidden">
        <div class="text-center rounded bg-light p-2 mb-4 sidebar-profile flex items-center">
            <div class="size-8 onlin">
                <img src="assets/img/profiles/avatar-02.jpg" alt="Img" class="rounded-full">
            </div>
            <div class="text-start sidebar-profile-info ms-2">
                <h6 class="text-xs leading-normal font-normal mb-1">Adrian Herman</h6>
                <p class="fs-10">System Admin</p>
            </div>
        </div>
        <div class="input-group input-group-flat inline-flex mb-4 hidden">
					<span class="input-icon-addon">
						<i class="ti ti-search"></i>
					</span>
            <input type="text" class="form-control" placeholder="Search in HRMS">
            <span class="input-group-text">
						<kbd>CTRL + / </kbd>
					</span>
        </div>
        <div class="flex items-center justify-between menu-item mb-3 hidden">
            <div class="me-3">
                <a href="calendar.html" class="btn btn-menubar size-[30px] flex items-center justify-center text-gray-500 rounded-[5px] hover:text-gray-900 hover:bg-dark-transparent">
                    <i class="ti ti-layout-grid-remove"></i>
                </a>
            </div>
            <div class="me-3">
                <a href="chat.html" class="btn btn-menubar size-[30px] flex items-center justify-center text-gray-500 rounded-[5px] hover:text-gray-900 hover:bg-dark-transparent relative">
                    <i class="ti ti-brand-hipchat"></i>
                    <span class="badge bg-info rounded-pill flex items-center justify-center header-badge">5</span>
                </a>
            </div>
            <div class="me-3 notification-item">
                <a href="activity.html" class="btn btn-menubar size-[30px] flex items-center justify-center text-gray-500 rounded-[5px] hover:text-gray-900 hover:bg-dark-transparent relative me-1">
                    <i class="ti ti-bell"></i>
                    <span class="notification-status-dot"></span>
                </a>
            </div>
            <div class="me-0">
                <a href="email.html" class="btn btn-menubar size-[30px] flex items-center justify-center text-gray-500 rounded-[5px] hover:text-gray-900 hover:bg-dark-transparent">
                    <i class="ti ti-message"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <x-layouts.app.sidebar.menu-title>@lang('general.basic-management')</x-layouts.app.sidebar.menu-title>
                <x-layouts.app.sidebar.dropdown-nav-list :title="__('general.project-basics')">
                    <x-layouts.app.sidebar.nav-item>{{ trans_choice('general.city', 2) }}</x-layouts.app.sidebar.nav-item>
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
