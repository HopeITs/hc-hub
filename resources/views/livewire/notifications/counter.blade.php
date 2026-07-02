<div wire:poll.10s="updateCount">
    @if($count > 0)
        <span
            x-data="{ pop: false }"
            x-init="pop = true; setTimeout(() => pop = false, 500)"
            @notif-arrived.window="pop = false; $nextTick(() => { pop = true; setTimeout(() => pop = false, 500) })"
            :class="pop && 'animate-notif-pop'"
            class="inline-flex items-center justify-center h-[17px] min-w-[17px] px-[4px] rounded-full
                   bg-[#be1f24] text-white text-[10px] font-bold leading-none tabular-nums
                   ring-2 ring-white shadow-sm">
            {{ $count > 99 ? '99+' : $count }}
        </span>
    @endif
</div>
