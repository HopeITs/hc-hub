<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Livewire\Concerns\Recruitment\ManagesApplicantProfile;
use Modules\Hr\Livewire\Concerns\Recruitment\ReadsApplicantProfile;

/*
 * An applicant's profile: who they are, what they applied for and how far
 * each application got, the evidence (indicators, CV reading, files,
 * answers), the decisions, interviews and offer, and the internal notes.
 * Named review participants see the profile without any applicant
 * permission; everything is re-resolved through the scope on each request.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.applicant')] class extends Component {
    use AuthorizesRecruitment;
    use ManagesApplicantProfile;
    use ReadsApplicantProfile;

    #[Locked]
    public int $applicantId;

    public function mount(int $applicantId): void
    {
        $this->applicantId = $applicantId;
        $this->applicant;

        $applications = $this->noteApplications;
        if ($applications->count() === 1) {
            $this->noteApplicationId = $applications->first()->getKey();
        }
    }
};
?>

@php
    use Modules\Hr\Enums\RecruitmentApplicationStatus as Stage;
    use Modules\Hr\Models\RecruitmentApplicant;
    use Modules\Hr\Support\Recruitment\Labels;

    $applicant = $this->applicant;
    $applications = $this->applications;
    $profile = $this->profile;
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $current = $applications->first();
    $currentCampaign = $current?->campaign;
    $city = $applicant->city ?? $currentCampaign?->vacancy?->createdFromRequest?->scopeCity;
    $currentTargets = $current ? ($profile['per'][$current->id]['targets'] ?? []) : [];
    $nextTarget = collect($currentTargets)->first(fn ($target) => $target !== Stage::Rejected);
@endphp

<div class="space-y-6" data-applicant="{{ $applicant->id }}">
    <x-ui.detail-header
        :back="\App\Support\BackUrl::for(route('hr.recruitment.applicants.index'))"
        :breadcrumbs="[['label' => __('hr_recruitment.hub.section')], ['label' => __('hr_recruitment.hub.titles.applicants'), 'href' => route('hr.recruitment.applicants.index')]]"
        :title="$applicant->full_name"
    >
        <x-slot:meta>
            <span>{{ $applicant->isInternal() ? __('hr_recruitment.applicant_ui.profile.internal_candidate') : __('hr_recruitment.applicant_ui.profile.external_candidate') }}</span>
            @if ($profile['isPrevious'])
                <span>{{ __('hr_recruitment.applicant_ui.previous_applicant_flag') }}</span>
            @endif
            @if ($applicant->anonymized_at)
                <x-ui.enum-badge :label="__('hr_recruitment.applicant_ui.anonymized')" />
            @endif
        </x-slot:meta>
        <x-slot:actions>
            @if ($profile['mayErase'] && ! $applicant->anonymized_at)
                <x-ui.button tone="secondary" icon="shield-exclamation" wire:click="$set('showErasure', true)">{{ __('hr_recruitment.hub.applicants.erasure_title') }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.detail-header>

    @if (session('status'))
        <div role="status" class="rounded-lg border border-panel-border bg-secondary-soft/40 px-4 py-3 text-sm dark:bg-surface-2">{{ session('status') }}</div>
    @endif

    {{-- Identity on one side, "where are they and what do I do next" on the other. --}}
    <x-ui.panel>
        <div class="grid lg:grid-cols-[minmax(0,1fr)_22rem]">
            <dl class="grid gap-x-6 gap-y-4 p-5 text-sm sm:grid-cols-2 xl:grid-cols-3" aria-label="{{ __('hr_recruitment.applicant_ui.profile.contact_information') }}">
                <div class="min-w-0"><dt class="{{ $muted }}">{{ __('hr_recruitment.hub.applicants.email') }}</dt>
                    <dd class="wrap-anywhere"><a href="mailto:{{ $applicant->email_normalized }}" class="font-medium underline-offset-4 hover:underline"><bdi dir="ltr">{{ $applicant->email_normalized }}</bdi></a></dd></div>
                @if ($applicant->phone_normalized)
                    <div><dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.mobile') }}</dt>
                        <dd><a href="tel:{{ $applicant->phone_normalized }}" class="font-medium tabular-nums underline-offset-4 hover:underline"><bdi dir="ltr">{{ $applicant->phone_normalized }}</bdi></a></dd></div>
                @endif
                <div><dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.city') }}</dt><dd class="font-medium">{{ $city?->name ?? '—' }}</dd></div>
                <div><dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.source') }}</dt><dd class="font-medium">{{ RecruitmentApplicant::sourceLabel($applicant->source) }}</dd></div>
                <div><dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.first_seen') }}</dt><dd class="font-medium tabular-nums">{{ $applicant->created_at?->translatedFormat(__('formats.date')) ?? '—' }}</dd></div>
            </dl>

            <aside class="flex flex-col border-t border-panel-border bg-secondary-soft/30 p-5 lg:border-s lg:border-t-0 dark:bg-surface-2">
                <h2 class="type-label {{ $muted }}">{{ __('hr_recruitment.applicant_ui.profile.current_application') }}</h2>
                @if ($current)
                    <p class="mt-2 font-semibold" dir="auto">{{ $currentCampaign?->vacancy?->position?->title ?? __('hr_recruitment.applicant_ui.no_vacancy') }}</p>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-sm">
                        <x-ui.enum-badge :label="__('hr_recruitment.applicant_ui.statuses.'.$current->status)" :tone="Labels::tone($current->status)" />
                        @if ($currentCampaign)
                            <span class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.campaign_number', ['number' => $currentCampaign->id]) }}</span>
                        @endif
                    </div>
                    <div class="mt-auto pt-4">
                        <p class="type-label {{ $muted }}">{{ __('hr_recruitment.applicant_ui.profile.next_step') }}</p>
                        <div class="mt-2">
                            @if ($current->status === Stage::Shortlisted && $profile['per'][$current->id]['canSchedule'] && Route::has('hr.recruitment.interviews.create'))
                                <x-ui.button tone="primary" class="w-full" :href="route('hr.recruitment.interviews.create', ['applicationId' => $current->id])" wire:navigate>{{ __('hr_recruitment.applicant_ui.interviews.schedule') }}</x-ui.button>
                            @elseif ($current->status === Stage::Selected && $profile['per'][$current->id]['canCreateOffer'] && Route::has('hr.recruitment.offers.create'))
                                <x-ui.button tone="primary" class="w-full" :href="route('hr.recruitment.offers.create', ['applicationId' => $current->id])" wire:navigate>{{ __('hr_recruitment.applicant_ui.offers.create') }}</x-ui.button>
                            @elseif ($current->status === Stage::InterviewScheduled)
                                <p class="text-sm">{{ __('hr_recruitment.applicant_ui.profile.awaiting_interview') }}</p>
                            @elseif ($nextTarget)
                                <x-ui.button tone="primary" class="w-full" wire:click="beginStatusChange({{ $current->id }}, '{{ $nextTarget }}')">{{ __('hr_recruitment.applicant_ui.decisions.actions.'.$nextTarget) }}</x-ui.button>
                            @else
                                <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.profile.no_action') }}</p>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="mt-2 text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.no_applications') }}</p>
                @endif
            </aside>
        </div>
    </x-ui.panel>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section id="applications" aria-labelledby="applications-title" class="min-w-0 space-y-4">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 id="applications-title" class="text-lg font-semibold">{{ __('hr_recruitment.applicant_ui.history') }} <span class="tabular-nums {{ $muted }}">({{ $applications->count() }})</span></h2>
                @if ($profile['hidden'] > 0)
                    <p class="text-sm {{ $muted }}">{{ trans_choice('hr_recruitment.applicant_ui.hidden_applications', $profile['hidden'], ['count' => $profile['hidden']]) }}</p>
                @endif
            </div>

            @forelse ($applications as $application)
                @include('hr::pages.recruitment.applicants._show-application', ['application' => $application, 'state' => $profile['per'][$application->id], 'open' => $loop->first])
            @empty
                <x-ui.panel><x-ui.empty-state icon="document-text" :heading="__('hr_recruitment.applicant_ui.no_applications')" /></x-ui.panel>
            @endforelse

            {{-- Files with no application behind them: a talent-pool record, or an application since removed. --}}
            @if ($profile['orphans']->isNotEmpty())
                <x-ui.panel :title="__('hr_recruitment.applicant_ui.unattached_files')" :subtitle="__('hr_recruitment.applicant_ui.unattached_files_hint')">
                    <ul class="divide-y divide-panel-border px-4 text-sm">
                        @foreach ($profile['orphans'] as $file)
                            @include('hr::pages.recruitment.applicants._show-file', ['file' => $file])
                        @endforeach
                    </ul>
                </x-ui.panel>
            @endif
        </section>

        <aside class="space-y-4 xl:sticky xl:top-24">
            @include('hr::pages.recruitment.applicants._show-side')
        </aside>
    </div>

    @include('hr::pages.recruitment.applicants._show-dialogs')
</div>
