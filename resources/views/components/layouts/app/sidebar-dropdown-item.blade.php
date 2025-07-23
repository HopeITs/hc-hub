@props([
    'title' => 'Dropdown Menu',
    'icon' => 'ti ti-layout-grid-add'
])
<li class="submenu mb-[5px]">
    <a href="javascript:void(0);" class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]">
        <i class="{{$icon}}"></i><span class="ms-2">{{$title}}</span>
        <span class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
    </a>
    <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
        <li><a href="chat.html" class="relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0">Chat</a></li>

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
