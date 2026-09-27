<x-slot:title>{{ __('recruitment.create') }}</x-slot:title>

<div class="mx-auto max-w-5xl space-y-6">
    <a class="text-primary hover:underline" href="{{ route('recruitment.requests.index') }}">{{ __('recruitment.back') }}</a>
    <header>
        <h1 class="text-h2 font-semibold text-neutral-900">{{ __('recruitment.create') }}</h1>
        <p class="mt-2 text-secondary-medium">{{ __('recruitment.create_description') }}</p>
    </header>
    <form wire:submit="save" class="space-y-6">
        @include('livewire.recruitment.requests.partials.request-fields')
        <footer class="flex flex-wrap items-center justify-between gap-4 rounded-surface border border-neutral-200 bg-white p-5">
            <p class="text-sm text-secondary-medium">{{ __('recruitment.draft_help') }}</p>
            <x-primary-button type="submit" wire:target="save">{{ __('recruitment.save_draft') }}</x-primary-button>
        </footer>
    </form>
</div>
