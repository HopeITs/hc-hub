<?php

namespace App\Livewire\Recruitment\Campaigns;

use App\Models\HiringCampaign;
use App\Models\RecruitmentCampaignInvite;
use App\Services\RecruitmentCampaignInviteService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * HR's screen for an invitation-only campaign's invite list — add, view,
 * cancel. Closes Phase 2 Task 1's one remaining acceptance criterion
 * ("يمكن لـ HR إضافة قائمة مدعوين للحملة").
 *
 * Listing/filtering/search/cancel live in the nested InviteTable
 * (ramonrietdijk/livewire-tables); this component only owns the add-invite
 * form and the campaign it applies to.
 */
class Invites extends Component
{
    public HiringCampaign $campaign;

    public string $full_name = '';

    public string $email = '';

    public function mount(int $campaignId): void
    {
        $this->campaign = HiringCampaign::query()->visibleTo(auth()->user())->findOrFail($campaignId);

        Gate::authorize('manageForCampaign', [RecruitmentCampaignInvite::class, $this->campaign]);
    }

    public function add(RecruitmentCampaignInviteService $invites): void
    {
        $this->validate([
            'full_name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email:rfc', 'max:191'],
        ]);

        $invites->add(auth()->user(), $this->campaign, $this->full_name, $this->email);

        $this->reset(['full_name', 'email']);
        session()->flash('recruitment_invite_added', __('recruitment-applicants.invite_added'));

        // The list lives in a separate nested component (InviteTable); this
        // is the same refresh signal the package's own actions use to update
        // it after a change made outside the table itself.
        $this->dispatch('refreshLivewireTable');
    }

    public function render()
    {
        return view('livewire.recruitment.campaigns.invites');
    }
}
