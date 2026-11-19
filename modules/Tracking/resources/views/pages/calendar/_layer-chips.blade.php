{{--
    The chips other modules add to one day of calendar/index (leave,
    holidays…), from ShowsCalendarLayers::layerItemsByDay(). Included twice
    per day cell, around _item-chips: once for the layers that lead the day,
    once for the rest.

    @var \Carbon\CarbonImmutable $day
    @var array<string, list<array{layer: string, icon: string, leads: bool, id: string, title: string, context: string|null, href: string|null}>> $items
    @var bool $leads
--}}
@foreach ($items[$day->toDateString()] ?? [] as $item)
    @continue($item['leads'] !== $leads)
    @php $chipLabel = $item['context'] === null ? $item['title'] : $item['title'].' · '.$item['context']; @endphp
    <div
        wire:key="calendar-layer-{{ $item['layer'] }}-{{ $item['id'] }}-{{ $day->toDateString() }}"
        class="flex items-center gap-1 rounded border border-dashed border-secondary-light px-1.5 py-0.5 text-[10px] text-secondary-dark dark:border-line dark:text-ink-muted"
        title="{{ $chipLabel }}"
        data-test="calendar-layer-{{ $item['layer'] }}-{{ $item['id'] }}-{{ $day->toDateString() }}"
    >
        <flux:icon :name="$item['icon']" variant="micro" class="size-3 shrink-0" />
        @if ($item['href'] === null)
            <span class="min-w-0 flex-1 truncate font-medium">{{ $chipLabel }}</span>
        @else
            <a href="{{ $item['href'] }}" wire:navigate x-on:click.stop class="min-w-0 flex-1 truncate font-medium hover:underline">{{ $chipLabel }}</a>
        @endif
    </div>
@endforeach
