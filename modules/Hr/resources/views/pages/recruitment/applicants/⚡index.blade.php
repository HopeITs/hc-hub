<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Actions\Recruitment\RecruitmentFinalistService;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Livewire\Concerns\Recruitment\ManagesApplicantBulk;
use Modules\Hr\Livewire\Concerns\Recruitment\ReadsApplicants;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\RecruitmentApplicant;

/*
 * Applicants: the review pipeline as stage tiles, the campaign lenses
 * (shortlist votes, interview recommendations, finalists), then one row per
 * applicant. Bulk tag / reject / grant access act on the ticked rows; the
 * export downloads what the filters show, for holders of the explicit
 * export permission. Nothing here decides an application on its own.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.applicants')] class extends Component {
    use AuthorizesRecruitment;
    use ManagesApplicantBulk;
    use ReadsApplicants;
    use WithPagination;

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $campaign = '';

    #[Url(except: '')]
    public string $city = '';

    #[Url(except: '')]
    public string $source = '';

    #[Url(except: '')]
    public string $tag = '';

    #[Url(except: '')]
    public string $flagType = '';

    #[Url(except: '')]
    public string $flagCategory = '';

    #[Url(except: '')]
    public string $cv = '';

    #[Url(except: '')]
    public string $previous = '';

    #[Url(except: '')]
    public string $endorsement = '';

    #[Url(except: '')]
    public string $evaluation = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'submitted')]
    public string $sort = 'submitted';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    public function mount(): void
    {
        Gate::authorize('viewAny', RecruitmentApplicant::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['status', 'campaign', 'city', 'source', 'tag', 'flagType', 'flagCategory', 'cv', 'previous', 'endorsement', 'evaluation', 'from', 'to', 'search'], true)) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, ['submitted', 'name', 'status'], true)) {
            return;
        }

        $this->direction = $this->sort === $column && $this->direction === 'desc' ? 'asc' : 'desc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('status', 'city', 'source', 'tag', 'flagType', 'flagCategory', 'cv', 'previous', 'endorsement', 'evaluation', 'from', 'to', 'search');
        $this->resetPage();
        $this->selected = [];
    }

    /** One finalist left in the campaign: select them and move on to the offer. */
    public function finalizeSingleCandidate(RecruitmentFinalistService $finalists): void
    {
        abort_unless(ctype_digit($this->campaign), 404);
        $campaign = HiringCampaign::query()->visibleTo($this->viewer())->findOrFail((int) $this->campaign);

        try {
            $finalists->selectSingle($this->viewer(), $campaign);
        } catch (ValidationException $exception) {
            $this->toast($exception->validator->errors()->first(), false);

            return;
        }

        $this->clearRows();
        $this->toast(__('hr_recruitment.applicant_ui.finalists.single_selected'));
    }
};
?>

@php
    use Modules\Hr\Actions\Recruitment\HiringCampaignStateMachine;
    use Modules\Hr\Enums\HiringCampaignStatus;
    use Modules\Hr\Enums\RecruitmentApplicationStatus as Stage;
    use Modules\Hr\Enums\RecruitmentCvAvailability;
    use Modules\Hr\Enums\RecruitmentEvaluationRecommendation;
    use Modules\Hr\Models\RecruitmentApplication;
    use Modules\Hr\Models\RecruitmentApplicationFlag;
    use Modules\Hr\Support\Recruitment\Access;
    use Modules\Hr\Support\Recruitment\Labels;

    $muted = 'text-secondary-medium dark:text-ink-muted';
    $rows = $this->rows;
    $sets = $this->indicatorSets;
    $schedulable = $this->schedulable;
    $stages = $this->stageCounts;
    $lens = $this->campaignLens;
    $options = $this->options;
    $states = app(HiringCampaignStateMachine::class);
    $mayEvaluations = $this->may('recruitment-applicants.view-evaluations');
    $mayTag = Gate::allows('bulkTag', RecruitmentApplication::class);
    $mayReject = Gate::allows('bulkReject', RecruitmentApplication::class);
    $mayAccess = Gate::allows('bulkManageReviewParticipants', RecruitmentApplication::class);
    $maySelect = $mayTag || $mayReject || $mayAccess;
    $mayExport = Access::hasExplicitPermission($this->viewer(), 'recruitment-applicants.export');
    $mayManageTags = Gate::allows('create', \Modules\Hr\Models\RecruitmentTag::class);
    $pipeline = [Stage::Submitted, Stage::Shortlisted, Stage::InterviewScheduled, Stage::Interviewed, Stage::Finalist, Stage::Selected];
    $filtered = collect([$status, $city, $source, $tag, $flagType, $flagCategory, $cv, $previous, $endorsement, $evaluation, $from, $to, trim($search)])->contains(fn ($value) => $value !== '');
    $sortIcon = fn (string $column) => $sort === $column ? ($direction === 'asc' ? 'chevron-up' : 'chevron-down') : null;
    $chip = fn (bool $on) => 'inline-flex min-h-9 cursor-pointer items-center gap-2 rounded-lg border px-3 text-sm transition '.($on
        ? 'border-secondary-dark bg-secondary-soft font-semibold text-secondary-dark dark:border-ink dark:bg-surface-3 dark:text-ink'
        : 'border-panel-border bg-white text-secondary-dark hover:border-secondary-light dark:bg-surface-1 dark:text-ink');
@endphp

<x-desk.register class="@container" heading-id="recruitment-applicants-heading" :title="__('hr_recruitment.hub.titles.applicants')" :count="$rows->total()">
    <x-slot:figures>{{ __('hr_recruitment.applicant_ui.applicants_hint') }}</x-slot:figures>

    <x-slot:actions>
        @if ($mayManageTags)
            <x-ui.button tone="secondary" icon="tag" wire:click="$set('showTags', true)">{{ __('hr_recruitment.applicant_ui.tags.manage_title') }}</x-ui.button>
        @endif
        @if ($mayExport && $rows->total() > 0)
            <form method="POST" action="{{ route('hr.recruitment.applicants.export') }}" class="contents">
                @csrf
                @foreach ($this->filterValues() as $key => $value)
                    @if (in_array($key, ['campaign', 'status', 'city', 'tag', 'source', 'cv', 'previous', 'submitted_from', 'submitted_to', 'search'], true))
                        <input type="hidden" name="filters[{{ $key }}]" value="{{ $value }}">
                    @endif
                @endforeach
                <x-ui.button type="submit" tone="secondary" icon="arrow-down-tray" :title="__('hr_recruitment.hub.applicants.export_hint')">{{ __('hr_recruitment.hub.applicants.export') }}</x-ui.button>
            </form>
        @endif
    </x-slot:actions>

    <x-slot:search>
        <x-desk.register-search :label="__('hr_recruitment.hub.search')" :placeholder="__('hr_recruitment.hub.search_applicants')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        <x-ui.header-filter-select :label="__('hr_recruitment.applicant_ui.filters.campaign')" wire:model.live="campaign" :value="$campaign" searchable>
            <option value="">{{ __('hr_recruitment.applicant_ui.filters.all_campaigns') }}</option>
            @foreach ($options['campaigns'] as $id => $label)
                <option value="{{ $id }}">{{ $label }}</option>
            @endforeach
        </x-ui.header-filter-select>
        <x-ui.header-filter-select :label="__('hr_recruitment.applicant_ui.status')" wire:model.live="status" :value="$status">
            <option value="">{{ __('hr_recruitment.hub.all_statuses') }}</option>
            @foreach ($options['statuses'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-ui.header-filter-select>
        @if ($options['cities'] !== [])
            <x-ui.header-filter-select :label="__('hr_recruitment.applicant_ui.filters.city')" wire:model.live="city" :value="$city">
                <option value="">{{ __('hr_recruitment.hub.all_cities') }}</option>
                @foreach ($options['cities'] as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif
        <x-ui.header-filter-select :label="__('hr_recruitment.applicant_ui.source')" wire:model.live="source" :value="$source">
            <option value="">{{ __('hr_recruitment.hub.all') }}</option>
            @foreach ($options['sources'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-ui.header-filter-select>
        <x-ui.header-filter-select :label="__('hr_recruitment.applicant_ui.cv')" wire:model.live="cv" :value="$cv">
            <option value="">{{ __('hr_recruitment.hub.all') }}</option>
            @foreach ($options['cv'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-ui.header-filter-select>
        <x-ui.header-filter-select :label="__('hr_recruitment.applicant_ui.previous_applicant')" wire:model.live="previous" :value="$previous">
            <option value="">{{ __('hr_recruitment.hub.all') }}</option>
            @foreach ($options['previous'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-ui.header-filter-select>
        @if ($options['tags'] !== [])
            <x-ui.header-filter-select :label="__('hr_recruitment.applicant_ui.tags.title')" wire:model.live="tag" :value="$tag" searchable>
                <option value="">{{ __('hr_recruitment.hub.all') }}</option>
                @foreach ($options['tags'] as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif
        <x-ui.header-filter-select :label="__('hr_recruitment.applicant_ui.flags.type')" wire:model.live="flagType" :value="$flagType">
            <option value="">{{ __('hr_recruitment.hub.all') }}</option>
            @foreach ($options['flagTypes'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-ui.header-filter-select>
        <x-ui.header-filter-select :label="__('hr_recruitment.applicant_ui.flags.category')" wire:model.live="flagCategory" :value="$flagCategory">
            <option value="">{{ __('hr_recruitment.hub.all') }}</option>
            @foreach ($options['flagCategories'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-ui.header-filter-select>
        <x-ui.header-filter-select :label="__('hr_recruitment.applicant_ui.endorsement.groups_label')" wire:model.live="endorsement" :value="$endorsement">
            <option value="">{{ __('hr_recruitment.hub.all') }}</option>
            @foreach ($options['endorsements'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-ui.header-filter-select>
        @if ($mayEvaluations)
            <x-ui.header-filter-select :label="__('hr_recruitment.applicant_ui.dashboard.evaluation_filter')" wire:model.live="evaluation" :value="$evaluation">
                <option value="">{{ __('hr_recruitment.hub.all') }}</option>
                @foreach ($options['evaluations'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif
        <label class="flex items-center gap-1.5 text-sm">
            <span class="{{ $muted }}">{{ __('hr_recruitment.hub.applicants.date_from') }}</span>
            <input type="date" wire:model.live="from" class="h-9 rounded-lg border border-panel-border bg-white px-2 text-sm dark:bg-surface-1" />
        </label>
        <label class="flex items-center gap-1.5 text-sm">
            <span class="{{ $muted }}">{{ __('hr_recruitment.hub.applicants.date_to') }}</span>
            <input type="date" wire:model.live="to" class="h-9 rounded-lg border border-panel-border bg-white px-2 text-sm dark:bg-surface-1" />
        </label>
        @if ($filtered)
            <x-ui.button tone="ghost" size="sm" icon="x-mark" wire:click="clearFilters">{{ __('hr_recruitment.hub.change_filters') }}</x-ui.button>
        @endif
    </x-slot:filters>

    <div class="space-y-4">
        {{-- The pipeline: each stage is a filter and shows how many applicants stand on it. --}}
        <section aria-labelledby="review-stage-title" class="rounded-panel border border-panel-border bg-white p-4 dark:bg-surface-1">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 id="review-stage-title" class="type-label {{ $muted }}">{{ __('hr_recruitment.applicant_ui.filters.review_stage') }}</h2>
                <p class="text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.filters.review_stage_hint') }}</p>
            </div>
            <div role="group" aria-label="{{ __('hr_recruitment.applicant_ui.filters.review_stage') }}" class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
                @foreach (['' => $stages['all'], ...array_combine($pipeline, array_map(fn ($stage) => $stages['statuses'][$stage] ?? 0, $pipeline))] as $stage => $count)
                    @php $on = $status === (string) $stage; @endphp
                    <button type="button" wire:click="$set('status', '{{ $stage }}')" aria-pressed="{{ $on ? 'true' : 'false' }}" wire:key="stage-{{ $stage ?: 'all' }}"
                        @class(['flex min-h-16 cursor-pointer flex-col items-start justify-between gap-1 rounded-lg border px-3 py-2.5 text-start transition',
                            'border-secondary-dark bg-secondary-soft dark:border-ink dark:bg-surface-3' => $on,
                            'border-panel-border hover:border-secondary-light' => ! $on])>
                        <span @class(['text-xl font-semibold tabular-nums', $muted => $count === 0])>{{ $count }}</span>
                        <span class="text-xs {{ $on ? 'font-semibold' : $muted }}">{{ $stage === '' ? __('hr_recruitment.applicant_ui.filters.all') : __('hr_recruitment.applicant_ui.statuses.'.$stage) }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        {{-- The campaign lenses: votes and evaluations only mean something inside one campaign. --}}
        @if ($lens !== null)
            <div class="grid gap-4 lg:grid-cols-2">
                <section class="rounded-panel border border-panel-border bg-white p-4 dark:bg-surface-1">
                    <h2 class="type-label {{ $muted }}">{{ __('hr_recruitment.applicant_ui.endorsement.groups_label') }}</h2>
                    <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.endorsement.groups_hint') }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($lens['endorsements'] as $group => $count)
                            <button type="button" wire:click="$set('endorsement', '{{ $endorsement === $group ? '' : $group }}')" aria-pressed="{{ $endorsement === $group ? 'true' : 'false' }}" class="{{ $chip($endorsement === $group) }}">
                                {{ __('hr_recruitment.applicant_ui.endorsement.group_'.$group) }} <span class="tabular-nums {{ $muted }}">{{ $count }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>
                @if ($lens['evaluations'] !== null)
                    <section class="rounded-panel border border-panel-border bg-white p-4 dark:bg-surface-1">
                        <h2 class="type-label {{ $muted }}">{{ __('hr_recruitment.applicant_ui.dashboard.title') }}</h2>
                        <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.dashboard.hint') }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($lens['evaluations'] as $recommendation => $count)
                                <button type="button" wire:click="$set('evaluation', '{{ $evaluation === $recommendation ? '' : $recommendation }}')" aria-pressed="{{ $evaluation === $recommendation ? 'true' : 'false' }}" class="{{ $chip($evaluation === $recommendation) }}">
                                    {{ $recommendation === 'none' ? __('hr_recruitment.applicant_ui.dashboard.not_evaluated') : __('hr_recruitment.applicant_ui.evaluations.recommendations.'.$recommendation) }}
                                    <span class="tabular-nums {{ $muted }}">{{ $count }}</span>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            @if ($lens['finalists'] > 0)
                {{-- The one place on the list that asks for a decision. --}}
                <section class="flex flex-wrap items-center justify-between gap-4 rounded-panel border border-panel-border bg-white p-4 dark:bg-surface-1">
                    <div class="flex min-w-0 items-start gap-3">
                        <flux:icon.trophy variant="mini" class="mt-0.5 size-5 {{ $muted }}" />
                        <div class="min-w-0">
                            <h2 class="font-semibold">{{ __('hr_recruitment.applicant_ui.finalists.title') }}</h2>
                            <p class="text-sm {{ $muted }}">
                                {{ trans_choice('hr_recruitment.applicant_ui.finalists.count', $lens['finalists'], ['count' => $lens['finalists']]) }}
                                {{ $lens['finalists'] === 1 ? __('hr_recruitment.applicant_ui.finalists.single_hint') : __('hr_recruitment.applicant_ui.finalists.comparison_hint') }}
                            </p>
                        </div>
                    </div>
                    @if ($lens['finalists'] === 1 && $this->may('recruitment-applicants.accept'))
                        <x-ui.button tone="primary" wire:click="finalizeSingleCandidate" wire:loading.attr="disabled" wire:target="finalizeSingleCandidate">{{ __('hr_recruitment.applicant_ui.finalists.proceed_offer') }}</x-ui.button>
                    @endif
                </section>
            @endif
        @endif

        @if ($bulkResult !== [])
            <div role="status" class="flex flex-wrap items-start justify-between gap-3 rounded-lg border border-panel-border bg-secondary-soft/40 px-4 py-3 text-sm dark:bg-surface-2">
                <ul class="space-y-0.5">
                    @foreach ($bulkResult as $line)
                        <li @class(['font-semibold' => $loop->first])>{{ $line }}</li>
                    @endforeach
                </ul>
                <x-ui.button tone="ghost" size="sm" wire:click="dismissBulkResult">{{ __('hr_recruitment.applicant_ui.tags.dismiss') }}</x-ui.button>
            </div>
        @endif

        @if ($maySelect && $rows->total() > 0)
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="{{ $muted }}">{{ __('hr_recruitment.hub.applicants.selected', ['count' => count($selected)]) }}</span>
                <x-ui.button tone="ghost" size="sm" wire:click="selectMatching">{{ __('hr_recruitment.hub.applicants.select_matching') }}</x-ui.button>
                @if ($selected !== [])
                    <x-ui.button tone="ghost" size="sm" wire:click="clearSelection">{{ __('hr_recruitment.hub.applicants.clear_selection') }}</x-ui.button>
                    <span class="ms-auto flex flex-wrap gap-2">
                        @if ($mayTag)
                            <x-ui.button tone="secondary" size="sm" icon="tag" wire:click="openBulk('tags')">{{ __('hr_recruitment.applicant_ui.tags.bulk_action') }}</x-ui.button>
                        @endif
                        @if ($mayAccess)
                            <x-ui.button tone="secondary" size="sm" icon="user-plus" wire:click="openBulk('access')">{{ __('hr_recruitment.applicant_ui.profile_reviews.bulk_action') }}</x-ui.button>
                        @endif
                        @if ($mayReject)
                            <x-ui.button tone="secondary" size="sm" icon="x-circle" wire:click="openBulk('reject')">{{ __('hr_recruitment.applicant_ui.decisions.bulk_action') }}</x-ui.button>
                        @endif
                    </span>
                @endif
            </div>
            @error('selected')<p class="text-sm text-primary">{{ $message }}</p>@enderror
        @endif

        @if ($rows->isEmpty())
            <x-ui.empty-state icon="users" :heading="$filtered || $campaign !== '' ? __('hr_recruitment.hub.no_matching') : __('hr_recruitment.hub.none')" />
        @else
            <x-ui.data-table :caption="__('hr_recruitment.hub.titles.applicants')">
                <x-slot:head>
                    @if ($maySelect)
                        <th class="w-10!"><span class="sr-only">{{ __('hr_recruitment.hub.select') }}</span></th>
                    @endif
                    <th><button type="button" wire:click="sortBy('name')" class="inline-flex items-center gap-1">{{ __('hr_recruitment.applicant_ui.name') }}@if ($sortIcon('name'))<flux:icon :name="$sortIcon('name')" variant="micro" class="size-3.5" />@endif</button></th>
                    <th>{{ __('hr_recruitment.applicant_ui.campaign') }}</th>
                    <th><button type="button" wire:click="sortBy('submitted')" class="inline-flex items-center gap-1">{{ __('hr_recruitment.applicant_ui.applied_at') }}@if ($sortIcon('submitted'))<flux:icon :name="$sortIcon('submitted')" variant="micro" class="size-3.5" />@endif</button></th>
                    <th><button type="button" wire:click="sortBy('status')" class="inline-flex items-center gap-1">{{ __('hr_recruitment.applicant_ui.status') }}@if ($sortIcon('status'))<flux:icon :name="$sortIcon('status')" variant="micro" class="size-3.5" />@endif</button></th>
                    <th>{{ __('hr_recruitment.applicant_ui.endorsement.column') }}</th>
                    @if ($mayEvaluations)
                        <th>{{ __('hr_recruitment.applicant_ui.dashboard.evaluation_column') }}</th>
                    @endif
                    <th>{{ __('hr_recruitment.applicant_ui.cv') }}</th>
                    <th>{{ __('hr_recruitment.applicant_ui.tags.title') }}</th>
                    <th>{{ __('hr_recruitment.applicant_ui.indicators.column') }}</th>
                    <th class="w-36!"><span class="sr-only">{{ __('hr_recruitment.applicant_ui.next_action') }}</span></th>
                </x-slot:head>

                @foreach ($rows as $application)
                    @php
                        $applicant = $application->applicant;
                        $campaignRow = $application->campaign;
                        $effective = $campaignRow ? $states->effective($campaignRow) : null;
                        $votes = $application->endorsements->pluck('voter_role')->unique()->count();
                        $group = $votes >= 2 ? 'preferred' : ($votes === 1 ? 'alternative' : 'unvoted');
                        $set = $sets[$application->id] ?? null;
                        $canSchedule = isset($schedulable[$application->id]);
                    @endphp
                    <tr wire:key="applicant-{{ $application->id }}">
                        @if ($maySelect)
                            <td><input type="checkbox" wire:model.live="selected" value="{{ $application->id }}" aria-label="{{ __('hr_recruitment.hub.select') }}: {{ $applicant?->full_name }}" class="size-4 rounded border-secondary-light" /></td>
                        @endif
                        <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.name')">
                            <a href="{{ route('hr.recruitment.applicants.show', ['applicantId' => $application->applicant_id]) }}" wire:navigate class="font-semibold underline-offset-4 hover:underline"><bdi>{{ $applicant?->full_name ?? '—' }}</bdi></a>
                            <span class="mt-0.5 flex flex-wrap gap-1 text-xs {{ $muted }}">
                                <span>{{ RecruitmentApplicant::sourceLabel($applicant?->source) }}</span>
                                @if ($application->has_previous_application)
                                    · <span>{{ __('hr_recruitment.applicant_ui.previous_applicant_flag') }}</span>
                                @endif
                            </span>
                        </x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.campaign')">
                            @if ($campaignRow)
                                <span class="block whitespace-nowrap font-medium">{{ __('hr_recruitment.applicant_ui.campaign_number', ['number' => $campaignRow->id]) }}</span>
                            @endif
                            <span class="block text-xs {{ $muted }}" dir="auto">{{ $campaignRow?->vacancy?->position?->title ?? __('hr_recruitment.applicant_ui.no_vacancy') }}</span>
                            @if ($effective !== null && ! in_array($effective, [HiringCampaignStatus::Published->value, HiringCampaignStatus::Open->value], true))
                                <span class="mt-1 inline-block text-xs {{ $muted }}">{{ Labels::enum('HiringCampaignStatus', $effective) }}</span>
                            @endif
                        </x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.applied_at')">
                            <span class="whitespace-nowrap tabular-nums">{{ $application->submitted_at?->translatedFormat(__('formats.date')) ?? '—' }}</span>
                        </x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.status')">
                            <x-ui.enum-badge :label="__('hr_recruitment.applicant_ui.statuses.'.$application->status)" :tone="Labels::tone($application->status)" />
                        </x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.endorsement.column')">
                            <span @class(['whitespace-nowrap text-xs', 'font-semibold' => $group === 'preferred', $muted => $group === 'unvoted'])>{{ __('hr_recruitment.applicant_ui.endorsement.group_'.$group) }}</span>
                        </x-ui.data-table.cell>
                        @if ($mayEvaluations)
                            @php $tally = $application->interviews->flatMap->evaluations->countBy('recommendation'); @endphp
                            <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.dashboard.evaluation_column')">
                                @if ($tally->isEmpty())
                                    <span class="text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.dashboard.not_evaluated') }}</span>
                                @else
                                    <span class="flex flex-wrap gap-1">
                                        @foreach (RecruitmentEvaluationRecommendation::values() as $recommendation)
                                            @if ((int) $tally->get($recommendation, 0) > 0)
                                                <span class="inline-flex whitespace-nowrap rounded-md bg-secondary-soft px-2 py-0.5 text-xs font-semibold dark:bg-surface-3">{{ __('hr_recruitment.applicant_ui.evaluations.recommendations.'.$recommendation) }} <span class="ms-1 tabular-nums">{{ $tally->get($recommendation) }}</span></span>
                                            @endif
                                        @endforeach
                                    </span>
                                @endif
                            </x-ui.data-table.cell>
                        @endif
                        <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.cv')">
                            <span class="whitespace-nowrap text-xs">{{ __('hr_recruitment.applicant_ui.cv_availability.'.RecruitmentCvAvailability::of($application->cvFiles)) }}</span>
                        </x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.tags.title')">
                            <span class="flex flex-wrap gap-1">
                                @foreach ($application->tags as $applied)
                                    <span class="inline-flex whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-semibold {{ $applied->classes() }}">{{ $applied->name }}</span>
                                @endforeach
                                @foreach ($application->flags as $flag)
                                    <span @class(['inline-flex whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-semibold', 'border border-primary text-primary dark:border-brand dark:text-brand-text' => $flag->type === RecruitmentApplicationFlag::TYPE_RED, 'bg-secondary-soft text-secondary-dark dark:bg-surface-3 dark:text-ink' => $flag->type !== RecruitmentApplicationFlag::TYPE_RED])>{{ $flag->typeLabel() }}: {{ $flag->reasonLabel() }}</span>
                                @endforeach
                            </span>
                        </x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.indicators.column')">
                            @if ($set)
                                @php $lines = collect($set->indicators)->map(fn ($indicator) => $indicator->label().': '.__('hr_recruitment.applicant_ui.indicators.outcomes.'.$indicator->outcome)); @endphp
                                <span title="{{ $lines->implode("\n") }}" class="inline-flex cursor-help flex-col whitespace-nowrap text-xs font-semibold">
                                    {{ $set->assessedCount() === 0 ? __('hr_recruitment.applicant_ui.indicators.none_assessed') : __('hr_recruitment.applicant_ui.indicators.summary', ['met' => $set->metCount(), 'assessed' => $set->assessedCount()]) }}
                                    @if ($set->assessedCount() > 0)
                                        <span aria-hidden="true" class="mt-1 flex gap-0.5">
                                            @for ($i = 0; $i < $set->assessedCount(); $i++)
                                                <span @class(['h-1 w-4 rounded-full', 'bg-secondary-dark dark:bg-ink' => $i < $set->metCount(), 'bg-secondary-soft dark:bg-surface-3' => $i >= $set->metCount()])></span>
                                            @endfor
                                        </span>
                                    @endif
                                </span>
                                <span class="sr-only">({{ $lines->implode(__('hr_recruitment.applicant_ui.list_separator')) }})</span>
                            @endif
                        </x-ui.data-table.cell>
                        <td>
                            @if ($canSchedule && Route::has('hr.recruitment.interviews.create'))
                                <x-ui.button tone="neutral" size="sm" :href="route('hr.recruitment.interviews.create', ['applicationId' => $application->id])" wire:navigate class="whitespace-nowrap">
                                    {{ $application->status === Stage::Finalist ? __('hr_recruitment.applicant_ui.interviews.schedule_final') : __('hr_recruitment.applicant_ui.interviews.schedule') }}
                                </x-ui.button>
                            @else
                                <x-ui.button tone="secondary" size="sm" :href="route('hr.recruitment.applicants.show', ['applicantId' => $application->applicant_id])" wire:navigate class="whitespace-nowrap">{{ __('hr_recruitment.applicant_ui.profile.open') }}</x-ui.button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>

            <div class="mt-4">
                <flux:pagination :paginator="$rows" scroll-to="#recruitment-applicants-heading" />
            </div>
        @endif
    </div>

    @include('hr::pages.recruitment.applicants._index-dialogs')
</x-desk.register>
