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

    <a href="{{ route('recruitment.applicants.index') }}" wire:navigate class="text-sm font-medium text-primary hover:underline">
        &larr; {{ __('recruitment-applicant.back_to_list') }}
    </a>

    {{-- Who they are --}}
    <section class="rounded-surface border border-neutral-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-h3 font-semibold">{{ $applicant->full_name }}</h1>
                <p class="mt-1 text-sm text-secondary-medium" dir="ltr">
                    {{ $applicant->email_normalized }}@if ($applicant->phone_normalized) &middot; {{ $applicant->phone_normalized }}@endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                {{-- A flag for the reviewer, never a decision. --}}
                @if ($isPreviousApplicant)
                    <span class="rounded-control bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-800">{{ __('recruitment-applicant.previous_applicant_flag') }}</span>
                @endif
                @if ($applicant->anonymized_at)
                    <span class="rounded-control bg-neutral-100 px-3 py-1 text-sm font-semibold text-secondary-medium">{{ __('recruitment-applicant.anonymized') }}</span>
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
                    <div>
                        <h3 class="font-semibold">
                            {{ $campaign?->vacancy?->position?->title ?? __('recruitment-applicant.no_vacancy') }}
                            @if ($effective !== null && ! $campaignOpen)
                                <span class="ms-1 rounded-control bg-neutral-100 px-2 py-0.5 text-xs font-normal text-secondary-medium">{{ HiringCampaignStatus::getLocalizedName($effective)['value'] }}</span>
                            @endif
                        </h3>
                        <p class="mt-1 text-sm text-secondary-medium">
                            {{ __('recruitment-applicant.applied_at') }}: {{ $application->submitted_at?->format('Y-m-d H:i') ?? '—' }}
                            @if ($campaign)
                                &middot;
                                @can('view', $campaign)
                                    <a href="{{ route('recruitment.campaigns.show', ['campaignId' => $campaign->id]) }}" wire:navigate class="text-primary hover:underline">{{ __('recruitment-applicant.open_campaign') }}</a>
                                @endcan
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-control px-2 py-0.5 text-xs font-semibold {{ RecruitmentCvAvailability::classes($availability) }}">{{ __('recruitment-applicant.cv_availability.'.$availability) }}</span>
                        <span class="rounded-control bg-primary/10 px-3 py-1 text-sm font-semibold text-primary">{{ __('recruitment-applicant.statuses.'.$application->status) }}</span>
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
                        <div class="mt-4 rounded-control border p-4 {{ $statusTarget === ApplicationStatus::Rejected ? 'border-red-200 bg-red-50/50' : 'border-primary/30 bg-primary/5' }}">
                            <h4 class="font-semibold">
                                {{ __('recruitment-applicant.decisions.titles.'.$statusTarget) }}
                                <span class="font-normal text-secondary-medium">
                                    &middot; {{ __('recruitment-applicant.statuses.'.$statusFrom) }} &rarr; {{ __('recruitment-applicant.statuses.'.$statusTarget) }}
                                </span>
                            </h4>
                            @error('statusTarget') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror

                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                @if ($statusTarget === ApplicationStatus::Rejected)
                                    <div>
                                        <x-forms.input-label for="statusReasonCode" required>{{ __('recruitment-applicant.decisions.reason_code') }}</x-forms.input-label>
                                        <select id="statusReasonCode" wire:model.live="statusReasonCode" class="mt-1 w-full rounded-control border-neutral-300">
                                            <option value="">—</option>
                                            @foreach ($rejectionReasons as $code)
                                                <option value="{{ $code }}">{{ __('recruitment-applicant.decisions.rejection_reasons.'.$code) }}</option>
                                            @endforeach
                                        </select>
                                        @error('statusReasonCode') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                                <div class="sm:col-span-2">
                                    <x-forms.input-label for="statusReason" :required="$statusNeedsReason">{{ __('recruitment-applicant.decisions.internal_note') }}</x-forms.input-label>
                                    <textarea id="statusReason" wire:model="statusReason" rows="3" class="mt-1 w-full rounded-control border-neutral-300"></textarea>
                                    <p class="mt-1 text-xs text-secondary-medium">{{ __('recruitment-applicant.decisions.internal_note_hint') }}</p>
                                    @error('statusReason') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
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
                                <button type="button" wire:click="cancelStatusChange" class="cursor-pointer rounded-control border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                                    {{ __('recruitment-applicant.cancel') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($targets as $target)
                                @php
                                    $style = match ($target) {
                                        ApplicationStatus::Rejected => 'border-red-300 text-red-700 hover:bg-red-50',
                                        ApplicationStatus::Selected => 'border-green-300 text-green-800 hover:bg-green-50',
                                        ApplicationStatus::Shortlisted => 'border-primary/40 text-primary hover:bg-primary/5',
                                        default => 'border-neutral-300 hover:bg-neutral-50',
                                    };
                                    $label = $application->status === ApplicationStatus::Rejected
                                        ? __('recruitment-applicant.decisions.actions.reopen')
                                        : __('recruitment-applicant.decisions.actions.'.$target);
                                @endphp
                                <button type="button" wire:key="decide-{{ $application->id }}-{{ $target }}" wire:click="beginStatusChange({{ $application->id }}, '{{ $target }}')"
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
                                    <span class="font-medium">{{ __('recruitment-applicant.statuses.'.$decision->from_status) }} &rarr; {{ __('recruitment-applicant.statuses.'.$decision->to_status) }}</span>
                                    <span class="text-secondary-medium">
                                        &middot; {{ $decision->decidedBy?->profile?->name ?? $decision->decidedBy?->name ?? '—' }}
                                        &middot; {{ $decision->decided_at?->format('Y-m-d H:i') }}
                                    </span>
                                    @if ($decision->to_status === ApplicationStatus::Rejected && $decision->reason_code)
                                        @php $reasonKey = 'recruitment-applicant.decisions.rejection_reasons.'.$decision->reason_code; @endphp
                                        <p class="mt-1">{{ __('recruitment-applicant.decisions.reason_code') }}: {{ Lang::has($reasonKey) ? __($reasonKey) : $decision->reason_code }}</p>
                                    @endif
                                    @if ($decision->reason_text)
                                        <p class="mt-1 whitespace-pre-line text-secondary-medium">{{ $decision->reason_text }}</p>
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
                    @include('livewire.recruitment.applicants.partials.file-row', ['file' => $file])
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Archiving a CV --}}
    @if ($archiveFile)
        <section class="rounded-surface border border-primary/30 bg-primary/5 p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment-applicant.cv_archive.archive_title') }}</h2>
            <p class="mt-1 text-sm text-secondary-medium">
                {{ $archiveNeedsReason ? __('recruitment-applicant.cv_archive.archive_hint_no_vacancy') : __('recruitment-applicant.cv_archive.archive_hint') }}
            </p>
            <div class="mt-4">
                <x-forms.input-label for="archiveReason" :required="$archiveNeedsReason">{{ __('recruitment-applicant.cv_archive.reason') }}</x-forms.input-label>
                <textarea id="archiveReason" wire:model="archiveReason" rows="3" class="mt-1 w-full rounded-control border-neutral-300"></textarea>
                @error('archiveReason') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div class="mt-4 flex flex-wrap gap-3">
                <x-primary-button type="button" wire:click="archiveCv" wire:loading.attr="disabled" wire:target="archiveCv">
                    {{ __('recruitment-applicant.cv_archive.archive_confirm') }}
                </x-primary-button>
                <button type="button" wire:click="cancelArchive" class="cursor-pointer rounded-control border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                    {{ __('recruitment-applicant.cancel') }}
                </button>
            </div>
        </section>
    @endif

    <livewire:recruitment.applicants.notes :applicant-id="$applicant->id" />
</div>
