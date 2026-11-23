<?php

use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Models\PolicyDocument;
use Modules\Hr\Support\Employees\HrAccess;
use Modules\Hr\Support\Journeys\PolicyAcknowledgements;

/*
 * HR chooses which policy documents a new volunteer must read and agree to,
 * and of which kind (a policy to agree to, or a document made by another
 * team, such as the Media guideline, that is only reviewed). HR roles and the
 * administrators only, checked on mount and on every action.
 */
new #[Layout('layouts::desk'), Title('hr_onboarding.required_policies.title')] class extends Component {
    public function mount(): void
    {
        $this->authorizeHr();
    }

    /** @return Collection<int, PolicyDocument> */
    #[Computed]
    public function documents(): Collection
    {
        $this->authorizeHr();

        return PolicyDocument::query()->orderBy('title')->get();
    }

    public function setRequired(int $documentId, bool $required, PolicyAcknowledgements $policies): void
    {
        $this->authorizeHr();

        $policies->setRequired(PolicyDocument::query()->findOrFail($documentId), $required);

        unset($this->documents);
        Flux::toast(variant: 'success', text: __('hr_onboarding.required_policies.saved'));
    }

    /** policy (read and agreed) or review (made by another team, only reviewed). */
    public function setKind(int $documentId, string $kind, PolicyAcknowledgements $policies): void
    {
        $this->authorizeHr();

        $policies->setKind(PolicyDocument::query()->findOrFail($documentId), $kind);

        unset($this->documents);
        Flux::toast(variant: 'success', text: __('hr_onboarding.required_policies.saved'));
    }

    private function authorizeHr(): void
    {
        $user = Auth::user();

        abort_unless($user !== null && $user->can('employees.update') && (HrAccess::isHr($user) || HrAccess::isSuperAdmin($user)), 403);
    }
};
?>

@php
    $documents = $this->documents;
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<section class="flex flex-col gap-5" data-required-policies>
    <x-ui.detail-header :breadcrumbs="[['label' => __('hr_onboarding.required_policies.title')]]" :title="__('hr_onboarding.required_policies.title')" :subtitle="__('hr_onboarding.required_policies.lead')" />

    <x-ui.panel :title="__('hr_onboarding.required_policies.title')" :count="$documents->count()">
        @if ($documents->isEmpty())
            <p class="p-4 text-sm {{ $muted }}">{{ __('hr_onboarding.required_policies.none') }}</p>
        @else
            <ul class="divide-y divide-secondary-soft dark:divide-line">
                @foreach ($documents as $document)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3" wire:key="document-{{ $document->id }}" data-policy-document="{{ $document->id }}">
                        <span class="min-w-0 font-semibold" dir="auto">{{ $document->title }}</span>
                        <span class="flex flex-wrap items-center gap-3">
                            <flux:checkbox :checked="$document->required_for_onboarding" wire:click="setRequired({{ $document->id }}, {{ $document->required_for_onboarding ? 'false' : 'true' }})" :label="__('hr_onboarding.required_policies.required')" />
                            @if ($document->required_for_onboarding)
                                @foreach (\Modules\Hr\Models\PolicyDocument::KINDS as $kind)
                                    <x-ui.button size="sm" :tone="$document->onboarding_kind === $kind ? 'primary' : 'secondary'" wire:click="setKind({{ $document->id }}, '{{ $kind }}')" data-test="kind-{{ $kind }}">{{ __('hr_onboarding.required_policies.kinds.'.$kind) }}</x-ui.button>
                                @endforeach
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.panel>
</section>
