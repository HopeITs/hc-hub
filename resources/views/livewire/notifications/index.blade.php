<div class="space-y-4 w-8/12 mx-auto">
    <div class="flex justify-between">
        <h2 class="text-xl font-semibold text-gray-800">@lang('general.notifications')</h2>
        <button type="button" wire:click="markAllRead"
                class="text-sm p-2 bg-white border-primary border-1 text-primary rounded-md hover:text-white hover:bg-primary transition ease-in duration-100">@lang('general.mark-all-read')</button>
    </div>


    @forelse ($notifications as $notification)
        <div class="flex justify-between items-center bg-white px-4 py-3 rounded shadow-sm
         {{is_null($notification->read_at) ? ' bg-primary-50 border-s-4 border-primary-500 ' : '' }}">
            <div class="text-gray-700">
                <div class="font-medium">{{ $notification->data['title'][app()->getLocale()] ?? 'Notification' }}</div>
                <div class="text-sm">{{ $notification->data['body'][app()->getLocale()] ?? '' }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
            </div>
            <div>
                @if (is_null($notification->read_at) && !array_key_exists('url' , $notification->data))
                    <button type="button" wire:click="markAsRead('{{ $notification->id }}')"
                            class="text-xs text-primary hover:underline mx-2">@lang('general.mark-as-read')</button>
                @endif
                @if(array_key_exists('url' , $notification->data))
                    <a href="{{$notification->data['url']}}"
                       @if (is_null($notification->read_at)) wire:click="markAsRead('{{ $notification->id }}')"
                       @endif
                       class="text-xs text-primary  hover:underline ms-2">
                        @lang('general.show-more')
                    </a>
                @endif
            </div>


        </div>
    @empty
        <div class="text-gray-500">@lang('general.no-notifications')</div>
    @endforelse
    {{$notifications->links()}}
</div>
