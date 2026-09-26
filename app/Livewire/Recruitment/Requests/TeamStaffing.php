<?php

namespace App\Livewire\Recruitment\Requests;

use App\Enums\HiringCampaignStatus;
use App\Models\HiringCampaign;
use App\Models\Position;
use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\RecruitmentPositionActionService;
use App\Services\RecruitmentScopeResolver;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class TeamStaffing extends Component
{
    /** @var array<int, bool> */
    public array $collapsed = [];

    public function boot(): void
    {
        Gate::authorize('create', RecruitmentRequest::class);
    }

    public function toggle(int $positionId): void
    {
        $this->collapsed[$positionId] = ! ($this->collapsed[$positionId] ?? false);
    }

    public function expandAll(): void
    {
        foreach ($this->positionIds() as $positionId) {
            $this->collapsed[$positionId] = false;
        }
    }

    public function collapseAll(): void
    {
        foreach ($this->positionIds() as $positionId) {
            $this->collapsed[$positionId] = true;
        }
    }

    public function render(): View
    {
        Gate::authorize('create', RecruitmentRequest::class);

        $data = $this->teamData(auth()->user());

        return view('livewire.recruitment.requests.team-staffing', $data);
    }

    /**
     * @return array{root: ?array<string, mixed>, summary: array<string, int>, hasTeam: bool}
     */
    private function teamData(User $user): array
    {
        $scopes = app(RecruitmentScopeResolver::class);
        $managerPosition = $scopes->position($user);

        if ($managerPosition === null) {
            return ['root' => null, 'summary' => $this->emptySummary(), 'hasTeam' => false];
        }

        $root = Position::query()
            ->whereKey($managerPosition->getKey())
            ->with([...Position::cardRelations('direct'), 'directTeamRecursive'])
            ->first();

        if ($root === null) {
            return ['root' => null, 'summary' => $this->emptySummary(), 'hasTeam' => false];
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
            ->whereIn('status', RecruitmentPositionActionService::ACTIVE_REQUEST_STATUSES)
            ->get()
            ->groupBy('target_position_id');
        $visibleRequests = RecruitmentRequest::query()
            ->visibleTo($user)
            ->whereIn('target_position_id', $positionIds)
            ->whereIn('status', RecruitmentPositionActionService::DISPLAY_REQUEST_STATUSES)
            ->get()
            ->groupBy('target_position_id');
        $openVacancies = Vacancy::query()
            ->whereIn('position_id', $positionIds)
            ->available()
            ->get()
            ->groupBy('position_id');
        $vacancyIds = $openVacancies->flatten()->pluck('id');
        $activeCampaignVacancyIds = $vacancyIds->isEmpty()
            ? collect()
            : HiringCampaign::query()
                ->whereIn('vacancy_id', $vacancyIds)
                ->whereIn('status', [HiringCampaignStatus::Published, HiringCampaignStatus::Open])
                ->pluck('vacancy_id')
                ->map(fn ($id): int => (int) $id);
        $proposedManagerIds = $scopes->scopeProposedManagers(Position::query(), $user)
            ->whereIn('id', $positionIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $actions = app(RecruitmentPositionActionService::class);

        $build = function (Position $position, int $depth) use (&$build, $activeRequests, $visibleRequests, $openVacancies, $activeCampaignVacancyIds, $proposedManagerIds, $actions, $root): array {
            $positionId = (int) $position->getKey();
            $children = $position->getRelation('directTeamRecursive')
                ->filter(fn (Position $child): bool => $child->is_active)
                ->values();
            $availability = $actions->actionsFor(
                $position,
                $activeRequests->get($positionId, collect()),
                $openVacancies->get($positionId, collect()),
                $activeCampaignVacancyIds,
                $positionId === (int) $root->getKey(),
                in_array($positionId, $proposedManagerIds, true),
            );

            $actionItems = [];
            foreach ($availability as $type => $item) {
                $actionItems[$type] = [
                    ...$item,
                    'label' => $type === 'create_position'
                        ? __('recruitment.team_staffing.create_position')
                        : \App\Enums\RecruitmentRequestType::getLocalizedName((int) $type)['value'],
                    'url' => $type === 'create_position'
                        ? route('recruitment.requests.create', ['type' => \App\Enums\RecruitmentRequestType::CreatePosition, 'direct_manager_id' => $positionId])
                        : route('recruitment.requests.create', ['type' => $type, 'position_id' => $positionId]),
                ];
            }

            return [
                'position' => $position,
                'depth' => $depth,
                'children' => $children->map(fn (Position $child): array => $build($child, $depth + 1))->all(),
                'requests' => $visibleRequests->get($positionId, collect()),
                'openVacancies' => $openVacancies->get($positionId, collect()),
                'actions' => $actionItems,
            ];
        };

        $tree = $build($root, 0);
        $summary = [
            'positions' => $positions->count(),
            'filled' => (int) $positions->sum('exist_emp'),
            'capacity' => (int) $positions->sum('max_emp'),
            'vacancies' => (int) $positions->sum(fn (Position $position): int => max(0, (int) $position->max_emp - (int) $position->exist_emp)),
        ];

        return ['root' => $tree, 'summary' => $summary, 'hasTeam' => $positions->count() > 1];
    }

    /** @return list<int> */
    private function positionIds(): array
    {
        $root = app(RecruitmentScopeResolver::class)->position(auth()->user());

        if ($root === null) {
            return [];
        }

        return app(RecruitmentScopeResolver::class)
            ->manageablePositionIds(auth()->user(), true);
    }

    /** @return array{positions: int, filled: int, capacity: int, vacancies: int} */
    private function emptySummary(): array
    {
        return ['positions' => 0, 'filled' => 0, 'capacity' => 0, 'vacancies' => 0];
    }
}
