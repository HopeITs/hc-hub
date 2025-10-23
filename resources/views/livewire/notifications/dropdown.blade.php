<div wire:poll.10s>
    <div class="relative">
        <button>
            🔔
            @if($notifications->count())
                <span class="absolute top-0 right-0 h-2 w-2 bg-red-600 rounded-full"></span>
            @endif
        </button>

        <div class="absolute mt-2 w-64 bg-white shadow rounded">
            @forelse($notifications as $note)
                <div class="p-2 border-b">
                    <p class="text-sm font-medium">{{ $note->data['title'] ?? 'New Notification' }}</p>
                    <p class="text-xs text-gray-600">{{ $note->data['body'] ?? '' }}</p>
                    @if(isset($note->data['url']))
                        <a href="{{ $note->data['url'] }}" class="text-xs text-blue-600 hover:underline">
                            View
                        </a>
                    @endif
                    <button wire:click="markAsRead('{{ $note->id }}')" class="block text-xs text-gray-400 mt-1">
                        Mark as read
                    </button>
                </div>
            @empty
                <div class="p-2 text-gray-500">No new notifications</div>
            @endforelse
        </div>
    </div>
</div>
