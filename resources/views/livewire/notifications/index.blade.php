<div class="space-y-4">
    <h2 class="text-xl font-semibold text-gray-800">Notifications</h2>

    @forelse ($notifications as $notification)
        <div class="flex justify-between items-center bg-gray-100 px-4 py-3 rounded shadow-sm">
            <div class="text-gray-700">
                {{ $notification->data['message'] ?? 'Notification' }}
                <div class="text-xs text-gray-500 mt-1">
                    {{ $notification->created_at->diffForHumans() }}
                </div>
            </div>

            <div></div>

            @if (is_null($notification->read_at))
                <button wire:click="markAsRead('{{ $notification->id }}')"
                    class="text-sm text-primary-600 hover:text-primary-800 hover:underline">
                    Mark as read
                </button>
            @endif
        </div>
    @empty
        <div class="text-gray-500">No notifications.</div>
    @endforelse

</div>
