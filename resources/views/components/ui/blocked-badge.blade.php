@props(['project'])

{{-- "Blocked" where the edit button would be, for a project whose owner
     rejected being put in charge, as a critical x-ui.chip (plan task 2.4).
     Someone who may unblock gets the same chip inside a button that opens the
     unblock dialog. Needs ownerAssignment loaded. --}}
@if ($project->isOwnerRejected())
    @can('unblock', $project->ownerAssignment)
        <button
            type="button"
            wire:click="openUnblockModal({{ $project->id }})"
            class="inline-flex shrink-0 cursor-pointer touch-manipulation items-center rounded-full transition hover:brightness-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary-medium"
            title="{{ __('projects.unblock_hint') }}"
            aria-label="{{ __('projects.unblock_name', ['name' => $project->name]) }}"
            data-test="blocked-badge-unblock"
        >
            <x-ui.chip tone="critical" icon="lock-closed">{{ __('projects.blocked') }}</x-ui.chip>
        </button>
    @else
        <x-ui.chip tone="critical" icon="lock-closed" class="shrink-0" title="{{ __('projects.blocked_hint') }}" data-test="blocked-badge">{{ __('projects.blocked') }}</x-ui.chip>
    @endcan
@endif
