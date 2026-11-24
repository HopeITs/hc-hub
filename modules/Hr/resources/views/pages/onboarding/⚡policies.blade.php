<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Models\PolicyDocument;
use Modules\Hr\Support\Journeys\PolicyAcknowledgements;

/*
 * The policies a new volunteer reads and agrees to, and the documents made by
 * another team that they only review, from their own account. It only ever
 * reads and writes for the signed-in user's own employee, so one volunteer
 * cannot acknowledge for another.
 */
new #[Layout('layouts::desk'), Title('hr_onboarding.policies.title')] class extends Component {
    public function mount(): void
    {
        abort_unless(Auth::user()?->employee_id !== null, 403);
    }

    /** @return array<string, array{required: \Illuminate\Support\Collection, acknowledged: \Illuminate\Support\Collection, pending: \Illuminate\Support\Collection, complete: bool}> */
    #[Computed]
    public function sections(): array
    {
        $employee = Auth::user()->employee;
        abort_unless($employee !== null, 403);

        $policies = app(PolicyAcknowledgements::class);

        return [
            PolicyDocument::KIND_POLICY => $policies->status($employee, PolicyDocument::KIND_POLICY),
            PolicyDocument::KIND_REVIEW => $policies->status($employee, PolicyDocument::KIND_REVIEW),
        ];
    }

    public function acknowledge(int $documentId, PolicyAcknowledgements $policies): void
    {
        $employee = Auth::user()?->employee;
        abort_unless($employee !== null, 403);

        $done = $policies->acknowledge($employee, PolicyDocument::query()->findOrFail($documentId));

        unset($this->sections);
        Flux::toast(variant: $done ? 'success' : 'warning', text: __($done ? 'hr_onboarding.policies.acknowledged' : 'hr_journeys.nothing_changed'));
    }
};
?>

@php
    $sections = $this->sections;
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<section class="flex flex-col gap-5" data-onboarding-policies>
    <x-ui.detail-header :breadcrumbs="[['label' => __('hr_onboarding.policies.title')]]" :title="__('hr_onboarding.policies.title')" :subtitle="__('hr_onboarding.policies.lead')" />

    @foreach ($sections as $kind => $status)
        <x-ui.panel :title="__('hr_onboarding.policies.sections.'.$kind)" data-policies-section="{{ $kind }}">
            @if ($status['required']->isEmpty())
                <p class="p-4 text-sm {{ $muted }}">{{ __('hr_onboarding.policies.none') }}</p>
            @else
                <ul class="divide-y divide-secondary-soft dark:divide-line">
                    @foreach ($status['required'] as $document)
                        @php($agreed = $status['acknowledged']->contains($document->id))
                        <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3" wire:key="policy-{{ $document->id }}">
                            <div class="min-w-0">
                                <p class="font-semibold" dir="auto">{{ $document->title }}</p>
                                @if ($document->source_url)
                                    <a class="text-xs underline" href="{{ $document->source_url }}" target="_blank" rel="noopener noreferrer">{{ __('hr_onboarding.policies.open_source') }}</a>
                                @endif
                            </div>
                            @if ($agreed)
                                <span class="text-sm font-semibold text-status-good">{{ __('hr_onboarding.policies.agreed') }}</span>
                            @else
                                <x-ui.button tone="primary" size="sm" icon="check" wire:click="acknowledge({{ $document->id }})" data-test="acknowledge-policy">{{ __('hr_onboarding.policies.acknowledge.'.$kind) }}</x-ui.button>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.panel>
    @endforeach
</section>
