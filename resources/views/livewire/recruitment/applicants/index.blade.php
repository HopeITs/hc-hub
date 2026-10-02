<div class="space-y-6 p-4 sm:p-6">
    <div>
        <h1 id="applicants-title" tabindex="-1" class="scroll-mt-24 text-h3 font-semibold">{{ __('recruitment-applicant.applicants') }}</h1>
        <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-applicant.applicants_hint') }}</p>
    </div>

    @php
        $reviewStatuses = [
            ['status' => null, 'label' => __('recruitment-applicant.filters.all')],
            ['status' => \App\Enums\RecruitmentApplicationStatus::Submitted, 'label' => __('recruitment-applicant.statuses.submitted')],
            ['status' => \App\Enums\RecruitmentApplicationStatus::Shortlisted, 'label' => __('recruitment-applicant.statuses.shortlisted')],
            ['status' => \App\Enums\RecruitmentApplicationStatus::InterviewScheduled, 'label' => __('recruitment-applicant.statuses.interview_scheduled')],
            ['status' => \App\Enums\RecruitmentApplicationStatus::Interviewed, 'label' => __('recruitment-applicant.statuses.interviewed')],
            ['status' => \App\Enums\RecruitmentApplicationStatus::Selected, 'label' => __('recruitment-applicant.statuses.selected')],
        ];
    @endphp
    <section aria-label="{{ __('recruitment-applicant.filters.review_stage') }}" class="rounded-surface border border-neutral-200 bg-white p-4">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="font-semibold">{{ __('recruitment-applicant.filters.review_stage') }}</h2>
                <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-applicant.filters.review_stage_hint') }}</p>
            </div>
            <div class="w-full sm:w-72">
                <label for="applicant-campaign-filter" class="mb-1 block text-sm font-semibold text-secondary-dark">{{ __('recruitment-applicant.filters.campaign') }}</label>
                <select id="applicant-campaign-filter" wire:model.live="activeCampaignId" class="w-full rounded-control border-neutral-300 bg-white text-sm focus:border-primary focus:ring-primary">
                    <option value="">{{ __('recruitment-applicant.filters.all_campaigns') }}</option>
                    @foreach ($campaignOptions as $campaignId => $campaignLabel)
                        <option value="{{ $campaignId }}">{{ $campaignLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex w-full flex-wrap gap-2">
                @foreach ($reviewStatuses as $stage)
                    @php
                        $status = $stage['status'];
                        $label = $stage['label'];
                        $isActive = $activeStatus === $status && $activeEndorsementGroup === null;
                        $count = $status === null ? $counts->sum() : (int) ($counts[$status] ?? 0);
                    @endphp
                    <button type="button" wire:click="filterByStatus(@js($status))"
                        class="inline-flex min-h-9 items-center gap-1.5 rounded-control border px-3 py-1.5 text-sm font-semibold {{ $isActive ? 'border-primary bg-primary text-white' : 'border-neutral-300 bg-white text-secondary-dark hover:bg-neutral-50' }}">
                        <span>{{ $label }}</span>
                        <span class="rounded-full px-1.5 py-0.5 text-xs {{ $isActive ? 'bg-white/20 text-white' : 'bg-neutral-100 text-secondary-dark' }}">{{ $count }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Endorsement groups — only when a campaign is selected --}}
    @if ($endorsementCounts !== null)
    @php
        $endorsementGroups = [
            ['group' => 'preferred',   'label' => __('recruitment-applicant.endorsement.group_preferred'),   'color' => 'text-green-700 border-green-300 bg-green-50',   'activeColor' => 'border-green-600 bg-green-600 text-white'],
            ['group' => 'alternative', 'label' => __('recruitment-applicant.endorsement.group_alternative'), 'color' => 'text-amber-700 border-amber-300 bg-amber-50',   'activeColor' => 'border-amber-500 bg-amber-500 text-white'],
            ['group' => 'unvoted',     'label' => __('recruitment-applicant.endorsement.group_unvoted'),     'color' => 'text-secondary-dark border-neutral-300 bg-white', 'activeColor' => 'border-neutral-500 bg-neutral-500 text-white'],
        ];
    @endphp
    <section aria-label="{{ __('recruitment-applicant.endorsement.groups_label') }}" class="rounded-surface border border-neutral-200 bg-white p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold">{{ __('recruitment-applicant.endorsement.groups_label') }}</h2>
                <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-applicant.endorsement.groups_hint') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($endorsementGroups as $eg)
                    @php
                        $isActive = $activeEndorsementGroup === $eg['group'];
                        $count = $endorsementCounts[$eg['group']] ?? 0;
                    @endphp
                    <button type="button" wire:click="filterByEndorsementGroup(@js($eg['group']))"
                        class="inline-flex min-h-9 items-center gap-1.5 rounded-control border px-3 py-1.5 text-sm font-semibold {{ $isActive ? $eg['activeColor'] : $eg['color'] }}">
                        <span>{{ $eg['label'] }}</span>
                        <span class="rounded-full px-1.5 py-0.5 text-xs {{ $isActive ? 'bg-white/20' : 'bg-black/5' }}">{{ $count }}</span>
                    </button>
                @endforeach
                @if ($activeEndorsementGroup !== null)
                    <button type="button" wire:click="filterByEndorsementGroup(null)"
                        class="inline-flex min-h-9 items-center rounded-control border border-neutral-300 bg-white px-3 py-1.5 text-sm text-secondary-dark hover:bg-neutral-50">
                        {{ __('recruitment-applicant.endorsement.clear_group') }}
                    </button>
                @endif
            </div>
        </div>
    </section>
    @endif

    <livewire:recruitment.applicants.bulk-tags />

    <livewire:recruitment.applicants.bulk-reject />

    <livewire:recruitment.applicants.application-table
        :stage-status="$activeStatus"
        :campaign-filter="$activeCampaignId"
        :endorsement-group="$activeEndorsementGroup"
    />

    <livewire:recruitment.applicants.tag-manager />
</div>
