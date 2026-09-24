<?php

namespace App\Livewire\Recruitment\Campaigns;

use App\Models\HiringCampaign;
use App\Models\Vacancy;
use App\Services\HiringCampaignService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Opening a campaign against a seat.
 *
 * The seat is chosen first and everything else follows from it, because a
 * campaign with no seat advertises nothing. Several campaigns may run on one
 * seat and usually do, in sequence: round the organisation first, then out to
 * everybody, and sometimes to a named list.
 */
class Create extends Component
{
    /**
     * Chosen from the list, so not locked.
     *
     * Nothing is trusted about it either: the seat is re-read through the
     * visibility boundary and re-checked for availability before anything is
     * created, and the policy is asked about that seat specifically.
     */
    public ?int $vacancyId = null;

    public string $linkMode = HiringCampaign::PUBLIC_OPEN;

    public string $startsAt = '';

    public string $endsAt = '';

    public function mount(?int $vacancyId = null): void
    {
        $this->vacancyId = $vacancyId;
    }

    public function save(HiringCampaignService $campaigns)
    {
        $vacancy = $this->vacancy();

        if ($vacancy === null) {
            $this->addError('vacancyId', __('recruitment-campaign.choose_a_vacancy'));

            return null;
        }

        try {
            $campaign = $campaigns->create(auth()->user(), $vacancy, [
                'link_mode' => $this->linkMode,
                'starts_at' => $this->startsAt,
                'ends_at' => $this->endsAt,
            ]);
        } catch (ValidationException $exception) {
            // The service owns every rule; this only shows what it said.
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return null;
        }

        session()->flash('recruitment_saved', __('recruitment-campaign.created'));

        return $this->redirectRoute('recruitment.campaigns.show',
            ['campaignId' => $campaign->getKey()], navigate: true);
    }

    public function render()
    {
        Gate::authorize('viewAny', HiringCampaign::class);

        return view('livewire.recruitment.campaigns.create', [
            // Only seats that are actually being recruited for, and only the
            // ones this user is allowed to see.
            'vacancies' => Vacancy::query()->available()
                ->visibleTo(auth()->user())
                ->with('position')
                ->orderByDesc('id')
                ->get(),
            'linkModes' => HiringCampaign::LINK_MODES,
        ]);
    }

    private function vacancy(): ?Vacancy
    {
        if ($this->vacancyId === null) {
            return null;
        }

        return Vacancy::query()->available()
            ->visibleTo(auth()->user())
            ->whereKey($this->vacancyId)
            ->first();
    }
}
