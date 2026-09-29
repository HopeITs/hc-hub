<x-slot:title>{{ __('recruitment.create') }}</x-slot:title>

<div class="mx-auto max-w-5xl space-y-6">
    <a class="text-primary hover:underline" href="{{ route('recruitment.requests.index') }}">{{ __('recruitment.back') }}</a>
    <header>
        <h1 class="text-h2 font-semibold text-neutral-900">{{ __('recruitment.create') }}</h1>
        <p class="mt-2 text-secondary-medium">{{ __('recruitment.create_description') }}</p>
    </header>
    <form wire:submit="save" class="space-y-6">
        @if (($editing ?? false) && filled($changeRequest ?? null))
            <section class="rounded-surface border border-neutral-200 bg-white p-5">
                <div>
                    <div>
                        <p class="text-body font-semibold text-neutral-900">{{ __('recruitment.changes_requested_edit_title') }}</p>
                        <p class="mt-1 text-small text-secondary-medium">{{ __('recruitment.changes_requested_edit_hint') }}</p>
                    </div>
                </div>
                @if (filled($changeRequest['comment'] ?? null))
                    <div class="mt-4 rounded-control border border-neutral-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-secondary-medium">{{ __('recruitment.approver_comment') }}</p>
                        <p class="mt-2 text-body text-neutral-900">{{ $changeRequest['comment'] }}</p>
                    </div>
                @endif
            </section>
        @endif

        @include('livewire.recruitment.requests.partials.request-fields')
        <footer class="flex flex-wrap items-center justify-between gap-4 rounded-surface border border-neutral-200 bg-white p-5">
            <p class="text-sm text-secondary-medium">{{ __('recruitment.draft_help') }}</p>
            <x-primary-button type="submit" wire:target="save">{{ __('recruitment.save_draft') }}</x-primary-button>
        </footer>
    </form>
</div>
