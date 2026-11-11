<?php

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Core\Models\City;
use Modules\Hr\Actions\Recruitment\RecruitmentApproverEligibility;
use Modules\Hr\Actions\Recruitment\RecruitmentPendingApprovalQuery;
use Modules\Hr\Enums\RecruitmentRequestType;
use Modules\Hr\Enums\WorkflowStepKey;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Support\Recruitment\Labels;

new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.approvals')] class extends Component {
    use AuthorizesRecruitment;
    use WithPagination;

    /** Waiting this many days or more is overdue. */
    public const LONG_WAIT_DAYS = 3;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $step = '';

    #[Url(except: '')]
    public string $city = '';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    public function mount(): void
    {
        $this->requirePermission('recruitment-requests.approve');

        // Closed to anyone no approval can reach, not only hidden from the menu.
        abort_unless(app(RecruitmentApproverEligibility::class)->mayReceiveApprovals($this->viewer()), 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'type', 'step', 'city', 'direction'], true)) {
            $this->resetPage();
        }
    }

    public function toggleDirection(): void
    {
        $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
    }

    private function queue(): Builder
    {
        return app(RecruitmentPendingApprovalQuery::class)->forUser($this->viewer());
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $pending = app(RecruitmentPendingApprovalQuery::class);

        return $this->queue()
            ->reorder()
            ->setEagerLoads([])
            ->addSelect(['waiting_since' => $pending->waitingSince()])
            ->with([
                'workflowStep:id,step_key',
                'workflowInstance:id,request_id',
                'workflowInstance.request:id,request_number,request_type,requester_user_id,scope_city_id,target_position_id',
                'workflowInstance.request.requester:id,email,employee_id',
                'workflowInstance.request.scopeCity:id,name_en,name_ar',
                'workflowInstance.request.targetPosition:id,title_en,title_ar,center_id',
                'workflowInstance.request.targetPosition.center:id,name_en,name_ar',
            ])
            ->when(trim($this->search) !== '', fn ($query) => $query->whereLike('pending_requests.request_number', '%'.trim($this->search).'%'))
            ->when(ctype_digit($this->type), fn ($query) => $query->where('pending_requests.request_type', (int) $this->type))
            ->when(ctype_digit($this->step), fn ($query) => $query->where('pending_definitions.step_key', (int) $this->step))
            ->when(ctype_digit($this->city), fn ($query) => $query->where('pending_requests.scope_city_id', (int) $this->city))
            ->orderBy('waiting_since', $this->direction === 'desc' ? 'desc' : 'asc')
            ->orderBy('recruitment_workflow_instance_steps.id')
            ->paginate(25);
    }

    /** @return array{waiting: int, longest: ?int, overdue: int, today: int} the inbox at a glance */
    #[Computed]
    public function summary(): array
    {
        $now = CarbonImmutable::now();
        $arrivals = $this->queue()->setEagerLoads([])->reorder()->select([])
            ->addSelect(['waiting_since' => app(RecruitmentPendingApprovalQuery::class)->waitingSince()])
            ->pluck('waiting_since')->filter()
            ->map(fn ($since): CarbonImmutable => CarbonImmutable::parse($since));
        $days = $arrivals->map(fn (CarbonImmutable $since): int => (int) $since->diffInDays($now));

        return [
            'waiting' => $this->queue()->setEagerLoads([])->reorder()->count(),
            'longest' => $days->max(),
            'overdue' => $days->filter(fn (int $day): bool => $day >= self::LONG_WAIT_DAYS)->count(),
            'today' => $arrivals->filter(fn (CarbonImmutable $since): bool => $since->isSameDay($now))->count(),
        ];
    }

    /** Only the governorates something is actually waiting in. */
    #[Computed]
    public function cities(): array
    {
        return City::query()
            ->whereIn('id', $this->queue()->reorder()->setEagerLoads([])->select('pending_requests.scope_city_id'))
            ->get(['id', 'name_en', 'name_ar'])
            ->mapWithKeys(fn (City $city): array => [$city->id => $city->name])
            ->all();
    }

    public function waitingLabel(?int $days): string
    {
        return match (true) {
            $days === null => '—',
            $days === 0 => __('hr_recruitment.workflow_ui.waiting_today'),
            default => trans_choice('hr_recruitment.workflow_ui.waiting_days', $days, ['count' => $days]),
        };
    }
};
?>

@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $rows = $this->rows;
    $summary = $this->summary;
    $filtered = $search !== '' || $type !== '' || $step !== '' || $city !== '';
@endphp

<x-desk.register class="@container" heading-id="recruitment-approvals-heading" :title="__('hr_recruitment.hub.titles.approvals')" :count="$summary['waiting']">
    @if ($summary['waiting'] > 0)
        <x-slot:figures>
            {{ __('hr_recruitment.workflow_ui.summary_overdue', ['count' => $this::LONG_WAIT_DAYS]) }}: <span @class(['font-semibold tabular-nums', 'text-status-critical' => $summary['overdue'] > 0])>{{ $summary['overdue'] }}</span>
            · {{ __('hr_recruitment.workflow_ui.summary_longest') }}: <span class="font-semibold">{{ $this->waitingLabel($summary['longest']) }}</span>
            · {{ __('hr_recruitment.workflow_ui.summary_today') }}: <span class="font-semibold tabular-nums">{{ $summary['today'] }}</span>
        </x-slot:figures>
    @endif

    <x-slot:search>
        <x-desk.register-search :label="__('hr_recruitment.hub.search_number')" :placeholder="__('hr_recruitment.number')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        <x-ui.header-filter-select :label="__('hr_recruitment.workflow_ui.request_type')" wire:model.live="type" :value="$type">
            <option value="">{{ __('hr_recruitment.hub.all_types') }}</option>
            @foreach (RecruitmentRequestType::cases() as $case)
                @continue($case->value === 0)
                <option value="{{ $case->value }}">{{ Labels::enum('RecruitmentRequestType', $case->value) }}</option>
            @endforeach
        </x-ui.header-filter-select>
        <x-ui.header-filter-select :label="__('hr_recruitment.workflow_ui.your_step')" wire:model.live="step" :value="$step">
            <option value="">{{ __('hr_recruitment.hub.all') }}</option>
            @foreach (WorkflowStepKey::cases() as $case)
                @continue($case->value === 0)
                <option value="{{ $case->value }}">{{ Labels::enum('WorkflowStepKey', $case->value) }}</option>
            @endforeach
        </x-ui.header-filter-select>
        @if (count($this->cities) > 1)
            <x-ui.header-filter-select :label="__('hr_recruitment.workflow_ui.governorate')" wire:model.live="city" :value="$city">
                <option value="">{{ __('hr_recruitment.hub.all_cities') }}</option>
                @foreach ($this->cities as $cityId => $cityName)
                    <option value="{{ $cityId }}">{{ $cityName }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif
    </x-slot:filters>

    @if ($rows->isEmpty())
        @if ($filtered)
            <x-ui.empty-state icon="magnifying-glass" :heading="__('hr_recruitment.hub.no_matching')" :body="__('hr_recruitment.hub.change_filters')" />
        @else
            <x-ui.empty-state icon="check-circle" :heading="__('hr_recruitment.workflow_ui.empty_title')" :body="__('hr_recruitment.workflow_ui.empty_message')" />
        @endif
    @else
        <x-ui.data-table :caption="__('hr_recruitment.hub.titles.approvals')">
            <x-slot:head>
                <th>{{ __('hr_recruitment.workflow_ui.request') }}</th>
                <th>{{ __('hr_recruitment.workflow_ui.request_type') }}</th>
                <th>{{ __('hr_recruitment.workflow_ui.requester') }}</th>
                <th>{{ __('hr_recruitment.workflow_ui.your_step') }}</th>
                <th>
                    <button type="button" wire:click="toggleDirection" class="inline-flex items-center gap-1">
                        {{ __('hr_recruitment.workflow_ui.waiting') }}
                        <flux:icon :name="$direction === 'asc' ? 'chevron-up' : 'chevron-down'" variant="micro" class="size-4" />
                    </button>
                </th>
                <th class="w-16!"><span class="sr-only">{{ __('hr_recruitment.hub.actions') }}</span></th>
            </x-slot:head>

            @foreach ($rows as $pendingStep)
                @php
                    $request = $pendingStep->workflowInstance->request;
                    $position = $request->targetPosition;
                    $since = $pendingStep->waiting_since ? CarbonImmutable::parse($pendingStep->waiting_since) : null;
                    $days = $since ? (int) $since->diffInDays(now()) : null;
                    $long = $days !== null && $days >= $this::LONG_WAIT_DAYS;
                @endphp
                <tr wire:key="pending-{{ $pendingStep->id }}">
                    <x-ui.data-table.cell :label="__('hr_recruitment.workflow_ui.request')">
                        <span class="block font-semibold" dir="auto">{{ $position?->title ?? $request->request_number }}</span>
                        <span class="block text-xs {{ $muted }}"><bdi class="tabular-nums">{{ $request->request_number }}</bdi>@if ($position?->center) · {{ $position->center->name }}@endif</span>
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.workflow_ui.request_type')">{{ Labels::enum('RecruitmentRequestType', $request->request_type) }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.workflow_ui.requester')">
                        <span class="block" dir="auto">{{ $request->requester?->name ?? '—' }}</span>
                        @if ($request->scopeCity)<span class="block text-xs {{ $muted }}">{{ $request->scopeCity->name }}</span>@endif
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.workflow_ui.your_step')">{{ Labels::enum('WorkflowStepKey', (int) $pendingStep->workflowStep?->step_key) }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_recruitment.workflow_ui.waiting')">
                        <span @class(['block whitespace-nowrap', 'font-semibold text-status-critical' => $long])>{{ $this->waitingLabel($days) }}</span>
                        @if ($since)
                            <time datetime="{{ $since->toIso8601String() }}" class="block text-xs tabular-nums {{ $muted }}">{{ __('hr_recruitment.workflow_ui.since', ['date' => $since->translatedFormat(__('formats.datetime'))]) }}</time>
                        @endif
                    </x-ui.data-table.cell>
                    <td>
                        <span class="flex items-center justify-end">
                            <x-ui.button tone="ghost" size="sm" icon="eye" class="size-8!" :href="route('hr.recruitment.requests.show', ['requestId' => $request->id])" wire:navigate :aria-label="__('hr_recruitment.workflow_ui.review_request', ['number' => $request->request_number])" />
                        </span>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-4">
            <flux:pagination :paginator="$rows" scroll-to="#recruitment-approvals-heading" />
        </div>
    @endif
</x-desk.register>
