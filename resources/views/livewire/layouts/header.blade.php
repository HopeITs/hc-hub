
<!-- Header -->
<div class="header fixed top-0  left-0 right-[252px] bg-white h-[50px] border-b border-gray-200 lg:right-vertical-w">
    <div class="main-header h-[50px]">

        <div class="header-left hidden relative float-left text-center h-[50px] z-[1] media-max-md:flex">
            <a href="index.html" class="logo">
                <img src="assets/img/logo.svg" alt="Logo">
            </a>
            <a href="index.html" class="dark-logo">
                <img src="assets/img/logo-white.svg" alt="Logo">
            </a>
        </div>

        <a id="mobile_btn" class="mobile_btn" href="#sidebar">
					<span class="bar-icon">
						<span></span>
						<span></span>
						<span></span>
					</span>
        </a>

        <div class="header-user px-[15px] py-4">
            <div class="nav user-menu nav-list flex items-center justify-center h-full px-6 'media-max-md:hidden">

                <div class="me-auto flex items-center" id="header-search">
                    <a id="toggle_btn" href="javascript:void(0);" class="btn btn-menubar size-[30px] flex items-center justify-center text-gray-500 text-lg leading-normal rounded-[5px] hover:text-gray-900 hover:bg-dark-transparent me-1">
                        <i class="ti ti-arrow-bar-to-left"></i>
                    </a>
                    <!-- Search -->
                    <div class="flex w-[259px] relative me-1">
                        <span class="absolute inset-y-0 start-0 flex items-center ps-2.5 pointer-events-none text-gray-400"><i class="ti ti-search"></i></span>
                        <input type="text" class="block flex-1 border border-borderColor bg-white rounded-[5px] py-1.5 pl-7 pr-12 text-gray-900 placeholder:text-gray-400 focus:ring-0 focus:border-borderColor h-[30px] text-xs" placeholder="Search in HRMS">
                        <span class="absolute inset-y-1/2 end-0 flex items-center me-2.5 pointer-events-none "><kbd class="bg-dark-transparent text-[10px] font-medium text-gray-500 px-[3px] py-0.5 rounded">CTRL + / </kbd></span>
                    </div>
                    <!-- /Search -->

                    <div>
                        <button data-dropdown-toggle="crm-dropdown" class="btn btn-menubar size-[30px] flex items-center justify-center text-gray-500 rounded-[5px] me-1 hover:text-gray-900 hover:bg-dark-transparent  focus:bg-dark-transparent focus:text-gray-900" type="button">
                            <i class="ti ti-layout-grid"></i>
                        </button>
                        <div id="crm-dropdown" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-[5px] shadow w-55 dark:bg-gray-700">
                            <div class="border-b border px-[20px] py-4">
                                <h4>CRM</h4>
                            </div>
                            <div class="border-b border p-[20px] pb-1">
                                <div class="grid grid-cols-1 gap-x-6 sm:grid-cols-2 *:border *:border-borderColor *:bg-white *:p-2 *:rounded-md *:font-medium">
                                    <a href="contacts.html" class="flex items-center justify-between p-2 crm-link text-gray-900 group hover:bg-primary-transparent hover:border-primary-transparent hover:text-primary mb-3">
												<span class="flex items-center me-3">
													<i class="ti ti-user-shield text-default me-2 group-hover:text-primary"></i>Contacts
												</span>
                                        <i class="ti ti-arrow-right"></i>
                                    </a>
                                    <a href="companies-grid.html" class="flex items-center justify-between p-2 crm-link text-gray-900 group hover:bg-primary-transparent hover:border-primary-transparent hover:text-primary mb-3">
												<span class="flex items-center me-3">
													<i class="ti ti-building text-default me-2 group-hover:text-primary"></i>Companies
												</span>
                                        <i class="ti ti-arrow-right"></i>
                                    </a>
                                    <a href="deals-grid.html" class="flex items-center justify-between p-2 crm-link text-gray-900 group hover:bg-primary-transparent hover:border-primary-transparent hover:text-primary mb-3">
												<span class="flex items-center me-3">
													<i class="ti ti-heart-handshake text-default me-2 group-hover:text-primary"></i>Deals
												</span>
                                        <i class="ti ti-arrow-right"></i>
                                    </a>
                                    <a href="leads-grid.html" class="flex items-center justify-between p-2 crm-link text-gray-900 group hover:bg-primary-transparent hover:border-primary-transparent hover:text-primary mb-3">
												<span class="flex items-center me-3">
													<i class="ti ti-user-check text-default me-2 group-hover:text-primary"></i>Leads
												</span>
                                        <i class="ti ti-arrow-right"></i>
                                    </a>
                                    <a href="pipeline.html" class="flex items-center justify-between p-2 crm-link text-gray-900 group hover:bg-primary-transparent hover:border-primary-transparent hover:text-primary mb-3">
												<span class="flex items-center me-3">
													<i class="ti ti-timeline-event-text text-default me-2 group-hover:text-primary"></i>Pipeline
												</span>
                                        <i class="ti ti-arrow-right"></i>
                                    </a>
                                    <a href="activity.html" class="flex items-center justify-between p-2 crm-link text-gray-900 group hover:bg-primary-transparent hover:border-primary-transparent hover:text-primary mb-3">
												<span class="flex items-center me-3">
													<i class="ti ti-activity text-default me-2 group-hover:text-primary"></i>Activities
												</span>
                                        <i class="ti ti-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="profile-settings.html" class="btn btn-menubar size-[30px] flex items-center justify-center text-gray-500 rounded-[5px] hover:text-gray-900 hover:bg-dark-transparent">
                        <i class="ti ti-settings-cog"></i>
                    </a>
                </div>

                <!-- Horizontal Single -->
                <div class="sidebar sidebar-horizontal hidden" id="horizontal-single">
                    <div class="sidebar-menu">
                        <div class="main-menu">
                            <ul class="nav-menu">
                                <li class="menu-title text-[10px] font-semibold text-gray-400 mb-3">
                                    <span>Main</span>
                                </li>
                                <li class="submenu">
                                    <a href="#" class="active">
                                        <i class="ti ti-smart-home"></i><span>Dashboard</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li><a href="index.html" class="active">Admin Dashboard</a></li>
                                        <li><a href="employee-dashboard.html">Employee Dashboard</a></li>
                                        <li><a href="deals-dashboard.html">Deals Dashboard</a></li>
                                        <li><a href="leads-dashboard.html">Leads Dashboard</a></li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="#">
                                        <i class="ti ti-user-star"></i><span>Super Admin</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li><a href="dashboard.html">Dashboard</a></li>
                                        <li><a href="companies.html">Companies</a></li>
                                        <li><a href="subscription.html">Subscriptions</a></li>
                                        <li><a href="packages.html">Packages</a></li>
                                        <li><a href="domain.html">Domain</a></li>
                                        <li><a href="purchase-transaction.html">Purchase Transaction</a></li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="#">
                                        <i class="ti ti-layout-grid-add"></i><span>Applications</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li><a href="chat.html">Chat</a></li>
                                        <li class="submenu submenu-two">
                                            <a href="call.html">Calls<span
                                                    class="menu-arrow inside-submenu"></span></a>
                                            <ul>
                                                <li><a href="voice-call.html">Voice Call</a></li>
                                                <li><a href="video-call.html">Video Call</a></li>
                                                <li><a href="outgoing-call.html">Outgoing Call</a></li>
                                                <li><a href="incoming-call.html">Incoming Call</a></li>
                                                <li><a href="call-history.html">Call History</a></li>
                                            </ul>
                                        </li>
                                        <li><a href="calendar.html">Calendar</a></li>
                                        <li><a href="email.html">Email</a></li>
                                        <li><a href="todo.html">To Do</a></li>
                                        <li><a href="notes.html">Notes</a></li>
                                        <li><a href="file-manager.html">File Manager</a></li>
                                        <li><a href="kanban-view.html">Kanban</a></li>
                                        <li><a href="invoices.html">Invoices</a></li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="#">
                                        <i class="ti ti-layout-board-split"></i><span>Layouts</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li>
                                            <a href="layout-horizontal.html">
                                                <span>Horizontal</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="layout-detached.html">
                                                <span>Detached</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="layout-modern.html">
                                                <span>Modern</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="layout-two-column.html">
                                                <span>Two Column </span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="layout-hovered.html">
                                                <span>Hovered</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="layout-box.html">
                                                <span>Boxed</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="layout-horizontal-single.html">
                                                <span>Horizontal Single</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="layout-horizontal-overlay.html">
                                                <span>Horizontal Overlay</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="layout-horizontal-box.html">
                                                <span>Horizontal Box</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="layout-horizontal-sidemenu.html">
                                                <span>Menu Aside</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="layout-vertical-transparent.html">
                                                <span>Transparent</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="layout-without-header.html">
                                                <span>Without Header</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="layout-rtl.html">
                                                <span>RTL</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="layout-dark.html">
                                                <span>Dark</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="#">
                                        <i class="ti ti-user-star"></i><span>Projects</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li>
                                            <a href="clients-grid.html"><span>Clients</span>
                                            </a>
                                        </li>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"><span>Projects</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li><a href="projects-grid.html">Projects</a></li>
                                                <li><a href="tasks.html">Tasks</a></li>
                                                <li><a href="task-board.html">Task Board</a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu">
                                            <a href="call.html">Crm<span class="menu-arrow"></span></a>
                                            <ul>
                                                <li><a href="contacts-grid.html"><span>Contacts</span></a></li>
                                                <li><a href="companies-grid.html"><span>Companies</span></a></li>
                                                <li><a href="deals-grid.html"><span>Deals</span></a></li>
                                                <li><a href="leads-grid.html"><span>Leads</span></a></li>
                                                <li><a href="pipeline.html"><span>Pipeline</span></a></li>
                                                <li><a href="analytics.html"><span>Analytics</span></a></li>
                                                <li><a href="activity.html"><span>Activities</span></a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"><span>Employees</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li><a href="employees.html">Employee Lists</a></li>
                                                <li><a href="employees-grid.html">Employee Grid</a></li>
                                                <li><a href="employee-details.html">Employee Details</a></li>
                                                <li><a href="departments.html">Departments</a></li>
                                                <li><a href="designations.html">Designations</a></li>
                                                <li><a href="policy.html">Policies</a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"><span>Tickets</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li><a href="tickets.html">Tickets</a></li>
                                                <li><a href="ticket-details.html">Ticket Details</a></li>
                                            </ul>
                                        </li>
                                        <li><a href="holidays.html"><span>Holidays</span></a></li>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"><span>Attendance</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">Leaves<span class="menu-arrow"></span></a>
                                                    <ul>
                                                        <li><a href="leaves.html">Leaves (Admin)</a></li>
                                                        <li><a href="leaves-employee.html">Leave (Employee)</a></li>
                                                        <li><a href="leave-settings.html">Leave Settings</a></li>
                                                    </ul>
                                                </li>
                                                <li><a href="attendance-admin.html">Attendance (Admin)</a></li>
                                                <li><a href="attendance-employee.html">Attendance (Employee)</a></li>
                                                <li><a href="timesheets.html">Timesheets</a></li>
                                                <li><a href="schedule-timing.html">Shift & Schedule</a></li>
                                                <li><a href="overtime.html">Overtime</a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"><span>Performance</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li><a href="performance-indicator.html">Performance Indicator</a></li>
                                                <li><a href="performance-review.html">Performance Review</a></li>
                                                <li><a href="performance-appraisal.html">Performance Appraisal</a></li>
                                                <li><a href="goal-tracking.html">Goal List</a></li>
                                                <li><a href="goal-type.html">Goal Type</a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"><span>Training</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li><a href="training.html">Training List</a></li>
                                                <li><a href="trainers.html">Trainers</a></li>
                                                <li><a href="training-type.html">Training Type</a></li>
                                            </ul>
                                        </li>
                                        <li><a href="promotion.html"><span>Promotion</span></a></li>
                                        <li><a href="resignation.html"><span>Resignation</span></a></li>
                                        <li><a href="termination.html"><span>Termination</span></a></li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="#">
                                        <i class="ti ti-user-star"></i><span>Administration</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"><span>Sales</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li><a href="estimates.html">Estimates</a></li>
                                                <li><a href="invoices.html">Invoices</a></li>
                                                <li><a href="payments.html">Payments</a></li>
                                                <li><a href="expenses.html">Expenses</a></li>
                                                <li><a href="provident-fund.html">Provident Fund</a></li>
                                                <li><a href="taxes.html">Taxes</a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"><span>Accounting</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li><a href="categories.html">Categories</a></li>
                                                <li><a href="budgets.html">Budgets</a></li>
                                                <li><a href="budget-expenses.html">Budget Expenses</a></li>
                                                <li><a href="budget-revenues.html">Budget Revenues</a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"><span>Payroll</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li><a href="employee-salary.html">Employee Salary</a></li>
                                                <li><a href="payslip.html">Payslip</a></li>
                                                <li><a href="payroll.html">Payroll Items</a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"><span>Assets</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li><a href="assets.html">Assets</a></li>
                                                <li><a href="asset-categories.html">Asset Categories</a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"><span>Help & Supports</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li><a href="knowledgebase.html">Knowledge Base</a></li>
                                                <li><a href="activity.html">Activities</a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"><span>User Management</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li><a href="users.html">Users</a></li>
                                                <li><a href="roles-permissions.html">Roles & Permissions</a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"><span>Reports</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li><a href="expenses-report.html">Expense Report</a></li>
                                                <li><a href="invoice-report.html">Invoice Report</a></li>
                                                <li><a href="payment-report.html">Payment Report</a></li>
                                                <li><a href="project-report.html">Project Report</a></li>
                                                <li><a href="task-report.html">Task Report</a></li>
                                                <li><a href="user-report.html">User Report</a></li>
                                                <li><a href="employee-report.html">Employee Report</a></li>
                                                <li><a href="payslip-report.html">Payslip Report</a></li>
                                                <li><a href="attendance-report.html">Attendance Report</a></li>
                                                <li><a href="leave-report.html">Leave Report</a></li>
                                                <li><a href="daily-report.html">Daily Report</a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"><span>Settings</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">General Settings<span class="menu-arrow"></span></a>
                                                    <ul>
                                                        <li><a href="profile-settings.html">Profile</a></li>
                                                        <li><a href="security-settings.html">Security</a></li>
                                                        <li><a href="notification-settings.html">Notifications</a></li>
                                                        <li><a href="connected-apps.html">Connected Apps</a></li>
                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">Website Settings<span class="menu-arrow"></span></a>
                                                    <ul>
                                                        <li><a href="bussiness-settings.html">Business Settings</a></li>
                                                        <li><a href="seo-settings.html">SEO Settings</a></li>
                                                        <li><a href="localization-settings.html">Localization</a></li>
                                                        <li><a href="prefixes.html">Prefixes</a></li>
                                                        <li><a href="preferences.html">Preferences</a></li>
                                                        <li><a href="performance-appraisal.html">Appearance</a></li>
                                                        <li><a href="language.html">Language</a></li>
                                                        <li><a href="authentication-settings.html">Authentication</a></li>
                                                        <li><a href="ai-settings.html">AI Settings</a></li>
                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">App Settings<span class="menu-arrow"></span></a>
                                                    <ul>
                                                        <li><a href="salary-settings.html">Salary Settings</a></li>
                                                        <li><a href="approval-settings.html">Approval Settings</a></li>
                                                        <li><a href="invoice-settings.html">Invoice Settings</a></li>
                                                        <li><a href="leave-type.html">Leave Type</a></li>
                                                        <li><a href="custom-fields.html">Custom Fields</a></li>
                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">System Settings<span class="menu-arrow"></span></a>
                                                    <ul>
                                                        <li><a href="email-settings.html">Email Settings</a></li>
                                                        <li><a href="email-template.html">Email Templates</a></li>
                                                        <li><a href="sms-settings.html">SMS Settings</a></li>
                                                        <li><a href="sms-template.html">SMS Templates</a></li>
                                                        <li><a href="otp-settings.html">OTP</a></li>
                                                        <li><a href="gdpr.html">GDPR Cookies</a></li>
                                                        <li><a href="maintenance-mode.html">Maintenance Mode</a></li>
                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">Financial Settings<span class="menu-arrow"></span></a>
                                                    <ul>
                                                        <li><a href="payment-gateways.html">Payment Gateways</a></li>
                                                        <li><a href="tax-rates.html">Tax Rate</a></li>
                                                        <li><a href="currencies.html">Currencies</a></li>
                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">Other Settings<span class="menu-arrow"></span></a>
                                                    <ul>
                                                        <li><a href="custom-css.html">Custom CSS</a></li>
                                                        <li><a href="custom-js.html">Custom JS</a></li>
                                                        <li><a href="cronjob.html">Cronjob</a></li>
                                                        <li><a href="storage-settings.html">Storage</a></li>
                                                        <li><a href="ban-ip-address.html">Ban IP Address</a></li>
                                                        <li><a href="backup.html">Backup</a></li>
                                                        <li><a href="clear-cache.html">Clear Cache</a></li>
                                                    </ul>
                                                </li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="#">
                                        <i class="ti ti-page-break"></i><span>Pages</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        <li><a href="starter.html"><span>Starter</span></a></li>
                                        <li><a href="profile.html"><span>Profile</span></a></li>
                                        <li><a href="gallery.html"><span>Gallery</span></a></li>
                                        <li><a href="search-result.html"><span>Search Results</span></a></li>
                                        <li><a href="timeline.html"><span>Timeline</span></a></li>
                                        <li><a href="pricing.html"><span>Pricing</span></a></li>
                                        <li><a href="coming-soon.html"><span>Coming Soon</span></a></li>
                                        <li><a href="under-maintenance.html"><span>Under Maintenance</span></a></li>
                                        <li><a href="under-construction.html"><span>Under Construction</span></a></li>
                                        <li><a href="api-keys.html"><span>API Keys</span></a></li>
                                        <li><a href="privacy-policy.html"><span>Privacy Policy</span></a></li>
                                        <li><a href="terms-condition.html"><span>Terms & Conditions</span></a></li>
                                        <li class="submenu">
                                            <a href="#"><span>Content</span> <span class="menu-arrow"></span></a>
                                            <ul>
                                                <li><a href="pages.html">Pages</a></li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">Blogs<span class="menu-arrow"></span></a>
                                                    <ul>
                                                        <li><a href="blogs.html">All Blogs</a></li>
                                                        <li><a href="blog-categories.html">Categories</a></li>
                                                        <li><a href="blog-comments.html">Comments</a></li>
                                                        <li><a href="blog-tags.html">Tags</a></li>
                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">Locations<span class="menu-arrow"></span></a>
                                                    <ul>
                                                        <li><a href="countries.html">Countries</a></li>
                                                        <li><a href="states.html">States</a></li>
                                                        <li><a href="cities.html">Cities</a></li>
                                                    </ul>
                                                </li>
                                                <li><a href="testimonials.html">Testimonials</a></li>
                                                <li><a href="faq.html">FAQ’S</a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu">
                                            <a href="#">
                                                <span>Authentication</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);" class="">Login<span class="menu-arrow"></span></a>
                                                    <ul>
                                                        <li><a href="login.html">Cover</a></li>
                                                        <li><a href="login-2.html">Illustration</a></li>
                                                        <li><a href="login-3.html">Basic</a></li>
                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);" class="">Register<span class="menu-arrow"></span></a>
                                                    <ul>
                                                        <li><a href="register.html">Cover</a></li>
                                                        <li><a href="register-2.html">Illustration</a></li>
                                                        <li><a href="register-3.html">Basic</a></li>
                                                    </ul>
                                                </li>
                                                <li class="submenu"><a href="javascript:void(0);">Forgot Password<span class="menu-arrow"></span></a>
                                                    <ul>
                                                        <li><a href="forgot-password.html">Cover</a></li>
                                                        <li><a href="forgot-password-2.html">Illustration</a></li>
                                                        <li><a href="forgot-password-3.html">Basic</a></li>
                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">Reset Password<span class="menu-arrow"></span></a>
                                                    <ul>
                                                        <li><a href="reset-password.html">Cover</a></li>
                                                        <li><a href="reset-password-2.html">Illustration</a></li>
                                                        <li><a href="reset-password-3.html">Basic</a></li>
                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">Email Verification<span class="menu-arrow"></span></a>
                                                    <ul>
                                                        <li><a href="email-verification.html">Cover</a></li>
                                                        <li><a href="email-verification-2.html">Illustration</a></li>
                                                        <li><a href="email-verification-3.html">Basic</a></li>
                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">2 Step Verification<span class="menu-arrow"></span></a>
                                                    <ul>
                                                        <li><a href="two-step-verification.html">Cover</a></li>
                                                        <li><a href="two-step-verification-2.html">Illustration</a></li>
                                                        <li><a href="two-step-verification-3.html">Basic</a></li>
                                                    </ul>
                                                </li>
                                                <li><a href="lock-screen.html">Lock Screen</a></li>
                                                <li><a href="error-404.html">404 Error</a></li>
                                                <li><a href="error-500.html">500 Error</a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu">
                                            <a href="#">
                                                <span>UI Interface</span>
                                                <span class="menu-arrow"></span>
                                            </a>
                                            <ul>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">
                                                        <i class="ti ti-hierarchy-2"></i>
                                                        <span>Base UI</span>
                                                        <span class="menu-arrow"></span>
                                                    </a>
                                                    <ul>
                                                        <li>
                                                            <a href="ui-alerts.html">Alerts</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-accordion.html">Accordion</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-avatar.html">Avatar</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-badges.html">Badges</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-borders.html">Border</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-buttons.html">Buttons</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-buttons-group.html">Button Group</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-breadcrumb.html">Breadcrumb</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-cards.html">Card</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-carousel.html">Carousel</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-colors.html">Colors</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-dropdowns.html">Dropdowns</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-grid.html">Grid</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-images.html">Images</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-lightbox.html">Lightbox</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-media.html">Media</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-modals.html">Modals</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-offcanvas.html">Offcanvas</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-pagination.html">Pagination</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-popovers.html">Popovers</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-progress.html">Progress</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-spinner.html">Spinner</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-sweetalerts.html">Sweet Alerts</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-nav-tabs.html">Tabs</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-tooltips.html">Tooltips</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-typography.html">Typography</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-video.html">Video</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-sortable.html">Sortable</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-swiperjs.html">Swiperjs</a>
                                                        </li>
                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">
                                                        <i class="ti ti-hierarchy-3"></i>
                                                        <span>Advanced UI</span>
                                                        <span class="menu-arrow"></span>
                                                    </a>
                                                    <ul>
                                                        <li>
                                                            <a href="ui-clipboard.html">Clipboard</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-drag-drop.html">Drag & Drop</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-rangeslider.html">Range Slider</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-rating.html">Rating</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-text-editor.html">Text Editor</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-counter.html">Counter</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-scrollbar.html">Scrollbar</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-stickynote.html">Sticky Note</a>
                                                        </li>
                                                        <li>
                                                            <a href="ui-timeline.html">Timeline</a>
                                                        </li>
                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">
                                                        <i class="ti ti-input-search"></i>
                                                        <span>Forms</span>
                                                        <span class="menu-arrow"></span>
                                                    </a>
                                                    <ul>
                                                        <li class="submenu submenu-two">
                                                            <a href="javascript:void(0);">Form Elements <span class="menu-arrow inside-submenu  absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                                                            </a>
                                                            <ul>
                                                                <li>
                                                                    <a href="form-basic-inputs.html">Basic Inputs</a>
                                                                </li>
                                                                <li>
                                                                    <a href="form-checkbox-radios.html">Checkbox & Radios</a>
                                                                </li>
                                                                <li>
                                                                    <a href="form-input-groups.html">Input Groups</a>
                                                                </li>
                                                                <li>
                                                                    <a href="form-grid-gutters.html">Grid & Gutters</a>
                                                                </li>
                                                                <li>
                                                                    <a href="form-select.html">Form Select</a>
                                                                </li>
                                                                <li>
                                                                    <a href="form-mask.html">Input Masks</a>
                                                                </li>
                                                                <li>
                                                                    <a href="form-fileupload.html">File Uploads</a>
                                                                </li>
                                                            </ul>
                                                        </li>
                                                        <li class="submenu submenu-two">
                                                            <a href="javascript:void(0);">Layouts <span class="menu-arrow inside-submenu  absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
                                                            </a>
                                                            <ul>
                                                                <li>
                                                                    <a href="form-horizontal.html">Horizontal Form</a>
                                                                </li>
                                                                <li>
                                                                    <a href="form-vertical.html">Vertical Form</a>
                                                                </li>
                                                                <li>
                                                                    <a href="form-floating-labels.html">Floating Labels</a>
                                                                </li>
                                                            </ul>
                                                        </li>
                                                        <li>
                                                            <a href="form-validation.html">Form Validation</a>
                                                        </li>

                                                        <li>
                                                            <a href="form-select2.html">Select2</a>
                                                        </li>
                                                        <li>
                                                            <a href="form-wizard.html">Form Wizard</a>
                                                        </li>
                                                        <li>
                                                            <a href="form-pickers.html">Form Pickers</a>
                                                        </li>

                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">
                                                        <i class="ti ti-table-plus"></i>
                                                        <span>Tables</span>
                                                        <span class="menu-arrow"></span>
                                                    </a>
                                                    <ul>
                                                        <li>
                                                            <a href="tables-basic.html">Basic Tables </a>
                                                        </li>
                                                        <li>
                                                            <a href="data-tables.html">Data Table </a>
                                                        </li>
                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">
                                                        <i class="ti ti-chart-line"></i>
                                                        <span>Charts</span>
                                                        <span class="menu-arrow"></span>
                                                    </a>
                                                    <ul>
                                                        <li>
                                                            <a href="chart-apex.html">Apex Charts</a>
                                                        </li>
                                                        <li>
                                                            <a href="chart-c3.html">Chart C3</a>
                                                        </li>
                                                        <li>
                                                            <a href="chart-js.html">Chart Js</a>
                                                        </li>
                                                        <li>
                                                            <a href="chart-morris.html">Morris Charts</a>
                                                        </li>
                                                        <li>
                                                            <a href="chart-flot.html">Flot Charts</a>
                                                        </li>
                                                        <li>
                                                            <a href="chart-peity.html">Peity Charts</a>
                                                        </li>
                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">
                                                        <i class="ti ti-icons"></i>
                                                        <span>Icons</span>
                                                        <span class="menu-arrow"></span>
                                                    </a>
                                                    <ul>
                                                        <li>
                                                            <a href="icon-fontawesome.html">Fontawesome Icons</a>
                                                        </li>
                                                        <li>
                                                            <a href="icon-tabler.html">Tabler Icons</a>
                                                        </li>
                                                        <li>
                                                            <a href="icon-bootstrap.html">Bootstrap Icons</a>
                                                        </li>
                                                        <li>
                                                            <a href="icon-remix.html">Remix Icons</a>
                                                        </li>
                                                        <li>
                                                            <a href="icon-feather.html">Feather Icons</a>
                                                        </li>
                                                        <li>
                                                            <a href="icon-ionic.html">Ionic Icons</a>
                                                        </li>
                                                        <li>
                                                            <a href="icon-material.html">Material Icons</a>
                                                        </li>
                                                        <li>
                                                            <a href="icon-pe7.html">Pe7 Icons</a>
                                                        </li>
                                                        <li>
                                                            <a href="icon-simpleline.html">Simpleline Icons</a>
                                                        </li>
                                                        <li>
                                                            <a href="icon-themify.html">Themify Icons</a>
                                                        </li>
                                                        <li>
                                                            <a href="icon-weather.html">Weather Icons</a>
                                                        </li>
                                                        <li>
                                                            <a href="icon-typicon.html">Typicon Icons</a>
                                                        </li>
                                                        <li>
                                                            <a href="icon-flag.html">Flag Icons</a>
                                                        </li>
                                                    </ul>
                                                </li>
                                                <li class="submenu">
                                                    <a href="javascript:void(0);">
                                                        <i class="ti ti-table-plus"></i>
                                                        <span>Maps</span>
                                                        <span class="menu-arrow"></span>
                                                    </a>
                                                    <ul>
                                                        <li>
                                                            <a href="maps-vector.html">Vector</a>
                                                        </li>
                                                        <li>
                                                            <a href="maps-leaflet.html">Leaflet</a>
                                                        </li>
                                                    </ul>
                                                </li>
                                            </ul>
                                        </li>
                                        <li><a href="#">Documentation</a></li>
                                        <li><a href="#">Change Log</a></li>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"><span>Multi Level</span><span class="menu-arrow"></span></a>
                                            <ul>
                                                <li><a href="javascript:void(0);">Multilevel 1</a></li>
                                                <li class="submenu submenu-two">
                                                    <a href="javascript:void(0);">Multilevel 2<span class="menu-arrow inside-submenu  absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span></a>
                                                    <ul>
                                                        <li><a href="javascript:void(0);">Multilevel 2.1</a></li>
                                                        <li class="submenu submenu-two submenu-three">
                                                            <a href="javascript:void(0);">Multilevel 2.2<span class="menu-arrow inside-submenu inside-submenu-two"></span></a>
                                                            <ul>
                                                                <li><a href="javascript:void(0);">Multilevel 2.2.1</a></li>
                                                                <li><a href="javascript:void(0);">Multilevel 2.2.2</a></li>
                                                            </ul>
                                                        </li>
                                                    </ul>
                                                </li>
                                                <li><a href="javascript:void(0);">Multilevel 3</a></li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Horizontal Single -->

                <div class="flex items-center">
                    <div class="me-1">
                        <a href="#" class="btn btn-menubar size-[30px] flex items-center justify-center text-gray-500 rounded-[5px] hover:text-gray-900 hover:bg-dark-transparent btnFullscreen">
                            <i class="ti ti-maximize"></i>
                        </a>
                    </div>
                    <div class="me-1">
                        <button data-dropdown-toggle="app-dropdown" class="btn btn-menubar size-[30px] flex items-center justify-center text-gray-500 rounded-[5px] hover:text-gray-900 hover:bg-dark-transparent focus:bg-dark-transparent focus:text-gray-900" type="button">
                            <i class="ti ti-layout-grid-remove"></i>
                        </button>
                        <div id="app-dropdown" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-[5px] shadow w-[200px]">
                            <div class="border-b border px-[20px] py-4">
                                <h4>Applications</h4>
                            </div>
                            <div class="border-b border p-[20px]">
                                <a href="calendar.html" class="flex items-center text-gray-900 hover:text-primary pb-2">
                                    <span class="inline-flex items-center justify-center size-8 rounded bg-dark-transparent me-2"><i class="ti ti-calendar text-gray-900"></i></span>Calendar
                                </a>
                                <a href="todo.html" class="flex items-center text-gray-900 hover:text-primary py-2">
                                    <span class="inline-flex items-center justify-center size-8 rounded bg-dark-transparent me-2"><i class="ti ti-subtask text-gray-900"></i></span>To Do
                                </a>
                                <a href="notes.html" class="flex items-center text-gray-900 hover:text-primary py-2">
                                    <span class="inline-flex items-center justify-center size-8 rounded  bg-dark-transparent me-2"><i class="ti ti-notes text-gray-900"></i></span>Notes
                                </a>
                                <a href="file-manager.html" class="flex items-center text-gray-900 hover:text-primary py-2">
                                    <span class="inline-flex items-center justify-center size-8 rounded bg-dark-transparent me-2"><i class="ti ti-folder text-gray-900"></i></span>File Manager
                                </a>
                                <a href="kanban-view.html" class="flex items-center text-gray-900 hover:text-primary py-2">
                                    <span class="inline-flex items-center justify-center size-8 rounded bg-dark-transparent me-2"><i class="ti ti-layout-kanban text-gray-900"></i></span>Kanban
                                </a>
                                <a href="invoices.html" class="flex items-center text-gray-900 hover:text-primary py-2 pb-0">
                                    <span class="inline-flex items-center justify-center size-8 rounded bg-dark-transparent me-2"><i class="ti ti-file-invoice text-gray-900"></i></span>Invoices
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="me-1">
                        <a href="chat.html" class="btn btn-menubar size-[30px] flex items-center justify-center text-gray-500 rounded-[5px] hover:text-gray-900 hover:bg-dark-transparent relative">
                            <i class="ti ti-brand-hipchat"></i>
                            <span class="right-1 top-0.5 absolute w-[10px] h-[10px] text-[6px] flex items-center justify-center bg-info text-white  rounded-full">5</span>
                        </a>
                    </div>
                    <div class="me-1">
                        <a href="email.html" class="btn btn-menubar size-[30px] flex items-center justify-center text-gray-500 rounded-[5px] hover:text-gray-900 hover:bg-dark-transparent">
                            <i class="ti ti-mail"></i>
                        </a>
                    </div>
                    <div class="me-1 notification_item">
                        <button data-dropdown-toggle="noti-dropdown" class="btn btn-menubar size-[30px] flex items-center justify-center text-gray-500 rounded-[5px] hover:text-gray-900 hover:bg-dark-transparent focus:bg-dark-transparent focus:text-gray-900 relative" type="button">
                            <i class="ti ti-bell"></i>
                            <span class="right-[5px] top-[5px] absolute w-[6px] h-[6px] text-[6px] flex items-center justify-center bg-danger text-white rounded-full notification-status-dot"></span>
                        </button>
                        <div id="noti-dropdown" class="z-10 hidden bg-white rounded-[5px] shadow p-6 w-55 dark:bg-gray-700">
                            <div class="flex items-center justify-between border-b border-borderColor p-0 pb-4 mb-4">
                                <h4 class="notification-title">Notifications (2)</h4>
                                <div class="flex items-center">
                                    <a href="#" class="text-primary text-nowrap text-[15px] me-3 lh-1">Mark all as read</a>
                                    <button id="doubleDropdownButton" data-dropdown-toggle="doubleDropdown" data-dropdown-placement="right-start" type="button" class="flex items-center w-full text-gray-900 hover:text-primary"><i class="ti ti-calendar-due me-1"></i>Today</button>
                                    <div id="doubleDropdown" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44">
                                        <ul class="p-2 text-sm text-gray-900" aria-labelledby="doubleDropdownButton">
                                            <li>
                                                <a href="javascript:void(0);" class="rounded p-2 flex items-center hover:bg-primary-transparent hover:text-primary text-gray-900">This Week</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="rounded p-2 flex items-center hover:bg-primary-transparent hover:text-primary text-gray-900">Last Week</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="text-gray-900 noti-content scroll-smooth h-[270px] overflow-y-auto">
                                <div class="border-b border-borderColor mb-4 pb-4">
                                    <a href="activity.html">
                                        <div class="flex">
													<span class="flex items-center size-[45px] me-2 shrink-0">
														<img src="assets/img/profiles/avatar-27.jpg" alt="Profile" class="rounded">
													</span>
                                            <div class="grow">
                                                <p class="mb-1"><span class="text-title font-semibold">Shawn</span> performance in Math is below the threshold.</p>
                                                <span>Just Now</span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="border-b border-borderColor mb-4 pb-4">
                                    <a href="activity.html" class="pb-0">
                                        <div class="flex">
													<span class="flex items-center size-[45px] me-2 shrink-0">
														<img src="assets/img/profiles/avatar-23.jpg" alt="Profile" class="rounded">
													</span>
                                            <div class="grow">
                                                <p class="mb-1"><span class="text-title font-semibold">Sylvia</span> added appointment on 02:00 PM</p>
                                                <span>10 mins ago</span>
                                                <div
                                                    class="flex justify-start items-center mt-1">
                                                    <span class="btn bg-light border border-light text-gray-900 text-center py-1 px-2 hover:bg-light-900 hover:text-gray-900 text-xs leading-normal me-2">Deny</span>
                                                    <span class="btn bg-primary border border-primary text-white text-center py-1 px-2 hover:bg-primary-900 hover:text-white text-xs leading-normal">Approve</span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="border-b border-borderColor mb-4 pb-4">
                                    <a href="activity.html">
                                        <div class="flex">
													<span class="flex items-center size-[45px] me-2 shrink-0">
														<img src="assets/img/profiles/avatar-25.jpg" alt="Profile" class="rounded">
													</span>
                                            <div class="grow">
                                                <p class="mb-1">New student record <span class="text-title font-semibold"> George</span> is created by <span class="text-title font-semibold">Teressa</span></p>
                                                <span>2 hrs ago</span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="border-0 mb-4 pb-0">
                                    <a href="activity.html">
                                        <div class="flex">
													<span class="flex items-center size-[45px] me-2 shrink-0">
														<img src="assets/img/profiles/avatar-01.jpg" alt="Profile" class="rounded">
													</span>
                                            <div class="grow">
                                                <p class="mb-1">A new teacher record for <span class="text-title font-semibold">Elisa</span> </p>
                                                <span>09:45 AM</span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <div class="flex p-0 gap-2">
                                <a href="#" class="btn bg-light border border-light text-gray-900 text-center hover:bg-light-900 hover:text-gray-900 w-full">Cancel</a>
                                <a href="activity.html" class="btn bg-primary border border-primary text-white text-center hover:bg-primary-900 hover:text-white w-full">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="me-1">
                        <button data-dropdown-toggle="profile-dropdown" class="flex items-center justify-center" type="button">
									<span class="relative">
										<img src="assets/img/profiles/avatar-12.jpg" alt="Img" class="size-6 rounded-full">
										<span class="right-0 bottom-0 absolute  w-2 h-2 bg-success border-2 border-white  rounded-full"></span>
									</span>
                        </button>
                        <div id="profile-dropdown" class="z-10 hidden bg-white divide-y divide-borderColor rounded-[5px] shadow w-55">
                            <div class="px-[20px] py-4">
                                <div class="flex items-center">
												<span class="size-[45px] me-2">
													<img src="assets/img/profiles/avatar-12.jpg" alt="img" class="rounded-full border-2 border-gray-100">
												</span>
                                    <div>
                                        <h5 class="mb-0">Kevin Larry</h5>
                                        <p class="text-xs leading-normal text-gray-900 font-medium mb-0">warren@example.com</p>
                                    </div>
                                </div>
                            </div>
                            <ul class="p-[20px]">
                                <li>
                                    <a class="inline-flex items-center text-gray-900 py-2 hover:text-primary" href="profile.html">
                                        <i class="ti ti-user-circle me-1"></i>My Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="inline-flex items-center text-gray-900 py-2 hover:text-primary" href="bussiness-settings.html">
                                        <i class="ti ti-settings me-1"></i>Settings
                                    </a>
                                </li>
                                <li>
                                    <a class="inline-flex items-center text-gray-900 py-2 hover:text-primary" href="security-settings.html">
                                        <i class="ti ti-status-change me-1"></i>Status
                                    </a>
                                </li>
                                <li>
                                    <a class="inline-flex items-center text-gray-900 py-2 hover:text-primary" href="profile-settings.html">
                                        <i class="ti ti-circle-arrow-up me-1"></i>My Account
                                    </a>
                                </li>
                                <li>
                                    <a class="inline-flex items-center text-gray-900 py-2 hover:text-primary" href="knowledgebase.html">
                                        <i class="ti ti-question-mark me-1"></i>Knowledge Base
                                    </a>
                                </li>
                            </ul>
                            <div class="px-[20px] py-4">
                                <a class="inline-flex items-center text-gray-900 py-2 hover:text-primary" href="login.html">
                                    <i class="ti ti-login me-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="dropdown mobile-user-menu">
            <a href="javascript:void(0);" data-dropdown-toggle="mobile-dropdown"><i class="fa fa-ellipsis-v"></i></a>
            <ul id="mobile-dropdown" class="hidden p-1 border rounded bg-white shadow-lg w-40 z-[1]">
                <li><a class="rounded p-2 flex items-center hover:bg-primary-transparent hover:text-primary text-gray-900" href="profile.html">My Profile</a></li>
                <li><a class="rounded p-2 flex items-center hover:bg-primary-transparent hover:text-primary text-gray-900" href="profile-settings.html">Settings</a></li>
                <li><a class="rounded p-2 flex items-center hover:bg-primary-transparent hover:text-primary text-gray-900" href="login.html">Logout</a></li>
            </ul>
        </div>
        <!-- /Mobile Menu -->

    </div>

</div>
<!-- /Header -->
