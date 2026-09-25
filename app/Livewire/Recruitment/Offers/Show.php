<?php

namespace App\Livewire\Recruitment\Offers;

use App\Enums\RecruitmentJobOfferStatus as Status;
use App\Models\RecruitmentJobOffer;
use App\Services\RecruitmentJobOfferService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Phase 2 Task 9: one job offer — the internal offer document (printable),
 * every version as it was offered, and what happened to it.
 *
 * Seen by users with view-offers in scope. The salary is rendered only for
 * those who may see pay figures (viewSalary) and never kept on the
 * component, so it never reaches another browser in a Livewire payload.
 *
 * HR with manage-offers can send the draft, record the candidate's answer,
 * cancel, and start a revision or edit the draft (on the form). Each action
 * locks the version the user was looking at; the service refuses if the
 * offer moved on since.
 */
class Show extends Component
{
    #[Locked]
    public int $offerId;

    /** 'send' | 'response' | 'cancel' | null */
    #[Locked]
    public ?string $panel = null;

    #[Locked]
    public ?int $expectedVersionId = null;

    public string $expiresOn = '';

    public string $response = '';

    /** Internal: the candidate's reasons, as HR recorded them. */
    public string $responseNote = '';

    public string $cancelReason = '';

    public string $cancelNote = '';

    public function mount(int $offerId): void
    {
        $this->offerId = $offerId;
        $this->resolveOffer();
    }

    public function beginSend(): void
    {
        $this->openPanel('send');
        $this->expiresOn = today()->addDays((int) config('recruitment.offers.validity_days', 7))->toDateString();
    }

    public function send(RecruitmentJobOfferService $offers): void
    {
        abort_unless($this->panel === 'send', 404);
        $offer = $this->authorizedOffer();

        $this->attempt(fn () => $offers->send(auth()->user(), $offer, (int) $this->expectedVersionId, $this->expiresOn), 'sent');
    }

    public function beginResponse(string $response): void
    {
        abort_unless(in_array($response, [Status::Accepted, Status::Rejected], true), 403);
        $this->openPanel('response');
        $this->response = $response;
    }

    public function recordResponse(RecruitmentJobOfferService $offers): void
    {
        abort_unless($this->panel === 'response', 404);
        $offer = $this->authorizedOffer();

        $this->attempt(fn () => $offers->recordResponse(auth()->user(), $offer, (int) $this->expectedVersionId, $this->response, $this->responseNote), 'response_recorded');
    }

    public function beginCancel(): void
    {
        $this->openPanel('cancel');
    }

    public function cancelOffer(RecruitmentJobOfferService $offers): void
    {
        abort_unless($this->panel === 'cancel', 404);
        $offer = $this->authorizedOffer();

        $this->attempt(fn () => $offers->cancel(auth()->user(), $offer, (int) $this->expectedVersionId, $this->cancelReason, $this->cancelNote), 'cancelled');
    }

    public function closePanel(): void
    {
        $this->reset('panel', 'expectedVersionId', 'expiresOn', 'response', 'responseNote', 'cancelReason', 'cancelNote');
        $this->resetErrorBag();
    }

    public function render(RecruitmentJobOfferService $offers)
    {
        $offer = $this->resolveOffer()->load([
            'application.applicant',
            'position',
            'vacancy',
            'currentVersion.contractType',
            'versions' => fn ($versions) => $versions->orderByDesc('version_no'),
            'versions.contractType',
            'versions.creator.profile',
            'creator.profile',
            'responseRecordedBy.profile',
            'cancelledBy.profile',
            'hiringConversion',
        ]);

        return view('livewire.recruitment.offers.show', [
            'offer' => $offer,
            'canManage' => Gate::allows('manage', $offer),
            'canSeeSalary' => Gate::allows('viewSalary', $offer),
            'canSeeApplicant' => Gate::allows('view', $offer->application),
            'seatsLeft' => $offer->vacancy ? $offers->seatsLeft($offer->vacancy, $offer) : 0,
            'cancellationReasons' => $offers->cancellationReasons(),
        ]);
    }

    private function openPanel(string $panel): void
    {
        $offer = $this->authorizedOffer();

        $this->closePanel();
        $this->panel = $panel;
        $this->expectedVersionId = (int) $offer->current_version_id;
    }

    private function attempt(callable $action, string $messageKey): void
    {
        $this->resetErrorBag();

        try {
            $action();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        }

        $this->closePanel();
        session()->flash('recruitment_saved', __('recruitment-applicant.offers.'.$messageKey));
    }

    private function authorizedOffer(): RecruitmentJobOffer
    {
        $offer = $this->resolveOffer();
        Gate::authorize('manage', $offer);

        return $offer;
    }

    /** Out of reach is a 404, like everywhere else on these pages. */
    private function resolveOffer(): RecruitmentJobOffer
    {
        $offer = RecruitmentJobOffer::query()->visibleTo(auth()->user())->findOrFail($this->offerId);
        Gate::authorize('view', $offer);

        return $offer;
    }
}
