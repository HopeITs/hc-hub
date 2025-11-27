<!-- Notification Button -->
<button id="dropdownNotificationButton"
        data-dropdown-toggle="dropdownNotification"
        type="button"
        class="relative cursor-pointer me-3 inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">

    <x-icons.notification class=" text-secondary-light hover:text-secondary-dark transition"/>
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
        @lang('general.notifications')
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
            @lang('general.view-all')
        </div>
    </a>
</div>
