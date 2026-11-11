{{-- One application on the applicant profile. Expects $application, $state (the profile's per-application facts) and $open. --}}
@php
    use Illuminate\Support\Facades\Lang;
    use Modules\Hr\Actions\Recruitment\HiringCampaignStateMachine;
    use Modules\Hr\Enums\HiringCampaignStatus;
    use Modules\Hr\Enums\RecruitmentApplicationStatus as Stage;
    use Modules\Hr\Enums\RecruitmentCvAvailability;
    use Modules\Hr\Models\RecruitmentApplicantFile;
    use Modules\Hr\Models\RecruitmentApplicationFlag;
    use Modules\Hr\Models\RecruitmentTag;
    use Modules\Hr\Support\Recruitment\Labels;

    $muted = 'text-secondary-medium dark:text-ink-muted';
    $label = 'type-label text-secondary-medium dark:text-ink-muted';
    $campaign = $application->campaign;
    $effective = $campaign ? app(HiringCampaignStateMachine::class)->effective($campaign) : null;
    $campaignOpen = in_array($effective, [HiringCampaignStatus::Published->value, HiringCampaignStatus::Open->value], true);
    $cvFiles = $application->files->where('file_type', RecruitmentApplicantFile::TYPE_CV);
    $availability = RecruitmentCvAvailability::of($cvFiles);
    $pipeline = [Stage::Submitted, Stage::Shortlisted, Stage::InterviewScheduled, Stage::Interviewed, Stage::Finalist, Stage::Selected];
    $ended = in_array($application->status, [Stage::Rejected, Stage::Withdrawn, Stage::Archived, Stage::OfferRejected], true);
    $reached = match ($application->status) {
        Stage::UnderReview => 0,
        Stage::OfferSent, Stage::OfferAccepted, Stage::Hired => count($pipeline),
        default => array_search($application->status, $pipeline, true),
    };
    $targets = $state['targets'];
    $forward = collect($targets)->reject(fn ($target) => $target === Stage::Rejected)->values();
    $set = $profile['indicatorSets'][$application->id] ?? null;
    $snapshot = $application->indicatorSnapshot;
    $hrVoted = $application->endorsements->contains('voter_role', 'hr');
    $managerVoted = $application->endorsements->contains('voter_role', 'manager');
    $fmt = fn ($date, $withTime = false) => $date?->translatedFormat($withTime ? __('formats.datetime') : __('formats.date'));
@endphp

<details id="application-{{ $application->id }}" wire:key="application-{{ $application->id }}" class="group rounded-panel border border-panel-border bg-white shadow-card dark:bg-surface-1" @if ($open || $statusApplicationId === $application->id) open @endif>
    <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 p-4 [&::-webkit-details-marker]:hidden">
        <div class="min-w-0">
            <h3 class="font-semibold" dir="auto">{{ $campaign?->vacancy?->position?->title ?? __('hr_recruitment.applicant_ui.no_vacancy') }}</h3>
            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm {{ $muted }}">
                @if ($campaign)
                    @if (Route::has('hr.recruitment.campaigns.show') && $this->may('recruitment-campaigns.view'))
                        <a href="{{ route('hr.recruitment.campaigns.show', ['campaignId' => $campaign->id]) }}" wire:navigate class="underline-offset-4 hover:underline">{{ __('hr_recruitment.applicant_ui.campaign_number', ['number' => $campaign->id]) }}</a>
                    @else
                        <span>{{ __('hr_recruitment.applicant_ui.campaign_number', ['number' => $campaign->id]) }}</span>
                    @endif
                    @if ($effective !== null && ! $campaignOpen)
                        <span>· {{ Labels::enum('HiringCampaignStatus', $effective) }}</span>
                    @endif
                    <span aria-hidden="true">·</span>
                @endif
                <span>{{ __('hr_recruitment.applicant_ui.applied_at') }} <span class="tabular-nums">{{ $fmt($application->submitted_at, true) ?? '—' }}</span></span>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.cv_availability.'.$availability) }}</span>
            <x-ui.enum-badge :label="__('hr_recruitment.applicant_ui.statuses.'.$application->status)" :tone="Labels::tone($application->status)" />
            <flux:icon.chevron-down variant="mini" class="size-5 {{ $muted }} transition-transform group-open:rotate-180" />
        </div>
    </summary>

    <div class="divide-y divide-panel-border border-t border-panel-border">
        {{-- Progress: one glance says how far they got, or where it stopped. --}}
        <div class="px-4 py-4">
            <ol aria-label="{{ __('hr_recruitment.applicant_ui.profile.workflow') }}" class="grid grid-cols-6">
                @foreach ($pipeline as $i => $step)
                    @php
                        $done = ! $ended && $reached !== false && $i < $reached;
                        $here = ! $ended && $reached === $i;
                    @endphp
                    <li class="relative flex flex-col items-center gap-1.5 text-center" @if ($here) aria-current="step" @endif>
                        @unless ($loop->first)
                            <span aria-hidden="true" class="absolute end-1/2 top-[7px] h-0.5 w-full {{ $done || $here ? 'bg-secondary-dark dark:bg-ink' : 'bg-secondary-soft dark:bg-surface-3' }}"></span>
                        @endunless
                        <span aria-hidden="true" @class([
                            'relative z-10 flex size-4 items-center justify-center rounded-full',
                            'bg-secondary-dark text-white dark:bg-ink dark:text-surface-0' => $done,
                            'bg-white ring-4 ring-inset ring-secondary-dark dark:bg-surface-1 dark:ring-ink' => $here,
                            'border-2 border-secondary-soft bg-white dark:border-line dark:bg-surface-1' => ! $done && ! $here,
                        ])>@if ($done)<flux:icon.check variant="micro" class="size-3" />@endif</span>
                        <span @class(['text-xs leading-tight max-sm:sr-only', 'font-semibold' => $here, $muted => ! $done && ! $here])>{{ __('hr_recruitment.applicant_ui.statuses.'.$step) }}</span>
                    </li>
                @endforeach
            </ol>
            @if ($ended)
                <p class="mt-3 text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.statuses.'.$application->status) }}</p>
            @endif
        </div>

        {{-- Decisions first: they are what people come here to do. --}}
        @if ($state['inScope'] && ($targets !== [] || $profile['canEndorse'] || $application->rejectionNotice || $application->decisions->isNotEmpty()))
            <section class="space-y-4 p-4" aria-labelledby="workflow-{{ $application->id }}">
                <h4 id="workflow-{{ $application->id }}" class="{{ $label }}">{{ __('hr_recruitment.applicant_ui.profile.workflow') }}</h4>

                @if ($targets !== [])
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($forward as $target)
                            <x-ui.button :tone="$loop->first ? 'neutral' : 'secondary'" size="sm" wire:key="decide-{{ $application->id }}-{{ $target }}" wire:click="beginStatusChange({{ $application->id }}, '{{ $target }}')">
                                {{ $application->status === Stage::Rejected ? __('hr_recruitment.applicant_ui.decisions.actions.reopen') : __('hr_recruitment.applicant_ui.decisions.actions.'.$target) }}
                            </x-ui.button>
                        @endforeach
                        @if (in_array(Stage::Rejected, $targets, true))
                            <x-ui.button tone="secondary" size="sm" class="sm:ms-auto text-primary!" wire:click="beginStatusChange({{ $application->id }}, '{{ Stage::Rejected }}')">{{ __('hr_recruitment.applicant_ui.decisions.actions.rejected') }}</x-ui.button>
                        @endif
                    </div>
                @endif

                @if ($profile['canEndorse'])
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-panel-border px-3 py-2">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                            <span class="font-semibold">{{ __('hr_recruitment.applicant_ui.endorsement.title') }}</span>
                            @foreach (['hr' => $hrVoted, 'manager' => $managerVoted] as $role => $voted)
                                <span class="inline-flex items-center gap-1.5 {{ $voted ? 'font-semibold' : $muted }}">
                                    <flux:icon :name="$voted ? 'check-circle' : 'minus-circle'" variant="micro" class="size-4" />
                                    {{ __('hr_recruitment.applicant_ui.endorsement.role_'.$role) }}
                                </span>
                            @endforeach
                        </div>
                        @if ($state['myEndorsement'])
                            <x-ui.button tone="secondary" size="sm" wire:click="retractEndorsement({{ $application->id }})">{{ __('hr_recruitment.applicant_ui.endorsement.retract') }}</x-ui.button>
                        @else
                            <x-ui.button tone="neutral" size="sm" icon="hand-thumb-up" wire:click="endorse({{ $application->id }})">{{ __('hr_recruitment.applicant_ui.endorsement.endorse') }}</x-ui.button>
                        @endif
                    </div>
                @endif

                @if ($application->rejectionNotice)
                    @php $notice = $application->rejectionNotice; @endphp
                    <p class="flex items-center gap-2 text-sm {{ $muted }}">
                        <flux:icon.envelope variant="micro" class="size-4" />
                        {{ __('hr_recruitment.applicant_ui.decisions.notice_status.'.$notice->status, ['date' => $fmt($notice->sent_at ?? $notice->failed_at ?? $notice->queued_at, true)]) }}
                    </p>
                @endif

                @if ($application->decisions->isNotEmpty())
                    <details class="group/history">
                        <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 text-sm font-semibold [&::-webkit-details-marker]:hidden">
                            <flux:icon.chevron-right variant="micro" class="size-4 transition-transform group-open/history:rotate-90 rtl:rotate-180 rtl:group-open/history:rotate-90" />
                            {{ __('hr_recruitment.applicant_ui.status_change.history') }} <span class="tabular-nums {{ $muted }}">({{ $application->decisions->count() }})</span>
                        </summary>
                        <ol class="mt-3 space-y-4 border-s-2 border-panel-border ps-5 text-sm">
                            @foreach ($application->decisions as $decision)
                                <li wire:key="decision-{{ $decision->id }}">
                                    <p class="font-semibold">{{ __('hr_recruitment.applicant_ui.statuses.'.$decision->from_status) }} → {{ __('hr_recruitment.applicant_ui.statuses.'.$decision->to_status) }}</p>
                                    <p class="{{ $muted }}" dir="auto">{{ $decision->decidedBy?->name ?? '—' }} · <span class="tabular-nums">{{ $fmt($decision->decided_at, true) }}</span></p>
                                    @if ($decision->to_status === Stage::Rejected && $decision->reason_code)
                                        @php $reasonKey = 'hr_recruitment.applicant_ui.decisions.rejection_reasons.'.$decision->reason_code; @endphp
                                        <p class="mt-1">{{ __('hr_recruitment.applicant_ui.decisions.reason_code') }}: {{ Lang::has($reasonKey) ? __($reasonKey) : $decision->reason_code }}</p>
                                    @endif
                                    @if ($decision->reason_text)
                                        <p class="mt-1.5 whitespace-pre-line rounded-md bg-secondary-soft/40 px-3 py-2 wrap-break-word dark:bg-surface-2" dir="auto">{{ $decision->reason_text }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </details>
                @endif
            </section>
        @endif

        {{-- The named reviewer's own assessment, and what was submitted. Advisory only. --}}
        @if ($profile['ownParticipant'] || $state['reviews']->isNotEmpty())
            <section class="space-y-3 p-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h4 class="{{ $label }}">{{ __('hr_recruitment.applicant_ui.profile_reviews.my_review') }}</h4>
                        <p class="text-xs {{ $muted }}">{{ $profile['canReview'] ? __('hr_recruitment.applicant_ui.profile_reviews.advisory') : ($profile['ownParticipant'] ? __('hr_recruitment.applicant_ui.profile_reviews.viewer_hint') : '') }}</p>
                    </div>
                    @if ($profile['canReview'])
                        @php $mine = $state['reviews']->firstWhere('reviewer_user_id', $this->viewer()->getKey()); @endphp
                        <x-ui.button tone="secondary" size="sm" icon="pencil-square" wire:click="openReview({{ $application->id }})">{{ $mine ? __('hr_recruitment.applicant_ui.profile_reviews.update') : __('hr_recruitment.applicant_ui.profile_reviews.submit') }}</x-ui.button>
                    @endif
                </div>
                @if ($state['reviews']->isNotEmpty())
                    <div class="space-y-3">
                        @foreach ($state['reviews'] as $review)
                            <article wire:key="profile-review-{{ $review->id }}" class="rounded-lg border border-panel-border p-3 text-sm">
                                <p class="flex flex-wrap justify-between gap-2">
                                    <span class="font-semibold" dir="auto">{{ $review->reviewer?->name ?? '—' }}</span>
                                    <span class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.evaluations.recommendations.'.$review->recommendation) }} · <span class="tabular-nums">{{ $fmt($review->updated_at, true) }}</span></span>
                                </p>
                                <dl class="mt-2 grid gap-3 md:grid-cols-2">
                                    @foreach (['pros', 'cons', 'notes'] as $field)
                                        @if (filled($review->{$field}))
                                            <div @class(['md:col-span-2' => $field === 'notes'])>
                                                <dt class="text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.profile_reviews.'.$field) }}</dt>
                                                <dd class="whitespace-pre-line wrap-break-word" dir="auto">{{ $review->{$field} }}</dd>
                                            </div>
                                        @endif
                                    @endforeach
                                </dl>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        {{-- Evidence: how they match, and the files behind it. --}}
        <div class="grid gap-x-8 gap-y-6 p-4 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            <section class="min-w-0">
                <h4 class="{{ $label }}">{{ __('hr_recruitment.applicant_ui.profile.evaluation') }}</h4>
                @if ($set)
                    {{-- An aid for the reviewer. Nothing here is a decision, and nothing here changes a status. --}}
                    <details class="mt-3 rounded-lg border border-panel-border p-3 text-sm">
                        <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-2">
                            <span class="font-semibold">{{ __('hr_recruitment.applicant_ui.indicators.title') }}</span>
                            <span class="text-xs font-semibold">
                                {{ $set->assessedCount() === 0 ? __('hr_recruitment.applicant_ui.indicators.none_assessed') : __('hr_recruitment.applicant_ui.indicators.summary', ['met' => $set->metCount(), 'assessed' => $set->assessedCount()]) }}
                                @if ($set->unassessedCount() > 0) · {{ trans_choice('hr_recruitment.applicant_ui.indicators.unassessed', $set->unassessedCount(), ['count' => $set->unassessedCount()]) }}@endif
                            </span>
                        </summary>
                        <p class="mt-3 {{ $muted }}">{{ __('hr_recruitment.applicant_ui.indicators.disclaimer') }}</p>
                        <ul class="mt-3 divide-y divide-panel-border">
                            @foreach ($set->indicators as $indicator)
                                <li class="flex flex-wrap items-start justify-between gap-3 py-2">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium">{{ $indicator->label() }}</p>
                                        <p class="{{ $muted }}">{{ $indicator->explanation() }}</p>
                                    </div>
                                    <x-ui.enum-badge :label="__('hr_recruitment.applicant_ui.indicators.outcomes.'.$indicator->outcome)" :tone="match ($indicator->outcome) { 'met' => \App\Enums\StatusTone::Good, 'partial' => \App\Enums\StatusTone::Warn, 'not_met' => \App\Enums\StatusTone::Critical, default => \App\Enums\StatusTone::Neutral }" />
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-3 text-xs {{ $muted }}">
                            {{ __('hr_recruitment.applicant_ui.indicators.criteria_version', ['version' => $set->criteriaVersion]) }}
                            @if ($snapshot)
                                · {{ __('hr_recruitment.applicant_ui.indicators.snapshot_saved', ['date' => $fmt($snapshot->computed_at, true), 'met' => $snapshot->met_count, 'assessed' => $snapshot->assessed_count, 'version' => $snapshot->criteria_version]) }}
                                @if ($snapshot->criteria_version !== $set->criteriaVersion) · {{ __('hr_recruitment.applicant_ui.indicators.snapshot_outdated') }}@endif
                            @endif
                        </p>
                    </details>
                @endif

                {{-- The automatic CV reading: only for a cleared CV this user may download. --}}
                @foreach ($cvFiles as $cvFile)
                    @if ($cvFile->cvAnalysis && Gate::allows('download', $cvFile))
                        @include('hr::pages.recruitment.applicants._show-cv-analysis', ['analysis' => $cvFile->cvAnalysis])
                    @endif
                @endforeach
            </section>

            <section class="min-w-0">
                <h4 class="{{ $label }}">{{ __('hr_recruitment.applicant_ui.profile.documents') }} <span class="tabular-nums">({{ $application->files->count() }})</span></h4>
                @if ($application->files->isNotEmpty())
                    <ul class="mt-2 divide-y divide-panel-border text-sm">
                        @foreach ($application->files as $file)
                            @include('hr::pages.recruitment.applicants._show-file', ['file' => $file])
                        @endforeach
                    </ul>
                @else
                    <p class="mt-3 rounded-lg border border-dashed border-panel-border px-4 py-5 text-center text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.cv_availability.missing') }}</p>
                @endif
            </section>
        </div>

        {{-- The answers read like the form: one heading per section, label above value. --}}
        <details class="group/answers" @if (count($state['answers']) <= 2) open @endif>
            <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 px-4 py-3 [&::-webkit-details-marker]:hidden">
                <span class="min-w-0">
                    <span class="{{ $label }}">{{ __('hr_recruitment.applicant_ui.application_answers.title') }}</span>
                    <span class="mt-0.5 block text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.application_answers.hint') }}</span>
                </span>
                <span class="flex items-center gap-2 text-xs {{ $muted }}">
                    @if ($application->formVersion)
                        {{ __('hr_recruitment.applicant_ui.application_answers.form_version', ['version' => $application->formVersion->version_no]) }}
                    @endif
                    <flux:icon.chevron-down variant="mini" class="size-5 transition-transform group-open/answers:rotate-180" />
                </span>
            </summary>
            <div class="px-4 pb-4">
                @forelse ($state['answers'] as $section)
                    <section class="mt-4 first:mt-0">
                        <h5 class="border-b border-panel-border pb-2 text-sm font-semibold">{{ $section['label'] }}</h5>
                        <dl class="mt-3 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                            @foreach ($section['answers'] as $answer)
                                @php $long = mb_strlen($answer['value']) > 90 || str_contains($answer['value'], "\n"); @endphp
                                <div @class(['min-w-0', 'sm:col-span-2' => $long])>
                                    <dt class="text-xs {{ $muted }}">{{ $answer['label'] }}</dt>
                                    <dd @class(['mt-0.5 whitespace-pre-line text-sm wrap-break-word', $muted => $answer['empty'], 'font-medium' => ! $answer['empty']]) dir="auto">{{ $answer['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @empty
                    <p class="rounded-lg border border-dashed border-panel-border px-4 py-5 text-center text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.application_answers.none') }}</p>
                @endforelse
            </div>
        </details>

        @if ($state['inScope'])
            {{-- Interviews and the offer. --}}
            <section id="application-{{ $application->id }}-interviews" class="space-y-4 p-4">
                <h4 class="{{ $label }}">{{ __('hr_recruitment.applicant_ui.profile.interviews_and_offer') }}</h4>

                <div>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold">{{ __('hr_recruitment.applicant_ui.interviews.title') }} <span class="tabular-nums {{ $muted }}">({{ $state['interviews']->count() }})</span></p>
                        @if ($state['canSchedule'] && Route::has('hr.recruitment.interviews.create'))
                            <x-ui.button tone="neutral" size="sm" icon="calendar" :href="route('hr.recruitment.interviews.create', ['applicationId' => $application->id])" wire:navigate>{{ __('hr_recruitment.applicant_ui.interviews.schedule') }}</x-ui.button>
                        @endif
                    </div>
                    @if ($state['mayScheduleAtAll'] && ! $state['canSchedule'])
                        <p class="mt-1 text-xs {{ $muted }}">{{ $application->status === Stage::Rejected ? __('hr_recruitment.applicant_ui.interviews.errors.application_rejected') : __('hr_recruitment.applicant_ui.interviews.errors.not_shortlisted') }}</p>
                    @endif
                    @if ($state['interviews']->isEmpty())
                        <p class="mt-2 text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.interviews.none_yet') }}</p>
                    @else
                        <ul class="mt-2 divide-y divide-panel-border rounded-lg border border-panel-border text-sm">
                            @foreach ($state['interviews'] as $interview)
                                @php
                                    $tally = $profile['tallies'][$interview->id] ?? null;
                                    $tz = $interview->timezone ?: config('app.timezone');
                                @endphp
                                <li wire:key="application-interview-{{ $interview->id }}" class="flex flex-wrap items-center justify-between gap-2 px-3 py-2">
                                    <a href="{{ Route::has('hr.recruitment.interviews.show') ? route('hr.recruitment.interviews.show', ['interviewId' => $interview->id]) : '#' }}" wire:navigate class="min-w-0 underline-offset-4 hover:underline">
                                        <span class="font-medium">{{ __('hr_recruitment.applicant_ui.interviews.types.'.$interview->interview_type) }}</span>
                                        · <span class="tabular-nums">{{ $interview->scheduled_at?->copy()->setTimezone($tz)->format('Y-m-d H:i') }}</span>
                                        · {{ __('hr_recruitment.applicant_ui.interviews.location_types.'.$interview->location_type) }}
                                        · {{ trans_choice('hr_recruitment.applicant_ui.interviews.participant_count', $interview->participants_count, ['count' => $interview->participants_count]) }}
                                    </a>
                                    <span class="flex flex-wrap items-center gap-2">
                                        @if ($tally && $tally['total'] > 0)
                                            <span class="text-xs {{ $muted }}">{{ trans_choice('hr_recruitment.applicant_ui.evaluations.count', $tally['total'], ['count' => $tally['total']]) }}:
                                                @foreach ($tally['counts'] as $value => $count)
                                                    @if ($count > 0){{ __('hr_recruitment.applicant_ui.evaluations.recommendations.'.$value) }} {{ $count }} @endif
                                                @endforeach
                                            </span>
                                        @endif
                                        <x-ui.enum-badge :label="__('hr_recruitment.applicant_ui.interviews.statuses.'.$interview->status)" :tone="Labels::tone($interview->status)" />
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @if ($state['offer'] || $state['canCreateOffer'])
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-panel-border px-3 py-2 text-sm">
                        <span class="font-semibold">{{ __('hr_recruitment.applicant_ui.offers.job_offer') }}</span>
                        @if ($state['offer'])
                            @php $offer = $state['offer']; @endphp
                            <a href="{{ Route::has('hr.recruitment.offers.show') ? route('hr.recruitment.offers.show', ['offerId' => $offer->id]) : '#' }}" wire:navigate class="flex flex-wrap items-center gap-2 underline-offset-4 hover:underline">
                                <span class="tabular-nums {{ $muted }}">v{{ $offer->currentVersion?->version_no }}</span>
                                <x-ui.enum-badge :label="__('hr_recruitment.applicant_ui.offers.statuses.'.$offer->status)" :tone="Labels::tone($offer->status)" />
                                @if ($offer->hiringConversion?->isConverted())
                                    <span class="font-semibold">{{ __('hr_recruitment.applicant_ui.statuses.hired') }}</span>
                                @endif
                            </a>
                        @elseif (Route::has('hr.recruitment.offers.create'))
                            <x-ui.button tone="neutral" size="sm" :href="route('hr.recruitment.offers.create', ['applicationId' => $application->id])" wire:navigate>{{ __('hr_recruitment.applicant_ui.offers.create') }}</x-ui.button>
                        @endif
                    </div>
                @endif
            </section>

            {{-- Internal labels and structured observations. Nothing here can change a status. --}}
            <section class="grid gap-6 p-4 lg:grid-cols-2">
                <div class="min-w-0">
                    <h4 class="{{ $label }}">{{ __('hr_recruitment.applicant_ui.tags.title') }}</h4>
                    <ul class="mt-2 flex flex-wrap gap-1.5">
                        @forelse ($state['tags'] as $assignment)
                            <li wire:key="assignment-{{ $assignment->id }}" title="{{ __('hr_recruitment.applicant_ui.tags.applied_by', ['name' => $assignment->creator?->name ?? '—', 'date' => $fmt($assignment->created_at, true) ?? '—']) }}"
                                class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-semibold {{ $assignment->tag->classes() }}">
                                @if ($assignment->tag->kind !== RecruitmentTag::KIND_NEUTRAL)
                                    <span>{{ $assignment->tag->kindLabel() }}:</span>
                                @endif
                                {{ $assignment->tag->name }}
                                @if ($state['mayTag'])
                                    <button type="button" wire:click="detachTag({{ $application->id }}, {{ $assignment->tag_id }})" class="ms-0.5 cursor-pointer" aria-label="{{ __('hr_recruitment.applicant_ui.tags.remove', ['name' => $assignment->tag->name]) }}"><flux:icon.x-mark variant="micro" class="size-3" /></button>
                                @endif
                            </li>
                        @empty
                            <li class="text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.tags.none') }}</li>
                        @endforelse
                    </ul>
                    @if ($state['mayTag'])
                        @php $available = collect($this->tagOptions)->except($state['tags']->pluck('tag_id')->all()); @endphp
                        <div class="mt-3 flex flex-wrap items-end gap-2">
                            @if ($available->isNotEmpty())
                                <div class="min-w-48 flex-1">
                                    <x-ui.form-select wire:model="tagPick.{{ $application->id }}" :label="__('hr_recruitment.applicant_ui.tags.tag')" :placeholder="__('hr_recruitment.applicant_ui.tags.choose')" searchable>
                                        @foreach ($available as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </x-ui.form-select>
                                </div>
                                <x-ui.button tone="secondary" size="sm" wire:click="attachTag({{ $application->id }})">{{ __('hr_recruitment.applicant_ui.tags.add') }}</x-ui.button>
                            @endif
                            @if ($profile['mayCreateTag'] && $newTagApplicationId !== $application->id)
                                <x-ui.button tone="ghost" size="sm" icon="plus" wire:click="openNewTag({{ $application->id }})">{{ __('hr_recruitment.applicant_ui.tags.create_and_add') }}</x-ui.button>
                            @endif
                        </div>
                        @if ($newTagApplicationId === $application->id)
                            <form wire:submit="createAndAttachTag" class="mt-3 grid items-end gap-2 sm:grid-cols-[minmax(0,1fr)_9rem_8rem]">
                                <x-ui.form-input wire:model="newTagName" :label="__('hr_recruitment.applicant_ui.tags.new_name')" maxlength="40" required dir="auto" />
                                <x-ui.form-select wire:model="newTagKind" :label="__('hr_recruitment.applicant_ui.tags.kind')">
                                    @foreach (RecruitmentTag::kinds() as $kind)
                                        <option value="{{ $kind }}">{{ __('hr_recruitment.applicant_ui.tags.kinds.'.$kind) }}</option>
                                    @endforeach
                                </x-ui.form-select>
                                <x-ui.form-select wire:model="newTagColor" :label="__('hr_recruitment.applicant_ui.tags.color')">
                                    @foreach (array_keys((array) \Modules\Hr\Support\Recruitment\RecruitmentConfig::get('recruitment.tags.palette', [])) as $color)
                                        <option value="{{ $color }}">{{ __('hr_recruitment.applicant_ui.tags.colors.'.$color) }}</option>
                                    @endforeach
                                </x-ui.form-select>
                                <span class="flex gap-2 sm:col-span-3">
                                    <x-ui.button type="submit" tone="primary" size="sm">{{ __('hr_recruitment.applicant_ui.tags.create_and_add') }}</x-ui.button>
                                    <x-ui.button tone="ghost" size="sm" wire:click="$set('newTagApplicationId', null)">{{ __('hr_recruitment.applicant_ui.cancel') }}</x-ui.button>
                                </span>
                            </form>
                        @endif
                    @endif
                </div>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h4 class="{{ $label }}">{{ __('hr_recruitment.applicant_ui.flags.title') }}</h4>
                            <p class="text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.flags.hint') }}</p>
                        </div>
                        @if ($state['mayTag'])
                            <x-ui.button tone="ghost" size="sm" icon="flag" wire:click="openFlag({{ $application->id }})">{{ __('hr_recruitment.applicant_ui.flags.add') }}</x-ui.button>
                        @endif
                    </div>
                    @if ($state['flags']->isEmpty())
                        <p class="mt-2 text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.flags.none') }}</p>
                    @else
                        <ul class="mt-2 space-y-2 text-sm">
                            @foreach ($state['flags'] as $flag)
                                <li wire:key="application-flag-{{ $flag->id }}" @class(['rounded-lg border px-3 py-2', 'border-primary dark:border-brand' => $flag->type === RecruitmentApplicationFlag::TYPE_RED, 'border-panel-border' => $flag->type !== RecruitmentApplicationFlag::TYPE_RED])>
                                    <p class="flex flex-wrap items-center justify-between gap-2">
                                        <span><span @class(['font-semibold', 'text-primary dark:text-brand-text' => $flag->type === RecruitmentApplicationFlag::TYPE_RED])>{{ $flag->typeLabel() }}</span> · {{ $flag->categoryLabel() }}: {{ $flag->reasonLabel() }}</span>
                                        @if ($state['mayTag'])
                                            <x-ui.button tone="ghost" size="sm" wire:click="discardFlag({{ $application->id }}, {{ $flag->id }})" wire:confirm="{{ __('hr_recruitment.applicant_ui.flags.discard_confirm') }}">{{ __('hr_recruitment.applicant_ui.flags.discard') }}</x-ui.button>
                                        @endif
                                    </p>
                                    @if ($flag->note)
                                        <p class="mt-1 whitespace-pre-line" dir="auto">{{ $flag->note }}</p>
                                    @endif
                                    <p class="mt-1 text-xs {{ $muted }}" dir="auto">{{ $flag->creator?->name ?? '—' }} · {{ $fmt($flag->created_at) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        @endif
    </div>
</details>
