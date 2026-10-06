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

    /** 'yes' | 'maybe' | 'no' | 'none' | null */
    public ?string $activeEvaluationRecommendation = null;

    public function filterByStatus(?string $status = null): void
    {
        $this->activeStatus = $status;
    }

    public function filterByEndorsementGroup(?string $group = null): void
    {
        $this->activeEndorsementGroup = $group;
    }

    public function filterByEvaluationRecommendation(?string $recommendation = null): void
    {
        $this->activeEvaluationRecommendation = $recommendation;
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
        $evaluationCounts = null;
        if ($this->activeCampaignId) {
            $rows = RecruitmentApplication::query()
                ->visibleTo($user)
                ->where('campaign_id', $this->activeCampaignId)
                ->with('endorsements:id,application_id,voter_role')
                ->get(['id']);

            $endorsementCounts = [
                'preferred' => $rows->filter(fn (RecruitmentApplication $application): bool => $application->endorsements->pluck('voter_role')->unique()->count() >= 2)->count(),
                'alternative' => $rows->filter(fn (RecruitmentApplication $application): bool => $application->endorsements->pluck('voter_role')->unique()->count() === 1)->count(),
                'unvoted' => $rows->filter(fn (RecruitmentApplication $application): bool => $application->endorsements->pluck('voter_role')->unique()->count() === 0)->count(),
            ];

            if ($user->hasActivePermission('recruitment-applicants.view-evaluations')) {
                $evaluationCounts = collect($filters->evaluationRecommendationOptions())
                    ->mapWithKeys(function (string $label, string $recommendation) use ($filters, $user): array {
                        $query = RecruitmentApplication::query()->visibleTo($user);
                        $filters->campaign($query, $this->activeCampaignId);
                        $filters->evaluationRecommendation($query, $recommendation);

                        return [$recommendation => $query->count()];
                    });
            }
        }

        return view('livewire.recruitment.applicants.index', [
            'counts' => $counts,
            'campaignOptions' => $filters->campaignOptions($user),
            'endorsementCounts' => $endorsementCounts,
            'evaluationCounts' => $evaluationCounts,
            'mayViewEvaluationDashboard' => $user->hasActivePermission('recruitment-applicants.view-evaluations'),
        ]);
    }
}
