<!-- Sidebar -->
<div class="sidebar fixed top-0 right-0 bottom-0 w-sidebar bg-white border-l border-borderColor" id="sidebar">
    <!-- Logo -->
    <div class="sidebar-logo fixed h-[50px] w-sidebar relative pt-3 px-3">
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
    <div class="modern-profile p-3 pb-0 ">
        <div class="text-center rounded bg-light p-3 mb-4 user-profile flex items-center justify-center flex-col">
            <div class="relative inline-flex items-center justify-center size-[45px] online mb-3">
                <img src="assets/img/profiles/avatar-02.jpg" alt="Img" class="rounded-full">
                <div class="absolute inline-flex items-center justify-center w-3 h-3 text-xs font-bold text-white bg-success border-2 border-white rounded-full top-8 end-0 dark:border-gray-900"></div>
            </div>
            <h6 class="text-xs leading-normal font-normal mb-1">Adrian Herman</h6>
            <p class="text-[10px]">System Admin</p>
        </div>

    </div>
    <div class="sidebar-header p-3 pb-0 pt-2 ">
        <div class="text-center rounded bg-light p-2 mb-4 sidebar-profile flex items-center">
            <div class="size-8 onlin">
                <img src="assets/img/profiles/avatar-02.jpg" alt="Img" class="rounded-full">
            </div>
            <div class="text-start sidebar-profile-info ms-2">
                <h6 class="text-xs leading-normal font-normal mb-1">Adrian Herman</h6>
                <p class="text-[10px]">System Admin</p>
            </div>
        </div>
        <div class="flex w-[220px] relative me-1 mb-4">
            <span class="absolute inset-y-0 start-0 flex items-center ps-2.5 pointer-events-none text-gray-400"><i class="ti ti-search"></i></span>
            <input type="text" class="block flex-1 border border-borderColor bg-white rounded-[5px] py-1.5 pl-7 pr-12 text-gray-900 placeholder:text-gray-400 focus:ring-0 focus:border-borderColor h-[30px] text-xs" placeholder="Search in HRMS">
            <span class="absolute inset-y-1/2 end-0 flex items-center me-2.5 pointer-events-none "><kbd class="bg-dark-transparent text-[10px] font-medium text-gray-500 px-[3px] py-0.5 rounded">CTRL + / </kbd></span>
        </div>
        <div class="flex items-center justify-between menu-item mb-3">
            <div class="me-3">
                <a href="calendar.html" class="btn btn-menubar border border-borderColor size-[30px] flex items-center justify-center text-gray-500 rounded-[5px] hover:text-gray-900 hover:bg-dark-transparent">
                    <i class="ti ti-layout-grid-remove"></i>
                </a>
            </div>

            <div class="me-3 notification-item">
                <a href="chat.html" class="relative inline-flex items-center justify-center border border-borderColor size-[30px] p-3 text-sm font-medium text-center hover:text-gray-900 hover:bg-dark-transparent  rounded-[5px]">
                    <i class="ti ti-brand-hipchat"></i>

                    <span class="text-[6px] w-[10px] h-[10px] flex items-center justify-center text-white rounded-full absolute right-[5px] text top-[2px] z-[1] bg-info">5</span>
                </a>
            </div>
            <div class="me-3 notification-item">
                <a href="activity.html" class="relative inline-flex items-center justify-center border border-borderColor size-[30px] p-3 text-sm font-medium text-center hover:text-gray-900 hover:bg-dark-transparent  rounded-[5px]">
                    <i class="ti ti-bell"></i>

                    <div class="notification-status-dot w-[6px] h-[6px] rounded-full absolute right-[5px] top-[5px] z-[1] bg-danger"></div>
                </a>
            </div>
            <div class="me-0">
                <a href="email.html" class="btn btn-menubar border border-borderColor size-[30px] flex items-center justify-center text-gray-500 rounded-[5px] hover:text-gray-900 hover:bg-dark-transparent">
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
                            <a href="javascript:void(0);" class=" relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-smart-home text-gray-500 group-hover:text-gray-900 group-active:text-gray-900"></i>
                                <span class="ms-2">Dashboard</span>
                                <span class="ms-2 text-[10px] bg-danger rounded font-medium text-white px-1 py-0.5 inline-flex items-center">Hot</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 right-2.5 flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:left-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="index.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Admin Dashboard</a></li>
                                <li><a href="employee-dashboard.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Employee Dashboard</a></li>
                                <li><a href="deals-dashboard.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Deals Dashboard</a></li>
                                <li><a href="leads-dashboard.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Leads Dashboard</a></li>
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
                        <li class="mb-[5px] active">
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
                        <li class="mb-[5px]">
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
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 right-2.5 flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:left-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="blogs.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">All Blogs</a></li>
                                <li><a href="blog-categories.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Categories</a></li>
                                <li><a href="blog-comments.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Comments</a></li>
                                <li><a href="blog-tags.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Blog Tags</a></li>
                            </ul>
                        </li>
                        <li class="submenu mb-[5px]">
                            <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
                                <i class="ti ti-map-pin-check"></i><span class="ms-2">Locations</span>
                                <span class="menu-arrow absolute top-1/2 -translate-y-1/2 right-2.5 flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                            </a>
                            <ul class="relative mt-2 before:absolute before:top-0 before:left-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
                                <li><a href="countries.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Countries</a></li>
                                <li><a href="states.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">States</a></li>
                                <li><a href="cities.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:left-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Cities</a></li>
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

            </ul>
        </div>
    </div>
</div>
<!-- /Sidebar -->



