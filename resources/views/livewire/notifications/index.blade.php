<div class="w-8/12 mx-auto space-y-4">
    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">@lang('general.notifications')</h2>

        @if($notifications->getCollection()->contains(fn ($n) => is_null($n->read_at)))
            <button
                type="button"
                wire:click="markAllRead"
                class="text-sm font-medium text-gray-600 transition hover:text-gray-900 hover:underline">
                @lang('general.mark-all-read')
            </button>
        @endif
    </div>

    {{-- Card list --}}
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-[0_25px_80px_-40px_rgba(2,6,23,0.35)] ring-1 ring-black/5">
        @forelse ($notifications as $notification)
            @php $isUnread = is_null($notification->read_at); @endphp

            <a
                wire:key="note-{{ $notification->id }}"
                x-data="{ clicked: false }"
                @if(array_key_exists('url', $notification->data)) href="{{ $notification->data['url'] }}" @endif
                @if($isUnread) wire:click="markAsRead('{{ $notification->id }}')" @click="clicked = true" @endif
                x-bind:style="clicked ? 'background-color: transparent' : ''"
                class="group relative flex items-start gap-3 border-b border-gray-100 px-5 py-4 transition-colors duration-300 last:border-b-0
                       {{ $isUnread
                            ? 'bg-gray-100 hover:bg-gray-200 cursor-pointer'
                            : 'bg-white hover:bg-gray-50' }}">

                {{-- Unread accent bar --}}
                <span class="absolute inset-y-0 start-0 w-[3px] transition-all duration-300 {{ $isUnread ? 'bg-[#be1f24]' : 'bg-transparent' }}"
                      x-bind:style="clicked ? 'background-color: transparent' : ''"></span>

                {{-- Icon bubble --}}
                <span class="mt-0.5 inline-flex size-9 shrink-0 items-center justify-center rounded-full transition-colors duration-300
                             {{ $isUnread ? 'bg-gray-200 text-gray-500' : 'bg-gray-100 text-gray-400' }}">
                    <x-icons.notification class="size-4" />
                </span>

                {{-- Text --}}
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold transition-colors duration-300 {{ $isUnread ? 'text-gray-900' : 'text-gray-800' }}">
                        {{ $notification->data['title'][app()->getLocale()] ?? 'Notification' }}
                    </p>

                    <p class="mt-0.5 text-xs transition-colors duration-300 {{ $isUnread ? 'text-gray-600' : 'text-gray-500' }}">
                        {{ $notification->data['body'][app()->getLocale()] ?? '' }}
                    </p>

                    <p class="mt-1 text-[11px] text-gray-400">
                        {{ $notification->created_at?->diffForHumans() }}
                    </p>
                </div>

                {{-- Unread dot (fades + shrinks out on read) --}}
                <span class="mt-1.5 size-2 shrink-0 rounded-full bg-[#be1f24] transition-all duration-300 {{ $isUnread ? 'opacity-100 scale-100' : 'opacity-0 scale-0' }}"
                      x-bind:style="clicked ? 'opacity: 0; transform: scale(0)' : ''"></span>
            </a>
        @empty
            <div class="flex flex-col items-center justify-center gap-2 px-4 py-14 text-center">
                <span class="inline-flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-300">
                    <x-icons.notification class="size-5" />
                </span>
                <span class="text-sm text-gray-400">@lang('general.no-notifications')</span>
            </div>
        @endforelse
    </div>

    {{ $notifications->links() }}
</div>
