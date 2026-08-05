@props(['title' , 'active' => false ,'activeTab'])
<li>
    <button {{$attributes->merge([
    'class' => $active ? 'w-full flex items-center p-3 rounded-control bg-primary text-white ' : 'w-full flex items-center p-3 rounded-control bg-neutral-50 hover:bg-neutral-100 disabled:hover:bg-neutral-50 disabled:text-secondary-light'
])
}} wire:click="setActiveTab('{{$activeTab}}')"

    >
        {{$slot}}
        <span class="ms-2 font-semibold">{{$title}}</span>
    </button>
</li>
