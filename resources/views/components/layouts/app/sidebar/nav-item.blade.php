@props(['active' => false])


<li x-show="!sidebarCollapsed || open">
    <a {{ $attributes->merge([
        'class' => 'nav-press group relative flex items-center w-full ps-6 py-2 text-small font-light
         border-s-2 rounded-e-control ' . ($active
                ? ' text-primary font-semibold border-primary bg-selection'
                : ' text-secondary-dark border-secondary-soft hover:text-primary hover:bg-black/[0.03]')
    ]) }}>

            {{$slot}}


    </a>
</li>


