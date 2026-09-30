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

    public function filterByStatus(?string $status = null): void
    {
        $this->activeStatus = $status;
    }

    public function render(RecruitmentApplicationFilters $filters)
    {
        Gate::authorize('viewAny', RecruitmentApplicant::class);

        $countQuery = RecruitmentApplication::query()->visibleTo(auth()->user());
        $filters->campaign($countQuery, $this->activeCampaignId);

        $counts = $countQuery
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.recruitment.applicants.index', [
            'counts' => $counts,
            'campaignOptions' => $filters->campaignOptions(auth()->user()),
        ]);
    }
}
