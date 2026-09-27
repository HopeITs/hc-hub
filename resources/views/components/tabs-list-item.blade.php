@props(['active' => false, 'activeTab' => 'all', 'action' => 'setActiveTab'])

<li class="me-2">
    <button type="button"
        {{ $attributes->merge([
            'class' => $active
                ? 'inline-block cursor-pointer rounded-control bg-primary px-4 py-3 text-white'
                : 'inline-block cursor-pointer rounded-control px-4 py-3 hover:bg-neutral-100 hover:text-neutral-900',
        ]) }}
        wire:click="{{ $action }}('{{ $activeTab }}')">
        {{ $slot }}
    </button>
</li>
