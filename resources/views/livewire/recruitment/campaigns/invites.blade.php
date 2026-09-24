<x-slot:title>{{ __('recruitment-applicants.invites_title') }}</x-slot:title>

<div class="space-y-6">
    <div>
        <h1 class="text-h2 font-semibold text-neutral-900">
            {{ __('recruitment-applicants.invites_title') }}
        </h1>
        <p class="mt-2 text-secondary-medium">
            {{ __('recruitment-applicants.invites_description') }}
        </p>
    </div>

    @if (session('recruitment_invite_added'))
        <p role="status" class="rounded-control border border-green-200 bg-green-50 p-4 text-green-800">
            {{ session('recruitment_invite_added') }}
        </p>
    @endif

    <form wire:submit="add" class="flex flex-wrap items-end gap-4 rounded-control border border-neutral-200 bg-white p-4 shadow-e1">
        <div class="min-w-[220px]">
            <x-forms.input-label for="full_name" required :value="__('recruitment-applicants.invite_full_name')" />
            <x-forms.input id="full_name" model="full_name" required />
            <x-forms.input-error field="full_name" input-id="full_name" />
        </div>

        <div class="min-w-[260px]">
            <x-forms.input-label for="email" required :value="__('recruitment-applicants.invite_email')" />
            <x-forms.input id="email" type="email" model="email" required />
            <x-forms.input-error field="email" input-id="email" />
        </div>

        <x-primary-button type="submit">
            {{ __('recruitment-applicants.invite_add') }}
        </x-primary-button>
    </form>

    <livewire:recruitment.campaigns.invite-table :campaign-id="$campaign->id" />
</div>
