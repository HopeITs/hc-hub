{{-- Sidebar links swap pages through wire:navigate instead of a full reload.
     Current page: ink text with the brand red bar on the start edge. --}}
@props(['active' => false, 'favourite' => null])


<li x-show="!sidebarCollapsed || open" class="relative group/item">
    <a wire:navigate {{ $attributes->merge([
        'class' => 'nav-press group relative flex items-center w-full min-h-11 ps-6 py-2 text-small font-semibold
         border-s-2 border-secondary-soft rounded-e-control focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus ' . ($active
                ? ' text-neutral-800 font-semibold before:content-[\'\'] before:absolute before:-start-0.5 before:top-1/2 before:-translate-y-1/2 before:h-5 before:w-[3px] before:rounded-full before:bg-primary'
                : ' text-secondary-medium hover:text-neutral-800 hover:bg-neutral-50'),
        'aria-current' => $active ? 'page' : null,
    ]) }}>

            {{$slot}}


    </a>

    @if ($favourite)
        <x-layouts.app.sidebar.favourite-star :id="$favourite['id']" :label="trim(strip_tags($slot))"
            :starred="$favourite['starred']" :full="$favourite['full']" :max="$favourite['max']" />
    @endif
</li>
