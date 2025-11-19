<!-- Notification Button -->
<button id="dropdownNotificationButton"
        data-dropdown-toggle="dropdownNotification"
        type="button"
        class="relative cursor-pointer inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">

    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 14 20">
        <path d="M12.133 10.632v-1.8A5.406 5.406 0 0 0 7.979 3.57.946.946 0 0 0 8 3.464V1.1a1 1 0 0 0-2 0v2.364a.946.946 0 0 0 .021.106 5.406 5.406 0 0 0-4.154 5.262v1.8C1.867 13.018 0 13.614 0 14.807 0 15.4 0 16 .538 16h12.924C14 16 14 15.4 14 14.807c0-1.193-1.867-1.789-1.867-4.175ZM3.823 17a3.453 3.453 0 0 0 6.354 0H3.823Z"/>
    </svg>

    <!-- Unread Counter -->
    <div class="absolute -top-2 start-3">
        <livewire:notifications.counter wire:poll.10s />
    </div>
</button>

<!-- Dropdown Panel -->
<div id="dropdownNotification"
     aria-labelledby="dropdownNotificationButton"
     class="z-20 hidden w-full max-w-sm bg-white rounded-lg shadow-sm divide-y divide-gray-100 dark:bg-gray-800 dark:divide-gray-700">

    <!-- Header -->
    <div class="px-4 py-2 text-center font-medium text-gray-700 bg-gray-50 rounded-t-lg dark:bg-gray-800 dark:text-white">
        Notifications
    </div>

    <!-- Livewire Notifications Dropdown (shows latest unread) -->
    <div class="max-h-80 overflow-y-auto">
        <livewire:notifications.dropdown wire:poll.10s />
    </div>

    <!-- Footer -->
    <a href="{{ route('notifications.index') }}"
       class="block py-2 text-sm font-medium text-center text-gray-900 bg-gray-50 rounded-b-lg hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-white">
        <div class="inline-flex items-center">
            <svg class="w-4 h-4 me-2 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 14">
                <path d="M10 0C4.612 0 0 5.336 0 7c0 1.742 3.546 7 10 7 6.454 0 10-5.258 10-7 0-1.664-4.612-7-10-7Zm0 10a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/>
            </svg>
            View all
        </div>
    </a>
</div>
