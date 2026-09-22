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
        session()->flash('recruitment_invite_added', __('recruitment.invite_added'));
    }

    public function cancel(int $inviteId, RecruitmentCampaignInviteService $invites): void
    {
        // Scoped to this campaign so the id in the request can't reach an
        // invite belonging to a campaign outside this screen's own.
        $invite = RecruitmentCampaignInvite::query()
            ->where('campaign_id', $this->campaign->getKey())
            ->findOrFail($inviteId);

        $invites->cancel(auth()->user(), $invite);
    }

    public function render()
    {
        return view('livewire.recruitment.campaigns.invites', [
            'invites' => $this->campaign->invites()->latest()->get(),
        ]);
    }
}
