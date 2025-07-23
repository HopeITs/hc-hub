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
                <li class="menu-title text-[10px] font-semibold text-gray-400 mb-3"><span>MAIN MENU</span></li>
                <li>
                    <ul class="mb-[19px]">
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-smart-home text-gray-500 group-hover:text-gray-900 group-active:text-gray-900"></i>
                                <span class="ms-2">Dashboard</span>
                                <span class="ms-2 text-[10px] bg-danger rounded font-medium text-white px-1 py-0.5 inline-flex items-center">Hot</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="index.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Admin Dashboard</a></li>
                                <li><a href="employee-dashboard.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Employee Dashboard</a></li>
                                <li><a href="deals-dashboard.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Deals Dashboard</a></li>
                                <li><a href="leads-dashboard.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Leads Dashboard</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-layout-grid-add"></i><span class="ms-2">Applications</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="chat.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Chat</a></li>
                                <li class="submenu submenu-two">
                                    <a href="call.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Calls<span class="menu-arrow inside-submenu absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span></a>
                                    <ul>
                                        <li><a href="voice-call.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Voice Call</a></li>
                                        <li><a href="video-call.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Video Call</a></li>
                                        <li><a href="outgoing-call.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Outgoing Call</a></li>
                                        <li><a href="incoming-call.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Incoming Call</a></li>
                                        <li><a href="call-history.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Call History</a></li>
                                    </ul>
                                </li>
                                <li><a href="calendar.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Calendar</a></li>
                                <li><a href="email.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Email</a></li>
                                <li><a href="todo.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">To Do</a></li>
                                <li><a href="notes.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Notes</a></li>
                                <li><a href="social-feed.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Social Feed</a></li>
                                <li><a href="file-manager.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">File Manager</a></li>
                                <li><a href="kanban-view.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Kanban</a></li>
                                <li><a href="invoices.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Invoices</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-user-star"></i><span class="ms-2">Super Admin</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="dashboard.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Dashboard</a></li>
                                <li><a href="companies.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Companies</a></li>
                                <li><a href="subscription.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Subscriptions</a></li>
                                <li><a href="packages.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Packages</a></li>
                                <li><a href="domain.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Domain</a></li>
                                <li><a href="purchase-transaction.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Purchase Transaction</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="menu-title text-[10px] font-semibold text-gray-400 mb-3"><span>LAYOUT</span></li>
                <li>
                    <ul class="mb-[19px]">
                        <li class="mb-[5px]">
                            <a href="layout-horizontal.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-layout-navbar"></i><span class="ms-2">Horizontal</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="layout-detached.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-details"></i><span class="ms-2">Detached</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="layout-modern.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-layout-board-split"></i><span class="ms-2">Modern</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="layout-two-column.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-columns-2"></i><span class="ms-2">Two Column </span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="layout-hovered.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-column-insert-left"></i><span class="ms-2">Hovered</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="layout-box.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-layout-align-middle"></i><span class="ms-2">Boxed</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="layout-horizontal-single.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-layout-navbar-inactive"></i><span class="ms-2">Horizontal Single</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="layout-horizontal-overlay.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-layout-collage"></i><span class="ms-2">Horizontal Overlay</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="layout-horizontal-box.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-layout-board"></i><span class="ms-2">Horizontal Box</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="layout-horizontal-sidemenu.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-table"></i><span class="ms-2">Menu Aside</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="layout-vertical-transparent.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-layout"></i><span class="ms-2">Transparent</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="layout-without-header.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-layout-sidebar"></i><span class="ms-2">Without Header</span>
                            </a>
                        </li>
                        <li class="mb-[5px] active">
                            <a href="layout-rtl.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-text-direction-rtl"></i><span class="ms-2">RTL</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="layout-dark.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-moon"></i><span class="ms-2">Dark</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-title text-[10px] font-semibold text-gray-400 mb-3"><span>PROJECTS</span></li>
                <li>
                    <ul class="mb-[19px]">
                        <li class="mb-[5px]">
                            <a href="clients-grid.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-users-group"></i><span class="ms-2">Clients</span>
                            </a>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-box"></i><span class="ms-2">Projects</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="projects-grid.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Projects</a></li>
                                <li><a href="tasks.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Tasks</a></li>
                                <li><a href="task-board.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Task Board</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="menu-title text-[10px] font-semibold text-gray-400 mb-3"><span>CRM</span></li>
                <li>
                    <ul class="mb-[19px]">
                        <li class="mb-[5px]">
                            <a href="contacts-grid.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-user-shield"></i><span class="ms-2">Contacts</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="companies-grid.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-building"></i><span class="ms-2">Companies</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="deals-grid.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-heart-handshake"></i><span class="ms-2">Deals</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="leads-grid.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-user-check"></i><span class="ms-2">Leads</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="pipeline.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-timeline-event-text"></i><span class="ms-2">Pipeline</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="analytics.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-graph"></i><span class="ms-2">Analytics</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="activity.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-activity"></i><span class="ms-2">Activities</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-title text-[10px] font-semibold text-gray-400 mb-3"><span>HRM</span></li>
                <li>
                    <ul class="mb-[19px]">
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-users"></i><span class="ms-2">Employees</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="employees.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Employee Lists</a></li>
                                <li><a href="employees-grid.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Employee Grid</a></li>
                                <li><a href="employee-details.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Employee Details</a></li>
                                <li><a href="departments.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Departments</a></li>
                                <li><a href="designations.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Designations</a></li>
                                <li><a href="policy.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Policies</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-ticket"></i><span class="ms-2">Tickets</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="tickets.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Tickets</a></li>
                                <li><a href="ticket-details.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Ticket Details</a></li>
                            </ul>
                        </li>
                        <li class="mb-[5px]">
                            <a href="holidays.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-calendar-event"></i><span class="ms-2">Holidays</span>
                            </a>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-file-time"></i><span class="ms-2">Attendance</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li class="submenu submenu-two">
                                    <a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Leaves<span class="menu-arrow inside-submenu  absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span></a>
                                    <ul>
                                        <li><a href="leaves.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Leaves (Admin)</a></li>
                                        <li><a href="leaves-employee.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Leave (Employee)</a></li>
                                        <li><a href="leave-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Leave Settings</a></li>
                                    </ul>
                                </li>
                                <li><a href="attendance-admin.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Attendance (Admin)</a></li>
                                <li><a href="attendance-employee.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Attendance (Employee)</a></li>
                                <li><a href="timesheets.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Timesheets</a></li>
                                <li><a href="schedule-timing.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Shift & Schedule</a></li>
                                <li><a href="overtime.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Overtime</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-school"></i><span class="ms-2">Performance</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="performance-indicator.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Performance Indicator</a></li>
                                <li><a href="performance-review.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Performance Review</a></li>
                                <li><a href="performance-appraisal.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Performance Appraisal</a></li>
                                <li><a href="goal-tracking.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Goal List</a></li>
                                <li><a href="goal-type.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Goal Type</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-edit"></i><span class="ms-2">Training</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="training.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Training List</a></li>
                                <li><a href="trainers.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Trainers</a></li>
                                <li><a href="training-type.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Training Type</a></li>
                            </ul>
                        </li>
                        <li class="mb-[5px]">
                            <a href="promotion.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-speakerphone"></i><span class="ms-2">Promotion</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="resignation.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-external-link"></i><span class="ms-2">Resignation</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="termination.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-circle-x"></i><span class="ms-2">Termination</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-title text-[10px] font-semibold text-gray-400 mb-3"><span>RECRUITMENT</span></li>
                <li>
                    <ul class="mb-[19px]">
                        <li class="mb-[5px]">
                            <a href="job-grid.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-timeline"></i><span class="ms-2">Jobs</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="candidates-grid.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-user-shield"></i><span class="ms-2">Candidates</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="refferals.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-ux-circle"></i><span class="ms-2">Referrals</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-title text-[10px] font-semibold text-gray-400 mb-3"><span>FINANCE & ACCOUNTS</span></li>
                <li>
                    <ul class="mb-[19px]">
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-shopping-cart-dollar"></i><span class="ms-2">Sales</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="estimates.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Estimates</a></li>
                                <li><a href="invoices.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Invoices</a></li>
                                <li><a href="payments.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Payments</a></li>
                                <li><a href="expenses.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Expenses</a></li>
                                <li><a href="provident-fund.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Provident Fund</a></li>
                                <li><a href="taxes.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Taxes</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-file-dollar"></i><span class="ms-2">Accounting</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="categories.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Categories</a></li>
                                <li><a href="budgets.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Budgets</a></li>
                                <li><a href="budget-expenses.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Budget Expenses</a></li>
                                <li><a href="budget-revenues.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Budget Revenues</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-cash"></i><span class="ms-2">Payroll</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="employee-salary.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Employee Salary</a></li>
                                <li><a href="payslip.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Payslip</a></li>
                                <li><a href="payroll.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Payroll Items</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="menu-title text-[10px] font-semibold text-gray-400 mb-3"><span>ADMINISTRATION</span></li>
                <li>
                    <ul class="mb-[19px]">
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-cash"></i><span class="ms-2">Assets</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="assets.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Assets</a></li>
                                <li><a href="asset-categories.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Asset Categories</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-headset"></i><span class="ms-2">Help & Supports</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="knowledgebase.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Knowledge Base</a></li>
                                <li><a href="activity.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Activities</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-user-star"></i><span class="ms-2">User Management</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="users.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Users</a></li>
                                <li><a href="roles-permissions.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Roles & Permissions</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-user-star"></i><span class="ms-2">Reports</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="expenses-report.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Expense Report</a></li>
                                <li><a href="invoice-report.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Invoice Report</a></li>
                                <li><a href="payment-report.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Payment Report</a></li>
                                <li><a href="project-report.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Project Report</a></li>
                                <li><a href="task-report.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Task Report</a></li>
                                <li><a href="user-report.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">User Report</a></li>
                                <li><a href="employee-report.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Employee Report</a></li>
                                <li><a href="payslip-report.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Payslip Report</a></li>
                                <li><a href="attendance-report.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Attendance Report</a></li>
                                <li><a href="leave-report.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Leave Report</a></li>
                                <li><a href="daily-report.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Daily Report</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-settings"></i><span class="ms-2">Settings</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li class="submenu submenu-two">
                                    <a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">General Settings<span class="menu-arrow inside-submenu  absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span></a>
                                    <ul>
                                        <li><a href="profile-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Profile</a></li>
                                        <li><a href="security-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Security</a></li>
                                        <li><a href="notification-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Notifications</a></li>
                                        <li><a href="connected-apps.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Connected Apps</a></li>
                                    </ul>
                                </li>
                                <li class="submenu submenu-two">
                                    <a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Website Settings<span class="menu-arrow inside-submenu  absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span></a>
                                    <ul>
                                        <li><a href="bussiness-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Business Settings</a></li>
                                        <li><a href="seo-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">SEO Settings</a></li>
                                        <li><a href="localization-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Localization</a></li>
                                        <li><a href="prefixes.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Prefixes</a></li>
                                        <li><a href="preferences.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Preferences</a></li>
                                        <li><a href="performance-appraisal.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Appearance</a></li>
                                        <li><a href="language.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Language</a></li>
                                        <li><a href="authentication-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Authentication</a></li>
                                        <li><a href="ai-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">AI Settings</a></li>
                                    </ul>
                                </li>
                                <li class="submenu submenu-two">
                                    <a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">App Settings<span class="menu-arrow inside-submenu  absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span></a>
                                    <ul>
                                        <li><a href="salary-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Salary Settings</a></li>
                                        <li><a href="approval-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Approval Settings</a></li>
                                        <li><a href="invoice-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Invoice Settings</a></li>
                                        <li><a href="leave-type.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Leave Type</a></li>
                                        <li><a href="custom-fields.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Custom Fields</a></li>
                                    </ul>
                                </li>
                                <li class="submenu submenu-two">
                                    <a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">System Settings<span class="menu-arrow inside-submenu  absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span></a>
                                    <ul>
                                        <li><a href="email-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Email Settings</a></li>
                                        <li><a href="email-template.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Email Templates</a></li>
                                        <li><a href="sms-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">SMS Settings</a></li>
                                        <li><a href="sms-template.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">SMS Templates</a></li>
                                        <li><a href="otp-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">OTP</a></li>
                                        <li><a href="gdpr.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">GDPR Cookies</a></li>
                                        <li><a href="maintenance-mode.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Maintenance Mode</a></li>
                                    </ul>
                                </li>
                                <li class="submenu submenu-two">
                                    <a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Financial Settings<span class="menu-arrow inside-submenu  absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span></a>
                                    <ul>
                                        <li><a href="payment-gateways.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Payment Gateways</a></li>
                                        <li><a href="tax-rates.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Tax Rate</a></li>
                                        <li><a href="currencies.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Currencies</a></li>
                                    </ul>
                                </li>
                                <li class="submenu submenu-two">
                                    <a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Other Settings<span class="menu-arrow inside-submenu  absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span></a>
                                    <ul>
                                        <li><a href="custom-css.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Custom CSS</a></li>
                                        <li><a href="custom-js.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Custom JS</a></li>
                                        <li><a href="cronjob.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Cronjob</a></li>
                                        <li><a href="storage-settings.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Storage</a></li>
                                        <li><a href="ban-ip-address.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Ban IP Address</a></li>
                                        <li><a href="backup.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Backup</a></li>
                                        <li><a href="clear-cache.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Clear Cache</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="menu-title text-[10px] font-semibold text-gray-400 mb-3"><span>CONTENT</span></li>
                <li>
                    <ul class="mb-[19px]">
                        <li class="mb-[5px]">
                            <a href="pages.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-box-multiple"></i><span class="ms-2">Pages</span>
                            </a>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-brand-blogger"></i><span class="ms-2">Blogs</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="blogs.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">All Blogs</a></li>
                                <li><a href="blog-categories.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Categories</a></li>
                                <li><a href="blog-comments.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Comments</a></li>
                                <li><a href="blog-tags.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Blog Tags</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-map-pin-check"></i><span class="ms-2">Locations</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="countries.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Countries</a></li>
                                <li><a href="states.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">States</a></li>
                                <li><a href="cities.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Cities</a></li>
                            </ul>
                        </li>
                        <li class="mb-[5px]">
                            <a href="testimonials.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-message-2"></i><span class="ms-2">Testimonials</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="faq.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-question-mark"></i><span class="ms-2">FAQ’S</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-title text-[10px] font-semibold text-gray-400 mb-3"><span>PAGES</span></li>
                <li>
                    <ul class="mb-[19px]">
                        <li class="mb-[5px]">
                            <a href="starter.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-layout-sidebar"></i><span class="ms-2">Starter</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="profile.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-user-circle"></i><span class="ms-2">Profile</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="gallery.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-photo"></i><span class="ms-2">Gallery</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="search-result.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-list-search"></i><span class="ms-2">Search Results</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="timeline.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-timeline"></i><span class="ms-2">Timeline</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="pricing.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-file-dollar"></i><span class="ms-2">Pricing</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="coming-soon.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-progress-bolt"></i><span class="ms-2">Coming Soon</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="under-maintenance.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-alert-octagon"></i><span class="ms-2">Under Maintenance</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="under-construction.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-barrier-block"></i><span class="ms-2">Under Construction</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="api-keys.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-api"></i><span class="ms-2">API Keys</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="privacy-policy.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-file-description"></i><span class="ms-2">Privacy Policy</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="terms-condition.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-file-check"></i><span class="ms-2">Terms & Conditions</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-title text-[10px] font-semibold text-gray-400 mb-3"><span>AUTHENTICATION</span></li>
                <li>
                    <ul class="mb-[19px]">
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-login"></i><span class="ms-2">Login</span><span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="login.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Cover</a></li>
                                <li><a href="login-2.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Illustration</a></li>
                                <li><a href="login-3.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Basic</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-forms"></i><span class="ms-2">Register</span><span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul>
                                <li><a href="register.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Cover</a></li>
                                <li><a href="register-2.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Illustration</a></li>
                                <li><a href="register-3.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Basic</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-help-triangle"></i><span class="ms-2">Forgot Password</span><span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="forgot-password.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Cover</a></li>
                                <li><a href="forgot-password-2.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Illustration</a></li>
                                <li><a href="forgot-password-3.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Basic</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-restore"></i><span class="ms-2">Reset Password</span><span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="reset-password.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Cover</a></li>
                                <li><a href="reset-password-2.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Illustration</a></li>
                                <li><a href="reset-password-3.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Basic</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-mail-exclamation"></i><span class="ms-2">Email Verification</span><span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="email-verification.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Cover</a></li>
                                <li><a href="email-verification-2.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Illustration</a></li>
                                <li><a href="email-verification-3.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Basic</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-password"></i><span class="ms-2">2 Step Verification</span><span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="two-step-verification.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Cover</a></li>
                                <li><a href="two-step-verification-2.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Illustration</a></li>
                                <li><a href="two-step-verification-3.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Basic</a></li>
                            </ul>
                        </li>
                        <li class="mb-[5px]">
                            <a href="lock-screen.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-lock-square"></i><span class="ms-2">Lock Screen</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="error-404.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-error-404"></i><span class="ms-2">404 Error</span>
                            </a>
                        </li>
                        <li class="mb-[5px]">
                            <a href="error-500.html" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-server"></i><span class="ms-2">500 Error</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-title text-[10px] font-semibold text-gray-400 mb-3"><span>UI INTERFACE</span></li>
                <li>
                    <ul class="mb-[19px]">
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-hierarchy-2"></i>
                                <span class="ms-2">Base UI</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li>
                                    <a href="ui-alerts.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Alerts</a>
                                </li>
                                <li>
                                    <a href="ui-accordion.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Accordion</a>
                                </li>
                                <li>
                                    <a href="ui-avatar.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Avatar</a>
                                </li>
                                <li>
                                    <a href="ui-badges.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Badges</a>
                                </li>
                                <li>
                                    <a href="ui-borders.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Border</a>
                                </li>
                                <li>
                                    <a href="ui-buttons.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Buttons</a>
                                </li>
                                <li>
                                    <a href="ui-buttons-group.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Button Group</a>
                                </li>
                                <li>
                                    <a href="ui-breadcrumb.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Breadcrumb</a>
                                </li>
                                <li>
                                    <a href="ui-cards.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Card</a>
                                </li>
                                <li>
                                    <a href="ui-carousel.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Carousel</a>
                                </li>
                                <li>
                                    <a href="ui-colors.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Colors</a>
                                </li>
                                <li>
                                    <a href="ui-dropdowns.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Dropdowns</a>
                                </li>
                                <li>
                                    <a href="ui-grid.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Grid</a>
                                </li>
                                <li>
                                    <a href="ui-images.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Images</a>
                                </li>
                                <li>
                                    <a href="ui-lightbox.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Lightbox</a>
                                </li>
                                <li>
                                    <a href="ui-media.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Media</a>
                                </li>
                                <li>
                                    <a href="ui-modals.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Modals</a>
                                </li>
                                <li>
                                    <a href="ui-offcanvas.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Offcanvas</a>
                                </li>
                                <li>
                                    <a href="ui-pagination.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Pagination</a>
                                </li>
                                <li>
                                    <a href="ui-popovers.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Popovers</a>
                                </li>
                                <li>
                                    <a href="ui-progress.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Progress</a>
                                </li>
                                <li>
                                    <a href="ui-spinner.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Spinner</a>
                                </li>
                                <li>
                                    <a href="ui-sweetalerts.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Sweet Alerts</a>
                                </li>
                                <li>
                                    <a href="ui-nav-tabs.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Tabs</a>
                                </li>
                                <li>
                                    <a href="ui-tooltips.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Tooltips</a>
                                </li>
                                <li>
                                    <a href="ui-typography.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Typography</a>
                                </li>
                                <li>
                                    <a href="ui-video.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Video</a>
                                </li>
                                <li>
                                    <a href="ui-sortable.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Sortable</a>
                                </li>
                                <li>
                                    <a href="ui-swiperjs.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Swiperjs</a>
                                </li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-hierarchy-3"></i>
                                <span class="ms-2">Advanced UI</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li>
                                    <a href="ui-clipboard.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Clipboard</a>
                                </li>
                                <li>
                                    <a href="ui-drag-drop.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Drag & Drop</a>
                                </li>
                                <li>
                                    <a href="ui-rangeslider.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Range Slider</a>
                                </li>
                                <li>
                                    <a href="ui-rating.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Rating</a>
                                </li>
                                <li>
                                    <a href="ui-text-editor.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Text Editor</a>
                                </li>
                                <li>
                                    <a href="ui-counter.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Counter</a>
                                </li>
                                <li>
                                    <a href="ui-scrollbar.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Scrollbar</a>
                                </li>
                                <li>
                                    <a href="ui-stickynote.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Sticky Note</a>
                                </li>
                                <li>
                                    <a href="ui-timeline.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Timeline</a>
                                </li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-input-search"></i>
                                <span class="ms-2">Forms</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li class="submenu submenu-two">
                                    <a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Form Elements <span class="menu-arrow inside-submenu  absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span></a>
                                    <ul>
                                        <li>
                                            <a href="form-basic-inputs.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Basic Inputs</a>
                                        </li>
                                        <li>
                                            <a href="form-checkbox-radios.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Checkbox & Radios</a>
                                        </li>
                                        <li>
                                            <a href="form-input-groups.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Input Groups</a>
                                        </li>
                                        <li>
                                            <a href="form-grid-gutters.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Grid & Gutters</a>
                                        </li>
                                        <li>
                                            <a href="form-select.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Form Select</a>
                                        </li>
                                        <li>
                                            <a href="form-mask.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Input Masks</a>
                                        </li>
                                        <li>
                                            <a href="form-fileupload.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">File Uploads</a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="submenu submenu-two">
                                    <a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Layouts <span class="menu-arrow inside-submenu  absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span></a>
                                    <ul>
                                        <li>
                                            <a href="form-horizontal.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Horizontal Form</a>
                                        </li>
                                        <li>
                                            <a href="form-vertical.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Vertical Form</a>
                                        </li>
                                        <li>
                                            <a href="form-floating-labels.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Floating Labels</a>
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    <a href="form-validation.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Form Validation</a>
                                </li>

                                <li>
                                    <a href="form-select2.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Select2</a>
                                </li>
                                <li>
                                    <a href="form-wizard.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Form Wizard</a>
                                </li>
                                <li>
                                    <a href="form-pickers.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Form Picker</a>
                                </li>

                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-table-plus"></i>
                                <span class="ms-2">Tables</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li>
                                    <a href="tables-basic.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Basic Tables </a>
                                </li>
                                <li>
                                    <a href="data-tables.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Data Table </a>
                                </li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-chart-line"></i>
                                <span class="ms-2">Charts</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li>
                                    <a href="chart-apex.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Apex Charts</a>
                                </li>
                                <li>
                                    <a href="chart-c3.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Chart C3</a>
                                </li>
                                <li>
                                    <a href="chart-js.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Chart Js</a>
                                </li>
                                <li>
                                    <a href="chart-morris.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Morris Charts</a>
                                </li>
                                <li>
                                    <a href="chart-flot.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Flot Charts</a>
                                </li>
                                <li>
                                    <a href="chart-peity.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Peity Charts</a>
                                </li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-icons"></i>
                                <span class="ms-2">Icons</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li>
                                    <a href="icon-fontawesome.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Fontawesome Icons</a>
                                </li>
                                <li>
                                    <a href="icon-tabler.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Tabler Icons</a>
                                </li>
                                <li>
                                    <a href="icon-bootstrap.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Bootstrap Icons</a>
                                </li>
                                <li>
                                    <a href="icon-remix.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Remix Icons</a>
                                </li>
                                <li>
                                    <a href="icon-feather.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Feather Icons</a>
                                </li>
                                <li>
                                    <a href="icon-ionic.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Ionic Icons</a>
                                </li>
                                <li>
                                    <a href="icon-material.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Material Icons</a>
                                </li>
                                <li>
                                    <a href="icon-pe7.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Pe7 Icons</a>
                                </li>
                                <li>
                                    <a href="icon-simpleline.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Simpleline Icons</a>
                                </li>
                                <li>
                                    <a href="icon-themify.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Themify Icons</a>
                                </li>
                                <li>
                                    <a href="icon-weather.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Weather Icons</a>
                                </li>
                                <li>
                                    <a href="icon-typicon.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Typicon Icons</a>
                                </li>
                                <li>
                                    <a href="icon-flag.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Flag Icons</a>
                                </li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-table-plus"></i>
                                <span class="ms-2">Maps</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li>
                                    <a href="maps-vector.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Vector</a>
                                </li>
                                <li>
                                    <a href="maps-leaflet.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Leaflet</a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="menu-title text-[10px] font-semibold text-gray-400 mb-3"><span>Extras</span></li>
                <li>
                    <ul class="mb-[19px]">
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-file-text"></i>
                                <span class="ms-2">Documentation</span>
                            </a>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-exchange"></i>
                                <span class="ms-2">Changelog</span>
                                <span class="bg-pink text-white text-[10px] py-0.5 px-1.5 ms-1 rounded leading-4">v4.0.3</span>
                            </a>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-menu-2"></i>
                                <span class="ms-2">Multi Level</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Multilevel 1</a></li>
                                <li class="submenu submenu-two">
                                    <a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Multilevel 2<span class="menu-arrow inside-submenu  absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span></a>
                                    <ul>
                                        <li><a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Multilevel 2.1</a></li>
                                        <li class="submenu submenu-two submenu-three">
                                            <a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-5 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Multilevel 2.2<span class="menu-arrow inside-submenu inside-submenu-two absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span></a>
                                            <ul>
                                                <li><a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-10 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Multilevel 2.2.1</a></li>
                                                <li><a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 ms-10 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Multilevel 2.2.2</a></li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                                <li><a href="javascript:void(0);" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Multilevel 3</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- /Sidebar -->
