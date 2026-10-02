<?php

namespace App\Livewire\Recruitment\Applicants;

use App\Models\RecruitmentApplicant;
use App\Models\RecruitmentApplication;
use App\Services\RecruitmentApplicationFilters;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    public ?string $activeStatus = null;

    public int|string|null $activeCampaignId = null;

    /** 'preferred' | 'alternative' | 'unvoted' | null (null = no group filter) */
    public ?string $activeEndorsementGroup = null;

    public function filterByStatus(?string $status = null): void
    {
        $this->activeStatus = $status;
        $this->activeEndorsementGroup = null;
    }

    public function filterByEndorsementGroup(?string $group = null): void
    {
        $this->activeEndorsementGroup = $group;
        $this->activeStatus = null;
    }

    public function render(RecruitmentApplicationFilters $filters)
    {
        Gate::authorize('viewAny', RecruitmentApplicant::class);

        $user = auth()->user();

        $countQuery = RecruitmentApplication::query()->visibleTo($user);
        $filters->campaign($countQuery, $this->activeCampaignId);

        $counts = $countQuery
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Endorsement group counts — only when a campaign is selected.
        $endorsementCounts = null;
        if ($this->activeCampaignId) {
            $base = RecruitmentApplication::query()
                ->visibleTo($user)
                ->where('campaign_id', $this->activeCampaignId)
                ->withCount('endorsements');

            $rows = $base->get(['id', 'endorsements_count']);

            $endorsementCounts = [
                'preferred'   => $rows->where('endorsements_count', '>=', 2)->count(),
                'alternative' => $rows->where('endorsements_count', 1)->count(),
                'unvoted'     => $rows->where('endorsements_count', 0)->count(),
            ];
        }

        return view('livewire.recruitment.applicants.index', [
            'counts' => $counts,
            'campaignOptions' => $filters->campaignOptions($user),
            'endorsementCounts' => $endorsementCounts,
        ]);
    }
}
