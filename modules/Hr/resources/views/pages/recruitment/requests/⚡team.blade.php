<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Hr\Actions\Recruitment\RecruitmentPositionActionService;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Enums\HiringCampaignStatus;
use Modules\Hr\Enums\RecruitmentRequestType;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\RecruitmentRequest;
use Modules\Hr\Models\Vacancy;

/*
 * The manager's reporting line read as staffing: who holds each post, where
 * the establishment is short, and the request each post can take. Every
 * action is checked again when the request is saved. (The old screen's
 * zoomable chart is shown here as an outline.)
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.team')] class extends Component {
    use AuthorizesRecruitment;

    /** @var array<int, bool> position id => collapsed */
    public array $collapsed = [];

    #[Url(as: 'vacant', except: false)]
    public bool $vacantOnly = false;

    public function mount(): void
    {
        $this->requirePermission('recruitment-requests.view', 'recruitment-requests.manage');
        Gate::authorize('create', RecruitmentRequest::class);

        // Open three levels deep; anything below starts folded.
        $root = app(RecruitmentScopeResolver::class)->position($this->viewer());

        if ($root === null) {
            return;
        }

        $seen = [(int) $root->getKey() => true];
        $frontier = [(int) $root->getKey()];

        for ($depth = 0; $frontier !== []; $depth++) {
            $next = [];

            foreach (Position::query()->where('is_active', true)->whereIn('direct_manager_id', $frontier)->get(['id', 'direct_manager_id']) as $child) {
                $id = (int) $child->getKey();

                if (isset($seen[$id])) {
                    continue;
                }

                $seen[$id] = true;
                $next[] = $id;

                if ($depth >= 2) {
                    $this->collapsed[(int) $child->direct_manager_id] = true;
                }
            }

            $frontier = $next;
        }
    }

    public function toggle(int $positionId): void
    {
        $this->collapsed[$positionId] = ! ($this->collapsed[$positionId] ?? false);
    }

    public function expandAll(): void
    {
        $this->collapsed = [];
    }

    public function collapseAll(): void
    {
        foreach (app(RecruitmentScopeResolver::class)->manageablePositionIds($this->viewer(), true) as $positionId) {
            $this->collapsed[(int) $positionId] = true;
        }
    }

    public function toggleVacantOnly(): void
    {
        $this->vacantOnly = ! $this->vacantOnly;
        $this->collapsed = [];
    }

    /** @return array{root: ?array, summary: array<string, int>, hidden: int} */
    #[Computed]
    public function team(): array
    {
        $user = $this->viewer();
        $scopes = app(RecruitmentScopeResolver::class);
        $managerPosition = $scopes->position($user);
        $empty = ['root' => null, 'summary' => ['positions' => 0, 'filled' => 0, 'capacity' => 0, 'vacancies' => 0], 'hidden' => 0];

        $root = $managerPosition === null ? null : Position::query()
            ->whereKey($managerPosition->getKey())
            ->with([...Position::cardRelations('direct'), 'directTeamRecursive'])
            ->first();

        if ($root === null) {
            return $empty;
        }

        $positions = collect();
        $flatten = function (Position $position) use (&$flatten, &$positions): void {
            if (! $position->is_active) {
                return;
            }

            $positions->put($position->getKey(), $position);

            foreach ($position->getRelation('directTeamRecursive')->where('is_active', true) as $child) {
                $flatten($child);
            }
        };
        $flatten($root);
        $positionIds = $positions->keys()->map(fn ($id): int => (int) $id)->values();

        $activeRequests = RecruitmentRequest::query()
            ->whereIn('target_position_id', $positionIds)
            ->where(fn (Builder $query) => $query
                ->whereIn('status', RecruitmentPositionActionService::ACTIVE_REQUEST_STATUSES)
                ->orWhere(fn (Builder $fill) => $fill->blockingFill()))
            ->with('vacancy')
            ->get()
            ->groupBy('target_position_id');

        $visibleRequests = RecruitmentRequest::query()
            ->visibleTo($user)
            ->whereIn('target_position_id', $positionIds)
            ->whereIn('status', RecruitmentPositionActionService::DISPLAY_REQUEST_STATUSES)
            ->get(['id', 'request_number', 'status', 'target_position_id'])
            ->groupBy('target_position_id');

        $openVacancies = Vacancy::query()->whereIn('position_id', $positionIds)->available()->get()->groupBy('position_id');
        $vacancyIds = $openVacancies->flatten()->pluck('id');
        $campaignVacancyIds = $vacancyIds->isEmpty() ? collect() : HiringCampaign::query()
            ->whereIn('vacancy_id', $vacancyIds)
            ->whereIn('status', [HiringCampaignStatus::Published->value, HiringCampaignStatus::Open->value])
            ->pluck('vacancy_id')
            ->map(fn ($id): int => (int) $id);

        $proposedManagerIds = $scopes->scopeProposedManagers(Position::query(), $user)
            ->whereIn('id', $positionIds)->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $actions = app(RecruitmentPositionActionService::class);

        $build = function (Position $position, int $depth) use (&$build, $activeRequests, $visibleRequests, $openVacancies, $campaignVacancyIds, $proposedManagerIds, $actions, $root): array {
            $positionId = (int) $position->getKey();
            $isRoot = $positionId === (int) $root->getKey();
            $children = $position->getRelation('directTeamRecursive')
                ->filter(fn (Position $child): bool => (bool) $child->is_active)
                ->values()
                ->map(fn (Position $child): array => $build($child, $depth + 1))
                ->all();
            $vacancies = $openVacancies->get($positionId, collect());
            $onlyVacancyId = $vacancies->count() === 1 ? (int) $vacancies->first()->getKey() : null;
            $items = [];

            foreach ($actions->actionsFor($position, $activeRequests->get($positionId, collect()), $vacancies, $campaignVacancyIds, $isRoot, in_array($positionId, $proposedManagerIds, true)) as $type => $item) {
                $isCreate = $type === 'create_position';
                $parameters = $isCreate
                    ? ['type' => RecruitmentRequestType::CreatePosition->value, 'direct_manager_id' => $positionId]
                    : ['type' => $type, 'position_id' => $positionId];

                if (in_array((int) $type, [RecruitmentRequestType::FillVacancy->value, RecruitmentRequestType::CloseVacancy->value], true) && $onlyVacancyId !== null) {
                    $parameters['vacancy_id'] = $onlyVacancyId;
                }

                $items[$type] = [
                    ...$item,
                    'label' => $isCreate ? __('hr_recruitment.team_staffing.create_position') : \Modules\Hr\Support\Recruitment\Labels::enum('RecruitmentRequestType', (int) $type),
                    'url' => route('hr.recruitment.requests.create', $parameters),
                ];
            }

            return [
                'position' => $position,
                'id' => $positionId,
                'depth' => $depth,
                'isRoot' => $isRoot,
                'children' => $children,
                'requests' => $visibleRequests->get($positionId, collect()),
                'actions' => $items,
                'openSeats' => max(0, (int) $position->max_emp - (int) $position->exist_emp),
                'subtreeOpenSeats' => 0,
                'passthrough' => false,
            ];
        };

        $tree = $this->summarise($build($root, 0));
        $visible = $this->vacantOnly ? $this->prune($tree) : $tree;

        return [
            'root' => $visible,
            'summary' => [
                'positions' => $positions->count(),
                'filled' => (int) $positions->sum('exist_emp'),
                'capacity' => (int) $positions->sum('max_emp'),
                'vacancies' => (int) $positions->sum(fn (Position $position): int => max(0, (int) $position->max_emp - (int) $position->exist_emp)),
            ],
            'hidden' => $positions->count() - $this->countNodes($visible),
        ];
    }

    private function summarise(array $node): array
    {
        $node['children'] = array_map(fn (array $child): array => $this->summarise($child), $node['children']);
        $node['subtreeOpenSeats'] = $node['openSeats'] + array_sum(array_column($node['children'], 'subtreeOpenSeats'));

        return $node;
    }

    private function prune(array $node): ?array
    {
        if ($node['subtreeOpenSeats'] < 1 && ! $node['isRoot']) {
            return null;
        }

        $node['children'] = array_values(array_filter(array_map(fn (array $child): ?array => $this->prune($child), $node['children'])));
        $node['passthrough'] = $node['openSeats'] < 1 && ! $node['isRoot'];

        return $node;
    }

    private function countNodes(?array $node): int
    {
        return $node === null ? 0 : 1 + array_sum(array_map(fn (array $child): int => $this->countNodes($child), $node['children']));
    }
};
?>

@php
    $team = $this->team;
    $summary = $team['summary'];
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<div class="space-y-6">
    <x-ui.detail-header
        :back="\App\Support\BackUrl::for(route('hr.recruitment.requests.index'))"
        :breadcrumbs="[['label' => __('hr_recruitment.hub.section')], ['label' => __('hr_recruitment.hub.titles.requests'), 'href' => route('hr.recruitment.requests.index')]]"
        :title="__('hr_recruitment.team_staffing.title')"
        :subtitle="__('hr_recruitment.team_staffing.description')"
    />

    @if ($team['root'] === null)
        <x-ui.panel>
            <x-ui.empty-state icon="user-group" :heading="__('hr_recruitment.team_staffing.no_position')" :body="__('hr_recruitment.team_staffing.no_position_help')" />
        </x-ui.panel>
    @else
        <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('hr_recruitment.team_staffing.summary') }}">
            <x-ui.stat-tile :label="__('hr_recruitment.team_staffing.positions')" :value="$summary['positions']" />
            <x-ui.stat-tile :label="__('hr_recruitment.team_staffing.approved_headcount')" :value="$summary['capacity']" />
            <x-ui.stat-tile :label="__('hr_recruitment.team_staffing.filled_headcount')" :value="$summary['filled']" />
            <x-ui.stat-tile :label="__('hr_recruitment.team_staffing.open_capacity')" :value="$summary['vacancies']" :tone="$summary['vacancies'] > 0 ? 'warn' : null" />
        </dl>

        <x-ui.panel :title="__('hr_recruitment.team_staffing.tree_heading')" :subtitle="__('hr_recruitment.team_staffing.tree_help')" :clip="false">
            <x-slot:toolbar>
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.button size="sm" :tone="$vacantOnly ? 'primary' : 'secondary'" wire:click="toggleVacantOnly" aria-pressed="{{ $vacantOnly ? 'true' : 'false' }}">
                        {{ $vacantOnly ? __('hr_recruitment.team_staffing.show_all') : __('hr_recruitment.team_staffing.vacant_only') }}
                    </x-ui.button>
                    <x-ui.button size="sm" tone="ghost" icon="arrows-pointing-out" wire:click="expandAll">{{ __('hr_recruitment.team_staffing.show_team') }}</x-ui.button>
                    <x-ui.button size="sm" tone="ghost" icon="arrows-pointing-in" wire:click="collapseAll">{{ __('hr_recruitment.team_staffing.hide_team') }}</x-ui.button>
                </div>
            </x-slot:toolbar>
            <div class="p-4">
                @if ($vacantOnly && $team['hidden'] > 0)
                    <p class="mb-3 text-sm {{ $muted }}">{{ __('hr_recruitment.team_staffing.hidden_positions', ['count' => $team['hidden']]) }}</p>
                @endif
                <ul class="space-y-2" role="tree" aria-label="{{ __('hr_recruitment.team_staffing.tree_heading') }}">
                    <x-hr::recruitment.staffing-node :node="$team['root']" :collapsed="$collapsed" />
                </ul>
            </div>
        </x-ui.panel>
    @endif
</div>
