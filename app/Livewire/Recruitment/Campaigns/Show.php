<?php

namespace App\Livewire\Recruitment\Campaigns;

use App\Enums\HiringCampaignStatus as Status;
use App\Models\HiringCampaign;
use App\Services\HiringCampaignService;
use App\Services\HiringCampaignStateMachine;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One campaign, and the moves available to it right now.
 *
 * Which buttons appear is asked of the state machine rather than decided here,
 * so the screen and the server cannot disagree about what is possible. The
 * service re-checks everything anyway: a button that should not have been
 * shown still refuses when pressed.
 */
class Show extends Component
{
    #[Locked]
    public int $campaignId;

    public string $reason = '';

    public string $startsAt = '';

    public string $endsAt = '';

    public function mount(int $campaignId): void
    {
        $this->campaignId = $campaignId;
    }

    /** Publish, open, close, reopen, disable or reactivate. */
    public function act(string $action, HiringCampaignService $campaigns): void
    {
        $campaign = $this->resolve();
        $reason = trim($this->reason) ?: null;

        try {
            match ($action) {
                'publish' => $campaigns->publish(auth()->user(), $campaign),
                'open' => $campaigns->open(auth()->user(), $campaign),
                'close' => $campaigns->close(auth()->user(), $campaign, $reason),
                'reopen' => $campaigns->reopen(auth()->user(), $campaign),
                'disable' => $campaigns->disable(auth()->user(), $campaign, $reason),
                'reactivate' => $campaigns->reactivate(auth()->user(), $campaign),
                default => throw new InvalidArgumentException('Unknown campaign action.'),
            };
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        }

        $this->reason = '';
        session()->flash('recruitment_saved', __('recruitment-campaign.done'));
    }

    public function saveWindow(HiringCampaignService $campaigns): void
    {
        try {
            $campaigns->setWindow(auth()->user(), $this->resolve(), [
                'starts_at' => $this->startsAt,
                'ends_at' => $this->endsAt,
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        }

        session()->flash('recruitment_saved', __('recruitment-campaign.window_saved'));
    }

    public function render()
    {
        $campaign = $this->resolve();
        $states = app(HiringCampaignStateMachine::class);
        $service = app(HiringCampaignService::class);

        $this->startsAt = $this->startsAt ?: ($campaign->starts_at?->format('Y-m-d\TH:i') ?? '');
        $this->endsAt = $this->endsAt ?: ($campaign->ends_at?->format('Y-m-d\TH:i') ?? '');

        $effective = $states->effective($campaign);
        $mayManage = Gate::allows('manage', $campaign);

        return view('livewire.recruitment.campaigns.show', [
            'campaign' => $campaign,
            'effective' => $effective,
            // Asked of the machine, so the screen never offers a move the
            // server would refuse — and never hides one it would allow.
            'available' => $mayManage ? array_keys(array_filter([
                'publish' => $states->can($campaign, 'publish'),
                'open' => $states->can($campaign, 'open') && ! $states->hasEnded($campaign),
                'close' => $states->can($campaign, 'close'),
                'reopen' => $states->can($campaign, 'reopen') && ! $states->hasEnded($campaign),
                'disable' => $states->can($campaign, 'disable'),
                'reactivate' => $effective === Status::Disabled,
            ])) : [],
            'acceptsSubmissions' => $service->acceptsSubmissions($campaign),
            'publiclyVisible' => $service->isPubliclyVisible($campaign),
            'mayManage' => $mayManage,
        ]);
    }

    /** Re-resolved through the visibility boundary on every update. */
    private function resolve(): HiringCampaign
    {
        $campaign = HiringCampaign::query()->visibleTo(auth()->user())
            ->with(['vacancy.position', 'creator.profile', 'closedBy.profile'])
            ->findOrFail($this->campaignId);

        Gate::authorize('view', $campaign);

        return $campaign;
    }
}
