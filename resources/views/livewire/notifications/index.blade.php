<div wire:poll.5s class="space-y-4">
    <h2 class="text-xl font-semibold text-gray-800">Notifications</h2>

    @forelse ($notifications as $notification)
        <div class="flex justify-between items-center bg-gray-100 px-4 py-3 rounded shadow-sm">
            <div class="text-gray-700">
                <div class="font-medium">{{ $notification->data['title'] ?? 'Notification' }}</div>
                <div class="text-sm">{{ $notification->data['body'] ?? '' }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
            </div>

            @if (isset($notification->data['url']))
                {{-- <a href="{{ $notification->data['url'] }}" class="text-sm text-blue-600 hover:underline">View</a> --}}
            @endif

            @if (is_null($notification->read_at))
                <button wire:click="markAsRead('{{ $notification->id }}')" class="text-sm text-primary-600 hover:text-primary-800 hover:underline">Mark as read</button>
            @endif
        </div>
    @empty
        <div class="text-gray-500">No notifications.</div>
    @endforelse
</div>
