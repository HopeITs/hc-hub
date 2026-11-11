<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Actions\Recruitment\HiringCampaignStateMachine;
use Modules\Hr\Enums\HiringCampaignStatus;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Support\Recruitment\Labels;

new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.campaigns')] class extends Component {
    use AuthorizesRecruitment;
    use WithPagination;

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $mode = '';

    #[Url(except: '')]
    public string $search = '';

    public function mount(): void
    {
        $this->requirePermission('recruitment-campaigns.view');
        Gate::authorize('viewAny', HiringCampaign::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['status', 'mode', 'search'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        return HiringCampaign::query()->visibleTo($this->viewer())
            ->select(['hiring_campaigns.id', 'hiring_campaigns.vacancy_id', 'hiring_campaigns.link_mode', 'hiring_campaigns.status', 'hiring_campaigns.starts_at', 'hiring_campaigns.ends_at', 'hiring_campaigns.created_by_user_id', 'hiring_campaigns.created_at'])
            ->with([
                'vacancy:id,position_id,created_from_request_id',
                'vacancy.position:id,title_en,title_ar',
                'vacancy.createdFromRequest:id,request_number',
                'creator:id,email,employee_id',
            ])
            ->withCount('applications')
            ->when(ctype_digit($this->status), fn (Builder $query) => $this->whereEffectiveStatus($query, (int) $this->status))
            ->when(in_array($this->mode, HiringCampaign::LINK_MODES, true), fn (Builder $query) => $query->where('hiring_campaigns.link_mode', $this->mode))
            ->when(trim($this->search) !== '', fn (Builder $query) => $query->whereHas('vacancy', fn (Builder $vacancy) => $vacancy
                ->whereHas('position', fn (Builder $position) => $position->whereLike('title_en', '%'.trim($this->search).'%')->orWhereLike('title_ar', '%'.trim($this->search).'%'))
                ->orWhereHas('createdFromRequest', fn (Builder $request) => $request->whereLike('request_number', '%'.trim($this->search).'%'))))
            ->orderByDesc('hiring_campaigns.created_at')
            ->orderByDesc('hiring_campaigns.id')
            ->paginate(25);
    }

    /** @return array<int, int> effective status => count, read by the clock like the rows */
    #[Computed]
    public function counts(): array
    {
        $states = app(HiringCampaignStateMachine::class);

        return HiringCampaign::query()->visibleTo($this->viewer())
            ->get(['hiring_campaigns.id', 'hiring_campaigns.status', 'hiring_campaigns.ends_at'])
            ->countBy(fn (HiringCampaign $campaign): int => $states->effective($campaign))
            ->all();
    }

    /** A window that shut reads Expired, here as in the state machine. */
    private function whereEffectiveStatus(Builder $query, int $status): void
    {
        $live = [HiringCampaignStatus::Published->value, HiringCampaignStatus::Open->value];

        if ($status === HiringCampaignStatus::Expired->value) {
            $query->where(fn (Builder $expired) => $expired
                ->where(fn (Builder $byClock) => $byClock->whereIn('hiring_campaigns.status', $live)->whereNotNull('hiring_campaigns.ends_at')->where('hiring_campaigns.ends_at', '<=', now()))
                ->orWhere('hiring_campaigns.status', HiringCampaignStatus::Expired->value));

            return;
        }

        $query->where('hiring_campaigns.status', $status);

        if (in_array($status, $live, true)) {
            $query->where(fn (Builder $window) => $window->whereNull('hiring_campaigns.ends_at')->orWhere('hiring_campaigns.ends_at', '>', now()));
        }
    }
};
?>

@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $rows = $this->rows;
    $counts = $this->counts;
    $states = app(HiringCampaignStateMachine::class);
@endphp

<x-desk.register class="@container" heading-id="recruitment-campaigns-heading" :title="__('hr_recruitment.hub.titles.campaigns')" :count="$rows->total()">
    @if ($counts !== [])
        <x-slot:figures>
            @foreach ([HiringCampaignStatus::Open, HiringCampaignStatus::Published, HiringCampaignStatus::Draft] as $case)
                {{ Labels::enum('HiringCampaignStatus', $case->value) }}: <span class="font-semibold tabular-nums">{{ $counts[$case->value] ?? 0 }}</span>@if (! $loop->last) · @endif
            @endforeach
        </x-slot:figures>
    @endif

    <x-slot:search>
        <x-desk.register-search :label="__('hr_recruitment.hub.search')" :placeholder="__('hr_recruitment.campaign_ui.position')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        <x-ui.header-filter-select :label="__('hr_recruitment.campaign_ui.status')" wire:model.live="status" :value="$status">
            <option value="">{{ __('hr_recruitment.hub.all_statuses') }}</option>
            @foreach (HiringCampaignStatus::cases() as $case)
                <option value="{{ $case->value }}">{{ Labels::enum('HiringCampaignStatus', $case->value) }}@if (isset($counts[$case->value])) ({{ $counts[$case->value] }})@endif</option>
            @endforeach
        </x-ui.header-filter-select>
        <x-ui.header-filter-select :label="__('hr_recruitment.campaign_ui.link_mode')" wire:model.live="mode" :value="$mode">
            <option value="">{{ __('hr_recruitment.hub.all') }}</option>
            @foreach (HiringCampaign::LINK_MODES as $linkMode)
                <option value="{{ $linkMode }}">{{ __('hr_recruitment.campaign_ui.modes.'.$linkMode) }}</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    @if ($rows->isEmpty())
        <x-ui.empty-state icon="megaphone" :heading="$search === '' && $status === '' && $mode === '' ? __('hr_recruitment.hub.none') : __('hr_recruitment.hub.no_matching')" />
    @else
        <x-ui.data-table :caption="__('hr_recruitment.hub.titles.campaigns')">
            <x-slot:head>
                <th>{{ __('hr_recruitment.campaign_ui.position') }}</th>
                <th>{{ __('hr_recruitment.campaign_ui.request') }}</th>
                <th>{{ __('hr_recruitment.campaign_ui.link_mode') }}</th>
                <th>{{ __('hr_recruitment.campaign_ui.status') }}</th>
                <th>{{ __('hr_recruitment.applicant_ui.applicants') }}</th>
                <th>{{ __('hr_recruitment.campaign_ui.ends_at') }}</th>
                <th>{{ __('hr_recruitment.campaign_ui.created_by') }}</th>
                <th class="w-16!"><span class="sr-only">{{ __('hr_recruitment.hub.actions') }}</span></th>
            </x-slot:head>

            @foreach ($rows as $campaign)
                @php
                    $effective = $states->effective($campaign);
                    $request = $campaign->vacancy?->createdFromRequest;
                @endphp
                <tr wire:key="campaign-{{ $campaign->id }}">
                    <x-ui.data-table.cell :label="__('hr_recruitment.campaign_ui.position')"><span class="font-semibold" dir="auto">{{ $campaign->vacancy?->position?->title ?? '—' }}</span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.campaign_ui.request')">
                        @if ($request)
                            <a href="{{ route('hr.recruitment.requests.show', ['requestId' => $request->id]) }}" wire:navigate class="tabular-nums underline-offset-4 hover:underline"><bdi>{{ $request->request_number }}</bdi></a>
                        @else
                            —
                        @endif
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.campaign_ui.link_mode')">{{ __('hr_recruitment.campaign_ui.modes.'.$campaign->link_mode) }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.campaign_ui.status')">
                        <x-ui.enum-badge :case="HiringCampaignStatus::from($effective)" :label="Labels::enum('HiringCampaignStatus', $effective)" :tone="Labels::campaignTone($effective)" />
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.applicant_ui.applicants')"><span class="tabular-nums">{{ $campaign->applications_count }}</span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.campaign_ui.ends_at')">
                        <span class="whitespace-nowrap tabular-nums">
                            @if ($campaign->starts_at || $campaign->ends_at)
                                {{ $campaign->starts_at?->translatedFormat(__('formats.date')) ?? '…' }} → {{ $campaign->ends_at?->translatedFormat(__('formats.date')) ?? '…' }}
                            @else
                                <span class="{{ $muted }}">{{ __('hr_recruitment.campaign_ui.no_end_date') }}</span>
                            @endif
                        </span>
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.campaign_ui.created_by')"><span dir="auto">{{ $campaign->creator?->name ?? '—' }}</span></x-ui.data-table.cell>
                    <td>
                        <span class="flex items-center justify-end">
                            <x-ui.button tone="ghost" size="sm" icon="eye" class="size-8!" :href="route('hr.recruitment.campaigns.show', ['campaignId' => $campaign->id])" wire:navigate :aria-label="__('hr_recruitment.hub.view')" />
                        </span>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-4">
            <flux:pagination :paginator="$rows" scroll-to="#recruitment-campaigns-heading" />
        </div>
    @endif
</x-desk.register>
