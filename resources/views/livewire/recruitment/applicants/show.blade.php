@php
    use App\Enums\HiringCampaignStatus;
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

                {{-- Status change: its own action, its own permission, confirmed. --}}
                @if (! empty($statusTargets[$application->id] ?? []))
                    @if ($statusApplicationId === $application->id)
                        <div class="mt-4 rounded-control border border-primary/30 bg-primary/5 p-4">
                            <h4 class="font-semibold">{{ __('recruitment-applicant.status_change.change_title') }}</h4>
                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <x-forms.input-label for="statusTarget" required>{{ __('recruitment-applicant.status_change.new_status') }}</x-forms.input-label>
                                    <select id="statusTarget" wire:model.live="statusTarget" class="mt-1 w-full rounded-control border-neutral-300">
                                        <option value="">—</option>
                                        @foreach ($statusTargets[$application->id] as $target)
                                            <option value="{{ $target }}">{{ __('recruitment-applicant.statuses.'.$target) }}</option>
                                        @endforeach
                                    </select>
                                    @error('statusTarget') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <x-forms.input-label for="statusReason" :required="$statusNeedsReason">{{ __('recruitment-applicant.status_change.reason') }}</x-forms.input-label>
                                    <textarea id="statusReason" wire:model="statusReason" rows="3" class="mt-1 w-full rounded-control border-neutral-300"></textarea>
                                    @error('statusReason') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <x-primary-button type="button" wire:click="changeStatus" wire:loading.attr="disabled" wire:target="changeStatus"
                                    wire:confirm="{{ __('recruitment-applicant.status_change.confirm') }}">
                                    {{ __('recruitment-applicant.status_change.confirm_button') }}
                                </x-primary-button>
                                <button type="button" wire:click="cancelStatusChange" class="cursor-pointer rounded-control border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                                    {{ __('recruitment-applicant.cancel') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <button type="button" wire:click="beginStatusChange({{ $application->id }})"
                            class="mt-4 cursor-pointer rounded-control border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                            {{ __('recruitment-applicant.status_change.change') }}
                        </button>
                    @endif
                @endif

                @if ($application->decisions->isNotEmpty())
                    <details class="mt-4 text-sm">
                        <summary class="cursor-pointer font-medium">{{ __('recruitment-applicant.status_change.history') }}</summary>
                        <ol class="mt-2 space-y-2">
                            @foreach ($application->decisions as $decision)
                                <li class="border-s-2 border-neutral-200 ps-3">
                                    <span class="font-medium">{{ __('recruitment-applicant.statuses.'.$decision->from_status) }} &rarr; {{ __('recruitment-applicant.statuses.'.$decision->to_status) }}</span>
                                    <span class="text-secondary-medium">
                                        &middot; {{ $decision->decidedBy?->profile?->name ?? $decision->decidedBy?->name ?? '—' }}
                                        &middot; {{ $decision->decided_at?->format('Y-m-d H:i') }}
                                    </span>
                                    @if ($decision->reason_text)
                                        <p class="mt-1 whitespace-pre-line text-secondary-medium">{{ $decision->reason_text }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </details>
                @endif
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
