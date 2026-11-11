<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Actions\Recruitment\HiringCampaignStateMachine;
use Modules\Hr\Actions\Recruitment\RecruitmentApproverEligibility;
use Modules\Hr\Actions\Recruitment\RecruitmentRequestListQuery;
use Modules\Hr\Enums\HiringCampaignStatus;
use Modules\Hr\Enums\RecruitmentRequestStatus;
use Modules\Hr\Enums\RecruitmentRequestType;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\RecruitmentRequest;
use Modules\Hr\Models\Vacancy;
use Modules\Hr\Support\Recruitment\Labels;

new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.requests')] class extends Component {
    use AuthorizesRecruitment;
    use WithPagination;

    #[Url(as: 'show', except: 'all')]
    public string $viewFilter = RecruitmentRequestListQuery::ALL;

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $search = '';

    public function mount(): void
    {
        $this->requirePermission('recruitment-requests.view');
        Gate::authorize('viewAny', RecruitmentRequest::class);

        if (! in_array($this->viewFilter, $this->views, true)) {
            $this->viewFilter = RecruitmentRequestListQuery::ALL;
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['viewFilter', 'status', 'type', 'search'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Relationship choices that can be useful to this person: approvers keep
     * their review queue even when empty, team requests appear once one is in
     * scope, and with neither the page is simply their own list.
     *
     * @return list<string>
     */
    #[Computed]
    public function views(): array
    {
        $lists = app(RecruitmentRequestListQuery::class);
        $views = [];

        if (app(RecruitmentApproverEligibility::class)->mayReceiveApprovals($this->viewer())) {
            $views[] = RecruitmentRequestListQuery::NEEDS_REVIEW;
        }

        $views[] = RecruitmentRequestListQuery::MINE;

        if ($lists->forUser($this->viewer(), RecruitmentRequestListQuery::TEAM)->exists()) {
            $views[] = RecruitmentRequestListQuery::TEAM;
        }

        if (count($views) > 1) {
            $views[] = RecruitmentRequestListQuery::ALL;
        }

        return $views;
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $filter = in_array($this->viewFilter, $this->views, true) ? $this->viewFilter : RecruitmentRequestListQuery::ALL;

        return app(RecruitmentRequestListQuery::class)
            ->forUser($this->viewer(), $filter)
            ->select(['id', 'request_number', 'requester_user_id', 'request_type', 'mode', 'status', 'scope_city_id', 'target_position_id', 'draft_payload_json', 'created_at'])
            ->with([
                'requester:id,email,employee_id',
                'scopeCity:id,name_en,name_ar',
                'targetPosition:id,title_en,title_ar',
                'latestVersion',
                'latestVersion.positionDetail:id,request_version_id,priority',
                'vacancy:id,created_from_request_id,status,closed_at,open_at,filled_count,needed_count',
                'vacancy.campaigns:id,vacancy_id,status,starts_at,ends_at',
            ])
            ->when($this->status !== '' && ctype_digit($this->status), fn ($query) => $query->where('status', (int) $this->status))
            ->when($this->type !== '' && ctype_digit($this->type), fn ($query) => $query->where('request_type', (int) $this->type))
            ->when(trim($this->search) !== '', fn ($query) => $query->whereLike('request_number', '%'.trim($this->search).'%'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25);
    }

    /** @return array<int, true> */
    #[Computed]
    public function pendingReview(): array
    {
        return array_fill_keys(app(RecruitmentRequestListQuery::class)->pendingRequestIds($this->viewer()), true);
    }

    #[Computed]
    public function mayRunCampaigns(): bool
    {
        return $this->may('recruitment-campaigns.manage');
    }

    /**
     * The campaign shortcut of an approved request: continue the ongoing one,
     * else view the latest and start a new one while the seat is available.
     *
     * @return list<array{url: string, label: string, primary: bool}>
     */
    public function campaignLinks(RecruitmentRequest $request): array
    {
        if (! in_array((int) $request->status, [RecruitmentRequestStatus::Approved->value, RecruitmentRequestStatus::Applied->value], true) || $request->vacancy === null) {
            return [];
        }

        $vacancy = $request->vacancy;
        $states = app(HiringCampaignStateMachine::class);
        $campaigns = $vacancy->campaigns->sortByDesc('id');
        $ongoing = $campaigns->first(fn (HiringCampaign $campaign): bool => in_array(
            $states->effective($campaign),
            [HiringCampaignStatus::Draft->value, HiringCampaignStatus::Published->value, HiringCampaignStatus::Open->value],
            true,
        ));

        if ($ongoing !== null) {
            return [[
                'url' => route('hr.recruitment.campaigns.show', ['campaignId' => $ongoing->id]),
                'label' => (int) $ongoing->status === HiringCampaignStatus::Draft->value ? __('hr_recruitment.campaign_ui.continue_campaign') : __('hr_recruitment.campaign_ui.view_campaign'),
                'primary' => true,
            ]];
        }

        $links = [];

        if ($latest = $campaigns->first()) {
            $links[] = ['url' => route('hr.recruitment.campaigns.show', ['campaignId' => $latest->id]), 'label' => __('hr_recruitment.campaign_ui.view_campaign'), 'primary' => false];
        }

        $available = $vacancy->status === Vacancy::OPEN && $vacancy->closed_at === null
            && ($vacancy->open_at === null || $vacancy->open_at->isPast())
            && $vacancy->filled_count < $vacancy->needed_count;

        if ($available) {
            $links[] = ['url' => route('hr.recruitment.campaigns.create', ['vacancyId' => $vacancy->id]), 'label' => __('hr_recruitment.campaign_ui.new_campaign'), 'primary' => true];
        }

        return $links;
    }
};
?>

@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $rows = $this->rows;
    $pending = $this->pendingReview;
    $mayCampaigns = $this->mayRunCampaigns;
@endphp

<x-desk.register class="@container" heading-id="recruitment-requests-heading" :title="__('hr_recruitment.hub.titles.requests')" :count="$rows->total()">
    <x-slot:actions>
        <div class="flex flex-wrap items-center justify-end gap-2">
            @if ($this->may('recruitment-requests.manage'))
                <x-ui.button tone="action" icon="users" :href="route('hr.recruitment.requests.team')" wire:navigate>{{ __('hr_recruitment.hub.team_staffing') }}</x-ui.button>
            @endif
            @if ($this->may('recruitment-requests.direct-open'))
                <x-ui.button tone="action" icon="bolt" :href="route('hr.recruitment.requests.direct-open')" wire:navigate>{{ __('hr_recruitment.hub.direct_open') }}</x-ui.button>
            @endif
            @if ($this->may('recruitment-requests.manage'))
                <x-ui.button tone="primary" icon="plus" class="page-action page-action-primary" :href="route('hr.recruitment.requests.create')" wire:navigate data-quick-create>{{ __('hr_recruitment.hub.new_request') }}</x-ui.button>
            @endif
        </div>
    </x-slot:actions>

    <x-slot:search>
        <x-desk.register-search :label="__('hr_recruitment.hub.search_number')" :placeholder="__('hr_recruitment.number')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        @if (count($this->views) > 1)
            <x-ui.header-filter-select :label="__('hr_recruitment.request_view')" wire:model.live="viewFilter" :value="$viewFilter">
                @foreach ($this->views as $option)
                    <option value="{{ $option }}">{{ __('hr_recruitment.request_views.'.$option) }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif
        <x-ui.header-filter-select :label="__('hr_recruitment.status')" wire:model.live="status" :value="$status">
            <option value="">{{ __('hr_recruitment.hub.all_statuses') }}</option>
            @foreach (RecruitmentRequestStatus::cases() as $case)
                <option value="{{ $case->value }}">{{ Labels::enum('RecruitmentRequestStatus', $case->value) }}</option>
            @endforeach
        </x-ui.header-filter-select>
        <x-ui.header-filter-select :label="__('hr_recruitment.type')" wire:model.live="type" :value="$type">
            <option value="">{{ __('hr_recruitment.hub.all_types') }}</option>
            @foreach (RecruitmentRequestType::cases() as $case)
                @continue($case->value === 0)
                <option value="{{ $case->value }}">{{ Labels::enum('RecruitmentRequestType', $case->value) }}</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    @if ($rows->isEmpty())
        <x-ui.empty-state icon="briefcase" :heading="$search === '' && $status === '' && $type === '' ? __('hr_recruitment.hub.none') : __('hr_recruitment.hub.no_matching')" :body="__('hr_recruitment.list_description')" />
    @else
        <x-ui.data-table :caption="__('hr_recruitment.hub.titles.requests')">
            <x-slot:head>
                <th>{{ __('hr_recruitment.number') }}</th>
                <th>{{ __('hr_recruitment.type') }}</th>
                <th>{{ __('hr_recruitment.status') }}</th>
                <th>{{ __('hr_recruitment.priority') }}</th>
                <th>{{ __('hr_recruitment.requester') }}</th>
                <th>{{ __('hr_recruitment.city') }}</th>
                <th>{{ __('hr_recruitment.position') }}</th>
                <th>{{ __('hr_recruitment.created_at') }}</th>
                @if ($mayCampaigns)
                    <th>{{ __('hr_recruitment.campaign_ui.campaign') }}</th>
                @endif
                <th class="w-16!"><span class="sr-only">{{ __('hr_recruitment.hub.actions') }}</span></th>
            </x-slot:head>

            @foreach ($rows as $request)
                @php
                    $priority = $request->latestVersion?->positionDetail?->priority ?? data_get($request->draft_payload_json, 'priority');
                @endphp
                <tr wire:key="request-{{ $request->id }}">
                    <x-ui.data-table.cell :label="__('hr_recruitment.number')">
                        <span class="font-semibold tabular-nums"><bdi>{{ $request->request_number }}</bdi></span>
                        @if (isset($pending[$request->id]))
                            <span class="ms-1 inline-flex rounded-md border border-primary px-1.5 text-xs font-semibold text-primary dark:text-brand-text">{{ __('hr_recruitment.needs_my_review') }}</span>
                        @endif
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.type')">
                        {{ Labels::enum('RecruitmentRequestType', $request->request_type) }}
                        @if ((int) $request->mode !== 0)
                            <span class="block text-xs {{ $muted }}">{{ Labels::enum('RecruitmentRequestMode', $request->mode) }}</span>
                        @endif
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.status')">
                        <x-ui.enum-badge :case="RecruitmentRequestStatus::from((int) $request->status)" :label="Labels::enum('RecruitmentRequestStatus', $request->status)" :tone="Labels::requestTone((int) $request->status)" />
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.priority')">{{ $priority ? __('hr_recruitment.priority_'.$priority) : '—' }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.requester')"><span dir="auto">{{ $request->requester?->name ?? '—' }}</span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.city')">{{ $request->scopeCity?->name ?? '—' }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.position')"><span dir="auto">{{ $request->targetPosition?->title ?? '—' }}</span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.created_at')"><span class="whitespace-nowrap tabular-nums">{{ $request->created_at?->translatedFormat(__('formats.date')) }}</span></x-ui.data-table.cell>
                    @if ($mayCampaigns)
                        <x-ui.data-table.cell :label="__('hr_recruitment.campaign_ui.campaign')">
                            <span class="flex flex-wrap gap-1.5">
                                @foreach ($this->campaignLinks($request) as $link)
                                    <x-ui.button size="sm" :tone="$link['primary'] ? 'primary' : 'secondary'" :href="$link['url']" wire:navigate>{{ $link['label'] }}</x-ui.button>
                                @endforeach
                            </span>
                        </x-ui.data-table.cell>
                    @endif
                    <td>
                        <span class="flex items-center justify-end">
                            <x-ui.button tone="ghost" size="sm" icon="eye" class="size-8!" :href="route('hr.recruitment.requests.show', ['requestId' => $request->id])" wire:navigate :aria-label="__('hr_recruitment.hub.view').' '.$request->request_number" />
                        </span>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-4">
            <flux:pagination :paginator="$rows" scroll-to="#recruitment-requests-heading" />
        </div>
    @endif
</x-desk.register>
