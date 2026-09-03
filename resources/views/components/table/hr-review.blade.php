@props([
    'reviewed' => false,
    'modelId',
])

@if ($reviewed)
    <span
        role="status"
        class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800"
    >
        {{ __('general.reviewed') }}
    </span>
@else
    <button
        type="button"
        wire:click="confirmHrReview({{ $modelId }})"
        class="inline-flex items-center rounded-lg border border-neutral-200 bg-white px-2.5 py-1 text-xs font-semibold text-primary transition hover:bg-primary hover:text-white focus:outline-none focus:ring-2 focus:ring-primary"
    >
        {{ __('general.mark-reviewed') }}
    </button>
@endif
