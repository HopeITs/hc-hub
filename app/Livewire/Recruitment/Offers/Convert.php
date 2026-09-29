<?php

namespace App\Livewire\Recruitment\Offers;

use App\Enums\SalaryPer;
use App\Models\RecruitmentHiringConversion;
use App\Models\RecruitmentJobOffer;
use App\Services\RecruitmentHiringConversionService as Conversions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * Phase 2 Task 10: hire the candidate whose offer was accepted — create the
 * employee (or link the one they already are) and the contract, from the
 * accepted version of the offer.
 *
 * The contract terms are shown read-only: they are the accepted snapshot and
 * nothing here can change them. What HR fills in is who the employee is — the
 * same fields as the employee form — and, when the pay is per session, the
 * session length the offer does not carry.
 *
 * What would stop the hire (vacancy closed or full, position full or
 * inactive…) is listed before anything is typed; the service checks it again
 * on the locked rows when HR confirms.
 */
class Convert extends Component
{
    #[Locked]
    public int $offerId;

    /** 'create' | 'link' */
    public string $mode = '';

    public string $employeeId = '';

    /** @var array<string, mixed> the new employee; see Conversions::prefill() */
    public array $employee = [];

    public bool $confirmNotDuplicate = false;

    public string $sessionDuration = '';

    public function mount(int $offerId, Conversions $conversions): void
    {
        $this->offerId = $offerId;
        $conversion = $this->resolveConversion();

        if ($conversion->isConverted()) {
            return;
        }

        Gate::authorize('convert', $conversion);

        $this->employee = $conversions->prefill($conversion);

        // An internal applicant is that employee: linking is preselected.
        $internal = $conversions->matches($conversion)
            ->first(fn (array $match): bool => in_array(Conversions::MATCH_INTERNAL, $match['reasons'], true));

        $this->mode = $internal ? Conversions::MODE_LINK : Conversions::MODE_CREATE;
        $this->employeeId = $internal ? (string) $internal['employee']->getKey() : '';
    }

    public function save(Conversions $conversions)
    {
        $conversion = $this->resolveConversion();
        Gate::authorize('convert', $conversion);
        $this->resetErrorBag();

        try {
            $conversions->convert(auth()->user(), $conversion, [
                'mode' => $this->mode,
                'employeeId' => $this->employeeId,
                'employee' => $this->employee,
                'confirmNotDuplicate' => $this->confirmNotDuplicate,
                'sessionDuration' => $this->sessionDuration,
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return null;
        } catch (AuthorizationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            // Already rolled back and recorded on the conversion as a failed
            // attempt; HR gets a plain message rather than an error page.
            report($exception);
            $this->addError('conversion', __('recruitment-applicant.conversions.errors.unexpected'));

            return null;
        }

        session()->flash('recruitment_toast', __('recruitment-applicant.conversions.converted'));

        return $this->redirectRoute('recruitment.offers.show', ['offerId' => $this->offerId], navigate: true);
    }

    public function render(Conversions $conversions)
    {
        $conversion = $this->resolveConversion()->load([
            'jobOffer.application.applicant',
            'jobOffer.position',
            'jobOffer.vacancy',
            'jobOfferVersion.contractType',
            'employee',
            'contract',
            'convertedBy.profile',
        ]);
        $offer = $conversion->jobOffer;
        $converted = $conversion->isConverted();

        return view('livewire.recruitment.offers.convert', [
            'conversion' => $conversion,
            'offer' => $offer,
            'version' => $conversion->jobOfferVersion,
            'converted' => $converted,
            'canSeeSalary' => Gate::allows('viewSalary', $offer),
            'blockers' => $converted ? [] : $conversions->blockers($conversion),
            'matches' => $converted ? collect() : $conversions->matches($conversion, $this->employee['nationalNumber'] ?? null),
            'needsSessionDuration' => (int) $conversion->jobOfferVersion?->salary_per === SalaryPer::Session,
        ]);
    }

    /** Out of reach is a 404, like everywhere else on these pages. */
    private function resolveConversion(): RecruitmentHiringConversion
    {
        $offer = RecruitmentJobOffer::query()->visibleTo(auth()->user())->findOrFail($this->offerId);
        Gate::authorize('view', $offer);

        return RecruitmentHiringConversion::query()->where('job_offer_id', $offer->getKey())->firstOrFail();
    }
}
