<?php

namespace App\Livewire\Recruitment\Campaigns;

use App\Enums\HiringCampaignStatus;
use App\Models\HiringCampaign;
use App\Models\Vacancy;
use App\Services\HiringCampaignService;
use DomainException;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $vacancyId = null;

    public string $startsAt = '';

    public string $endsAt = '';

    public ?int $editingCampaignId = null;

    public string $editingStartsAt = '';

    public string $editingEndsAt = '';

    public ?int $disablingCampaignId = null;

    public string $disableReason = '';

    public ?string $notice = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', HiringCampaign::class);
        $this->startsAt = now()->format('Y-m-d\TH:i');
        $this->endsAt = now()->addDays(14)->format('Y-m-d\TH:i');
    }

    public function create(HiringCampaignService $campaigns)
    {
        $this->resetErrorBag();
        $this->notice = null;
        $this->validate([
            'vacancyId' => ['required', 'integer'],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['required', 'date', 'after:startsAt'],
        ]);

        $vacancy = Vacancy::query()
            ->visibleTo(auth()->user())
            ->whereKey($this->vacancyId)
            ->firstOrFail();

        try {
            $campaign = $campaigns->create(auth()->user(), $vacancy, [
                'starts_at' => $this->startsAt,
                'ends_at' => $this->endsAt,
            ]);
        } catch (DomainException $exception) {
            $this->addError('campaign', $exception->getMessage());

            return null;
        }

        session()->flash('campaign_notice', __('recruitment.campaign.created'));

        return $this->redirectRoute('recruitment.campaigns.publishing', $campaign);
    }

    public function beginScheduleEdit(int $campaignId): void
    {
        $campaign = $this->visibleCampaign($campaignId);
        Gate::authorize('update', $campaign);

        $this->editingCampaignId = $campaign->getKey();
        $this->editingStartsAt = $campaign->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->editingEndsAt = $campaign->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->resetErrorBag();
    }

    public function cancelScheduleEdit(): void
    {
        $this->reset('editingCampaignId', 'editingStartsAt', 'editingEndsAt');
        $this->resetErrorBag();
    }

    public function updateSchedule(HiringCampaignService $campaigns): void
    {
        $this->validate([
            'editingCampaignId' => ['required', 'integer'],
            'editingStartsAt' => ['required', 'date'],
            'editingEndsAt' => ['required', 'date', 'after:editingStartsAt'],
        ]);
        $campaign = $this->visibleCampaign((int) $this->editingCampaignId);

        try {
            $campaigns->updateSchedule(auth()->user(), $campaign, [
                'starts_at' => $this->editingStartsAt,
                'ends_at' => $this->editingEndsAt,
            ]);
        } catch (DomainException $exception) {
            $this->addError('campaign', $exception->getMessage());

            return;
        }

        $this->cancelScheduleEdit();
        $this->notice = __('recruitment.campaign.schedule_saved');
    }

    public function openCampaign(int $campaignId, HiringCampaignService $campaigns): void
    {
        $this->runStateAction(
            fn () => $campaigns->open(auth()->user(), $this->visibleCampaign($campaignId)),
            __('recruitment.campaign.opened'),
        );
    }

    public function closeCampaign(int $campaignId, HiringCampaignService $campaigns): void
    {
        $this->runStateAction(
            fn () => $campaigns->close(auth()->user(), $this->visibleCampaign($campaignId)),
            __('recruitment.campaign.closed'),
        );
    }

    public function beginDisable(int $campaignId): void
    {
        $campaign = $this->visibleCampaign($campaignId);
        Gate::authorize('manage', $campaign);
        $this->disablingCampaignId = $campaign->getKey();
        $this->disableReason = '';
        $this->resetErrorBag();
    }

    public function cancelDisable(): void
    {
        $this->reset('disablingCampaignId', 'disableReason');
        $this->resetErrorBag();
    }

    public function disableCampaign(HiringCampaignService $campaigns): void
    {
        try {
            $campaigns->disable(
                auth()->user(),
                $this->visibleCampaign((int) $this->disablingCampaignId),
                $this->disableReason,
            );
        } catch (DomainException $exception) {
            $this->addError('disableReason', $exception->getMessage());

            return;
        }

        $this->cancelDisable();
        $this->notice = __('recruitment.campaign.disabled');
    }

    public function render()
    {
        Gate::authorize('viewAny', HiringCampaign::class);
        $user = auth()->user();

        $campaigns = HiringCampaign::query()
            ->visibleTo($user)
            ->with([
                'vacancy.position.city',
                'vacancy.position.program',
                'vacancy.createdFromRequest',
                'jobPost.currentVersion',
                'applicationForm.currentVersion',
            ])
            ->latest('id')
            ->paginate(12);

        $availableVacancies = collect();
        if ($user->hasActivePermission('recruitment-campaigns.manage')) {
            $availableVacancies = Vacancy::query()
                ->visibleTo($user)
                ->available()
                ->whereDoesntHave('campaigns', fn ($query) => $query->whereIn('status', [
                    HiringCampaignStatus::Draft,
                    HiringCampaignStatus::Published,
                    HiringCampaignStatus::Open,
                ]))
                ->with(['position.city', 'position.program', 'createdFromRequest'])
                ->orderByDesc('id')
                ->get();
        }

        return view('livewire.recruitment.campaigns.index', [
            'campaigns' => $campaigns,
            'availableVacancies' => $availableVacancies,
            'activeStatuses' => [
                HiringCampaignStatus::Draft,
                HiringCampaignStatus::Published,
                HiringCampaignStatus::Open,
            ],
        ]);
    }

    private function visibleCampaign(int $campaignId): HiringCampaign
    {
        return HiringCampaign::query()
            ->visibleTo(auth()->user())
            ->findOrFail($campaignId);
    }

    private function runStateAction(callable $action, string $success): void
    {
        $this->resetErrorBag();
        $this->notice = null;

        try {
            $action();
        } catch (DomainException $exception) {
            $this->addError('campaign', $exception->getMessage());

            return;
        }

        $this->notice = $success;
    }
}
