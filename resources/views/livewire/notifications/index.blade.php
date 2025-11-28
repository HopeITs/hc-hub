<div class="space-y-4">
    <div class="flex justify-between">
        <h2 class="text-xl font-semibold text-gray-800">@lang('general.notifications')</h2>
        <button wire:click="markAllRead()" class="text-sm text-primary-600 hover:text-primary-800 hover:underline">@lang('general.mark-as-read')</button>
    </div>


    @forelse ($notifications as $notification)
        <div class="flex justify-between items-center bg-white px-4 py-3 rounded shadow-sm">
            <div class="text-gray-700">
                <div class="font-medium">{{ $notification->data['title'][app()->getLocale()] ?? 'Notification' }}</div>
                <div class="text-sm">{{ $notification->data['body'][app()->getLocale()] ?? '' }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
            </div>

            @if (is_null($notification->read_at))
                <button wire:click="markAsRead('{{ $notification->id }}')" class="text-sm text-primary-600 hover:text-primary-800 hover:underline">@lang('general.mark-as-read')</button>
            @endif
            <a href="{{$notification->data['url']}}" @if (is_null($notification->read_at)) wire:click="markAsRead('{{ $notification->id }}')"
               @endif
               class="text-xs text-primary-600 hover:underline ml-2">
                @lang('general.show-more')
            </a>

        </div>
    @empty
        <div class="text-gray-500">@lang('general.no-notifications')</div>
    @endforelse
</div>
