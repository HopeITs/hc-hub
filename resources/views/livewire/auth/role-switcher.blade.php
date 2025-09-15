<div class="space-y-2">
    <div class="text-xs">
        <span class="px-2 py-1 rounded bg-gray-100">
            {{ $activeRoleName ?? __('No active role') }}
        </span>
    </div>

    @if(count($inactive) > 0)
        <div class="text-sm opacity-70 mb-1">{{ __('Switch Role') }}</div>
        <div class="flex flex-wrap gap-2">
            @foreach($inactive as $r)
                <button
                    wire:click="switchTo({{ $r['id'] }})"
                    type="button"
                    class="px-3 py-1 rounded border hover:bg-gray-50"
                >
                    {{ $r['name'] }}
                </button>
            @endforeach
        </div>
    @endif
</div>
