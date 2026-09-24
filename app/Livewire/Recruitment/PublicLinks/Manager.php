<?php

namespace App\Livewire\Recruitment\PublicLinks;

use App\Enums\RecruitmentCampaignLinkMode;
use App\Models\HiringCampaign;
use App\Models\PublicRecruitmentLink;
use App\Models\RecruitmentCampaignInvite;
use App\Services\PublicRecruitmentLinkService;
use DomainException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Manager extends Component
{
    public HiringCampaign $campaign;

    public string $linkMode = RecruitmentCampaignLinkMode::PublicOpen;

    public string $expiresAt = '';

    public string $generatedUrl = '';

    public string $inviteName = '';

    public string $inviteEmail = '';

    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(HiringCampaign $campaign): void
    {
        Gate::authorize('view', $campaign);
        abort_unless(
            auth()->user()?->hasActivePermission('recruitment-links.view')
                || auth()->user()?->hasActivePermission('recruitment-links.manage'),
            403,
        );

        $this->campaign = $campaign;
        $this->linkMode = in_array($campaign->link_mode, [
            RecruitmentCampaignLinkMode::PublicOpen,
            RecruitmentCampaignLinkMode::InvitationOnly,
        ], true) ? $campaign->link_mode : RecruitmentCampaignLinkMode::PublicOpen;
        $expiry = $campaign->ends_at?->isFuture() ? $campaign->ends_at : now()->addDays(30);
        $this->expiresAt = $expiry->format('Y-m-d\TH:i');
    }

    public function generate(PublicRecruitmentLinkService $service): void
    {
        Gate::authorize('create', [PublicRecruitmentLink::class, $this->campaign]);
        if ((int) $this->campaign->status === \App\Enums\HiringCampaignStatus::Draft) {
            Gate::authorize('publish', $this->campaign);
        }

        $validated = $this->validate([
            'linkMode' => ['required', Rule::in([
                RecruitmentCampaignLinkMode::PublicOpen,
                RecruitmentCampaignLinkMode::InvitationOnly,
            ])],
            'expiresAt' => ['required', 'date', 'after:now'],
        ]);

        try {
            $result = $service->generate(
                $this->campaign,
                auth()->user(),
                $validated['expiresAt'],
                $validated['linkMode'],
            );
            $this->campaign->refresh();
            $this->generatedUrl = $result['url'];
            $this->success(__($result['campaign_published']
                ? 'recruitment.public_link.generated_and_published'
                : 'recruitment.public_link.generated'));
            $this->dispatch('campaign-status-changed');
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function disable(int $linkId, PublicRecruitmentLinkService $service): void
    {
        $link = $this->campaign->publicLinks()->findOrFail($linkId);
        Gate::authorize('update', $link);
        $service->disable($link, auth()->user());
        $this->generatedUrl = '';
        $this->success(__('recruitment.public_link.disabled'));
    }

    public function addInvite(PublicRecruitmentLinkService $service): void
    {
        Gate::authorize('create', [PublicRecruitmentLink::class, $this->campaign]);
        $validated = $this->validate([
            'inviteName' => ['required', 'string', 'max:191'],
            'inviteEmail' => ['required', 'email:rfc', 'max:191'],
        ]);

        try {
            $service->addInvite(
                $this->campaign->fresh(),
                auth()->user(),
                $validated['inviteName'],
                $validated['inviteEmail'],
            );
            $this->reset('inviteName', 'inviteEmail');
            $this->success(__('recruitment.public_link.invite_saved'));
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function cancelInvite(int $inviteId, PublicRecruitmentLinkService $service): void
    {
        Gate::authorize('create', [PublicRecruitmentLink::class, $this->campaign]);
        $invite = $this->campaign->invites()->findOrFail($inviteId);
        $service->cancelInvite($invite, auth()->user());
        $this->success(__('recruitment.public_link.invite_cancelled'));
    }

    public function updatedLinkMode(): void
    {
        $this->notice = null;
    }

    public function render()
    {
        return view('livewire.recruitment.public-links.manager', [
            'activeLink' => $this->campaign->publicLinks()
                ->where('status', PublicRecruitmentLink::STATUS_ACTIVE)
                ->latest('id')
                ->first(),
            'invites' => $this->campaign->invites()
                ->where('status', '!=', RecruitmentCampaignInvite::STATUS_CANCELLED)
                ->latest('id')
                ->get(),
        ]);
    }

    private function success(string $message): void
    {
        $this->notice = $message;
        $this->noticeType = 'success';
        $this->resetValidation();
    }

    private function error(string $message): void
    {
        $this->notice = $message;
        $this->noticeType = 'error';
    }
}
