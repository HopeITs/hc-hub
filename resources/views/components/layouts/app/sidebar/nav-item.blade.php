{{-- Sidebar links swap pages through wire:navigate instead of a full reload. --}}
@props(['active' => false])


<li x-show="!sidebarCollapsed || open">
    <a wire:navigate {{ $attributes->merge([
        'class' => 'nav-press group relative flex items-center w-full ps-6 py-2 text-small font-semibold
         border-s-2 border-secondary-soft rounded-e-control ' . ($active
                ? ' text-neutral-800 font-semibold before:content-[\'\'] before:absolute before:-start-0.5 before:top-1/2 before:-translate-y-1/2 before:h-5 before:w-[3px] before:rounded-full before:bg-primary'
                : ' text-secondary-medium hover:text-neutral-800 hover:bg-neutral-50'),
        'aria-current' => $active ? 'page' : null,
    ]) }}>

            {{$slot}}


    </a>
</li>


