<div>
    @forelse($notifications as $note)

        @php
            $isUnread = is_null($note->read_at);
        @endphp

        <div
            class="p-3 border-b hover:bg-neutral-100 dark:hover:bg-neutral-800 transition
                   @if($isUnread)
                       bg-primary-50 border-s-4 border-primary-500
                   @endif">

            <div class="flex items-start gap-2">

                @if($isUnread)
                    <span class="mt-1 inline-block w-2 h-2 bg-primary-500 rounded-full"></span>
                @endif

                <a class="flex-1" @if(array_key_exists('url' ,$note->data)) href="{{$note->data['url']}}"@endif  @if ($isUnread) wire:click="markAsRead('{{ $note->id }}')"
                    @endif>

                    <p class="text-sm font-semibold
                              @if($isUnread)
                                  text-neutral-900 dark:text-white
                              @else
                                  text-neutral-800 dark:text-neutral-200
                              @endif">
                        {{ $note->data['title'][app()->getLocale()] ?? 'Notification' }}
                    </p>

                    <p class="text-xs mt-1
                              @if($isUnread)
                                  text-neutral-700 dark:text-neutral-300
                              @else
                                  text-neutral-600 dark:text-neutral-400
                              @endif">
                        {{ $note->data['body'][app()->getLocale()] ?? '' }}
                    </p>


{{--                    @if ($isUnread)--}}
{{--                        <button wire:click="markAsRead('{{ $note->id }}')"--}}
{{--                                class="text-xs text-primary-600 hover:underline ms-2">--}}
{{--                            @lang('general.mark-as-read')--}}
{{--                        </button>--}}
{{--                    @endif--}}


                </a>

            </div>

        </div>

    @empty
        <div class="p-4 text-center text-neutral-500 dark:text-neutral-400">
            @lang('general.no-notifications')
        </div>
    @endforelse
</div>
