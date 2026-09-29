@php
    use App\Enums\HiringCampaignStatus;
    use App\Enums\RecruitmentApplicationStatus as ApplicationStatus;
    use Illuminate\Support\Facades\Gate;
    use Illuminate\Support\Facades\Lang;
    use App\Enums\RecruitmentCvAvailability;
    use App\Models\RecruitmentApplicant;

    $states = app(\App\Services\HiringCampaignStateMachine::class);
@endphp

<div class="space-y-6 p-6">
    @if (session('recruitment_saved'))
        <p role="status" class="rounded-control border border-green-200 bg-green-50 p-4 text-green-800">{{ session('recruitment_saved') }}</p>
    @endif

    <a href="{{ route('recruitment.applicants.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline">
        <svg class="size-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7"/></svg>
        {{ __('recruitment-applicant.back_to_list') }}
    </a>

    {{-- Who they are --}}
    <section class="rounded-surface border border-neutral-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-h3 font-semibold wrap-break-word">{{ $applicant->full_name }}</h1>
                {{-- Each value is isolated left-to-right on its own, so the line
                     still starts on the reading side of an Arabic page. --}}
                <p class="mt-1 text-sm text-secondary-medium wrap-anywhere">
                    <bdi dir="ltr">{{ $applicant->email_normalized }}</bdi>@if ($applicant->phone_normalized) &middot; <bdi dir="ltr">{{ $applicant->phone_normalized }}</bdi>@endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                {{-- A flag for the reviewer, never a decision. --}}
                @if ($isPreviousApplicant)
                    <span class="rounded-control bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-800">{{ __('recruitment-applicant.previous_applicant_flag') }}</span>
                @endif
                @if ($applicant->anonymized_at)
                    <span class="rounded-control bg-neutral-100 px-3 py-1 text-sm font-semibold text-secondary-dark">{{ __('recruitment-applicant.anonymized') }}</span>
                @endif
            </div>
        </div>

        <dl class="mt-6 grid gap-4 sm:grid-cols-3">
            <div>
                <dt class="text-sm text-secondary-medium">{{ __('recruitment-applicant.source') }}</dt>
                <dd class="font-medium">{{ RecruitmentApplicant::sourceLabel($applicant->source) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-secondary-medium">{{ __('recruitment-applicant.city') }}</dt>
                <dd class="font-medium">{{ $applicant->city?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-secondary-medium">{{ __('recruitment-applicant.first_seen') }}</dt>
                <dd class="font-medium">{{ $applicant->created_at?->format('Y-m-d') ?? '—' }}</dd>
            </div>
        </dl>
    </section>

    {{-- Application history --}}
    <section class="rounded-surface border border-neutral-200 bg-white p-6">
        <h2 class="text-h4 font-semibold">{{ __('recruitment-applicant.history') }}</h2>

        @if ($hiddenApplications > 0)
            <p class="mt-2 text-sm text-secondary-medium">{{ trans_choice('recruitment-applicant.hidden_applications', $hiddenApplications, ['count' => $hiddenApplications]) }}</p>
        @endif

        @forelse ($applications as $application)
            @php
                $campaign = $application->campaign;
                $effective = $campaign ? $states->effective($campaign) : null;
                $campaignOpen = in_array($effective, [HiringCampaignStatus::Published, HiringCampaignStatus::Open], true);
                $cvFiles = $application->files->where('file_type', \App\Models\RecruitmentApplicantFile::TYPE_CV);
                $availability = RecruitmentCvAvailability::of($cvFiles);
            @endphp

            <article wire:key="application-{{ $application->id }}" class="mt-4 rounded-control border border-neutral-200 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        {{-- Focus lands here after a decision: the buttons that led to it are gone. --}}
                        <h3 id="application-{{ $application->id }}-title" tabindex="-1" class="scroll-mt-24 font-semibold wrap-break-word">
                            {{ $campaign?->vacancy?->position?->title ?? __('recruitment-applicant.no_vacancy') }}
                            @if ($effective !== null && ! $campaignOpen)
                                <span class="ms-1 rounded-control bg-neutral-100 px-2 py-0.5 text-xs font-normal text-secondary-dark">{{ HiringCampaignStatus::getLocalizedName($effective)['value'] }}</span>
                            @endif
                        </h3>
                        <p class="mt-1 text-sm text-secondary-medium">
                            {{ __('recruitment-applicant.applied_at') }}: {{ $application->submitted_at?->format('Y-m-d H:i') ?? '—' }}
                            @if ($campaign)
                                @can('view', $campaign)
                                    &middot;
                                    <a href="{{ route('recruitment.campaigns.show', ['campaignId' => $campaign->id]) }}" wire:navigate class="text-primary hover:underline">{{ __('recruitment-applicant.open_campaign') }}</a>
                                @endcan
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-recruitment.pill :tone="RecruitmentCvAvailability::classes($availability)">{{ __('recruitment-applicant.cv_availability.'.$availability) }}</x-recruitment.pill>
                        <span class="rounded-control px-3 py-1 text-sm font-semibold {{ ApplicationStatus::classes($application->status) }}">{{ __('recruitment-applicant.statuses.'.$application->status) }}</span>
                    </div>
                </div>

                {{-- Files. The link appears only for users who may download,
                     but the download route asks the policy again regardless. --}}
                @if ($application->files->isNotEmpty())
                    <ul class="mt-4 divide-y divide-neutral-100 text-sm">
                        @foreach ($application->files as $file)
                            @include('livewire.recruitment.applicants.partials.file-row', ['file' => $file])
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-sm text-secondary-medium">{{ __('recruitment-applicant.cv_availability.missing') }}</p>
                @endif

                @if (isset($indicatorSets[$application->id]))
                    @include('livewire.recruitment.applicants.partials.indicators', [
                        'set' => $indicatorSets[$application->id],
                        'snapshot' => $application->indicatorSnapshot,
                    ])
                @endif

                {{-- Automatic CV reading: only for a cleared CV this user may download. --}}
                @foreach ($cvFiles as $cvFile)
                    @if ($cvFile->cvAnalysis && Gate::allows('download', $cvFile))
                        @include('livewire.recruitment.applicants.partials.cv-analysis', ['analysis' => $cvFile->cvAnalysis])
                    @endif
                @endforeach

                <livewire:recruitment.applicants.application-tags :application-id="$application->id" :key="'tags-'.$application->id" />

                {{-- Decisions: one button per move this user may make. The button
                     only picks the target; the service checks it all again. --}}
                @php $targets = $statusTargets[$application->id] ?? []; @endphp
                @if (! empty($targets))
                    @if ($statusApplicationId === $application->id)
                        {{-- Keyed apart from the buttons it replaces, so each is swapped in whole. --}}
                        <div wire:key="decision-panel-{{ $application->id }}" class="mt-4 rounded-control border p-4 {{ $statusTarget === ApplicationStatus::Rejected ? 'border-red-200 bg-red-50/50' : 'border-neutral-200 bg-neutral-50' }}">
                            <h4 id="decision-panel-{{ $application->id }}" tabindex="-1" class="scroll-mt-24 font-semibold">
                                {{ __('recruitment-applicant.decisions.titles.'.$statusTarget) }}
                                <span class="font-normal text-secondary-medium">
                                    &middot; {{ __('recruitment-applicant.statuses.'.$statusFrom) }} <x-recruitment.transition-arrow /> {{ __('recruitment-applicant.statuses.'.$statusTarget) }}
                                </span>
                            </h4>
                            <x-forms.input-error field="statusTarget" :input-id="'decision-'.$application->id" />

                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                @if ($statusTarget === ApplicationStatus::Rejected)
                                    <div>
                                        <x-forms.input-label for="statusReasonCode" required>{{ __('recruitment-applicant.decisions.reason_code') }}</x-forms.input-label>
                                        <select id="statusReasonCode" wire:model.live="statusReasonCode" aria-required="true"
                                            @error('statusReasonCode') aria-invalid="true" aria-describedby="statusReasonCode-error" @enderror
                                            class="mt-1 w-full rounded-control border-neutral-300">
                                            <option value="">—</option>
                                            @foreach ($rejectionReasons as $code)
                                                <option value="{{ $code }}">{{ __('recruitment-applicant.decisions.rejection_reasons.'.$code) }}</option>
                                            @endforeach
                                        </select>
                                        <x-forms.input-error field="statusReasonCode" input-id="statusReasonCode" />
                                    </div>
                                @endif
                                <div class="sm:col-span-2">
                                    <x-forms.input-label for="statusReason" :required="$statusNeedsReason">{{ __('recruitment-applicant.decisions.internal_note') }}</x-forms.input-label>
                                    <textarea id="statusReason" wire:model="statusReason" rows="3" @if ($statusNeedsReason) aria-required="true" @endif
                                        aria-describedby="statusReason-hint @error('statusReason') statusReason-error @enderror" @error('statusReason') aria-invalid="true" @enderror
                                        class="mt-1 w-full rounded-control border-neutral-300"></textarea>
                                    <p id="statusReason-hint" class="mt-1 text-xs text-secondary-medium">{{ __('recruitment-applicant.decisions.internal_note_hint') }}</p>
                                    <x-forms.input-error field="statusReason" input-id="statusReason" />
                                </div>
                                @if ($statusTarget === ApplicationStatus::Rejected)
                                    <div class="sm:col-span-2">
                                        @if ($application->rejectionNotice?->isSettled())
                                            <p class="text-sm text-secondary-medium">{{ __('recruitment-applicant.decisions.already_notified') }}</p>
                                        @else
                                            <label class="flex items-start gap-2 text-sm">
                                                <input type="checkbox" wire:model="statusNotify" class="mt-0.5 rounded border-neutral-300">
                                                <span>
                                                    {{ __('recruitment-applicant.decisions.notify') }}
                                                    <span class="block text-xs text-secondary-medium">{{ __('recruitment-applicant.decisions.notify_hint') }}</span>
                                                </span>
                                            </label>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <x-primary-button type="button" wire:click="changeStatus" wire:loading.attr="disabled" wire:target="changeStatus"
                                    wire:confirm="{{ __('recruitment-applicant.status_change.confirm') }}">
                                    {{ __('recruitment-applicant.decisions.confirm.'.$statusTarget) }}
                                </x-primary-button>
                                <x-secondary-button wire:click="cancelStatusChange">
                                    {{ __('recruitment-applicant.cancel') }}
                                </x-secondary-button>
                            </div>
                        </div>
                    @else
                        <div wire:key="decisions-{{ $application->id }}" class="mt-4 flex flex-wrap gap-2">
                            @foreach ($targets as $target)
                                @php
                                    $style = match ($target) {
                                        ApplicationStatus::Rejected => 'border-red-300 text-red-700 hover:bg-red-50',
                                        ApplicationStatus::Selected => 'border-green-300 text-green-800 hover:bg-green-50',
                                        ApplicationStatus::Shortlisted => 'border-neutral-300 text-primary hover:bg-selection',
                                        default => 'border-neutral-300 hover:bg-neutral-50',
                                    };
                                    $label = $application->status === ApplicationStatus::Rejected
                                        ? __('recruitment-applicant.decisions.actions.reopen')
                                        : __('recruitment-applicant.decisions.actions.'.$target);
                                @endphp
                                <button type="button" id="decide-{{ $application->id }}-{{ $target }}" wire:key="decide-{{ $application->id }}-{{ $target }}" wire:click="beginStatusChange({{ $application->id }}, '{{ $target }}')"
                                    class="cursor-pointer rounded-control border px-4 py-2 text-sm font-semibold {{ $style }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                @endif

                @if ($application->rejectionNotice)
                    @php $notice = $application->rejectionNotice; @endphp
                    <p class="mt-3 text-sm text-secondary-medium">
                        {{ __('recruitment-applicant.decisions.notice_status.'.$notice->status, [
                            'date' => ($notice->sent_at ?? $notice->failed_at ?? $notice->queued_at)?->format('Y-m-d H:i'),
                        ]) }}
                    </p>
                @endif

                {{-- Decision history: who, when, from/to and why. Staff only. --}}
                @if ($application->decisions->isNotEmpty())
                    <details class="mt-4 text-sm">
                        <summary class="cursor-pointer font-medium">{{ __('recruitment-applicant.status_change.history') }} ({{ $application->decisions->count() }})</summary>
                        <ol class="mt-2 space-y-2">
                            @foreach ($application->decisions as $decision)
                                <li class="border-s-2 border-neutral-200 ps-3">
                                    <span class="font-medium">{{ __('recruitment-applicant.statuses.'.$decision->from_status) }} <x-recruitment.transition-arrow /> {{ __('recruitment-applicant.statuses.'.$decision->to_status) }}</span>
                                    <span class="text-secondary-medium">
                                        &middot; {{ $decision->decidedBy?->profile?->name ?? $decision->decidedBy?->name ?? '—' }}
                                        &middot; {{ $decision->decided_at?->format('Y-m-d H:i') }}
                                    </span>
                                    @if ($decision->to_status === ApplicationStatus::Rejected && $decision->reason_code)
                                        @php $reasonKey = 'recruitment-applicant.decisions.rejection_reasons.'.$decision->reason_code; @endphp
                                        <p class="mt-1">{{ __('recruitment-applicant.decisions.reason_code') }}: {{ Lang::has($reasonKey) ? __($reasonKey) : $decision->reason_code }}</p>
                                    @endif
                                    @if ($decision->reason_text)
                                        <p class="mt-1 whitespace-pre-line text-secondary-medium wrap-break-word">{{ $decision->reason_text }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </details>
                @endif

                {{-- Task 7 (Team 2): this application's interviews. Keyed on the status so a decision re-renders it. --}}
                <livewire:recruitment.applicants.application-interviews :application-id="$application->id" :key="'interviews-'.$application->id.'-'.$application->status" />

                {{-- Task 9 (Team 2): this application's job offer (view-offers only). --}}
                <livewire:recruitment.applicants.application-offer :application-id="$application->id" :key="'offer-'.$application->id.'-'.$application->status" />
            </article>
        @empty
            <p class="mt-4 text-sm text-secondary-medium">{{ __('recruitment-applicant.no_applications') }}</p>
        @endforelse
    </section>

    {{-- Files with no application behind them: talent pool, or an application since removed. --}}
    @if ($orphanFiles->isNotEmpty())
        <section class="rounded-surface border border-neutral-200 bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment-applicant.unattached_files') }}</h2>
            <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-applicant.unattached_files_hint') }}</p>
            <ul class="mt-4 divide-y divide-neutral-100 text-sm">
                @foreach ($orphanFiles as $file)
                    @include('livewire.recruitment.applicants.partials.file-row', ['file' => $file, 'panelHeading' => 'h3'])
                @endforeach
            </ul>
        </section>
    @endif

    <livewire:recruitment.applicants.notes :applicant-id="$applicant->id" />
</div>
