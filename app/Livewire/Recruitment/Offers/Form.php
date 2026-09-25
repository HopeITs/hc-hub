<?php

namespace App\Livewire\Recruitment\Offers;

use App\Enums\ContractPlace;
use App\Enums\ContractType as EmploymentType;
use App\Enums\Currency;
use App\Enums\SalaryPer;
use App\Models\ContractType;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentJobOffer;
use App\Services\RecruitmentJobOfferService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Phase 2 Task 9: the offer's terms — create an offer for a selected
 * application, edit a draft, or revise a sent / refused / expired /
 * cancelled offer into a new version.
 *
 * HR only, and only users who may see pay figures (the policy requires
 * canViewSalary() to write an offer), so the salary is loaded into the form
 * for exactly the people allowed to see it. The version the form was opened
 * on is locked here; the service refuses a save made against an older one.
 */
class Form extends Component
{
    #[Locked]
    public ?int $applicationId = null;

    #[Locked]
    public ?int $offerId = null;

    /** 'create' | 'edit' | 'revise' */
    #[Locked]
    public string $mode = 'create';

    #[Locked]
    public ?int $expectedVersionId = null;

    public int|string $contractTypeId = '';

    public string $salary = '';

    public int|string $currency = Currency::USD;

    public int|string $salaryPer = SalaryPer::Month;

    public string $startDate = '';

    public string $endDate = '';

    public int|string $weeklyHours = '';

    public int|string $probationDays = 90;

    public int|string $employmentType = '';

    public int|string $place = '';

    public string $notes = '';

    /** Revise only: why this version differs from the last. Internal. */
    public string $reason = '';

    public function mount(?int $applicationId = null, ?int $offerId = null, string $mode = 'create'): void
    {
        if ($offerId === null) {
            abort_if($applicationId === null, 404);
            $application = $this->resolveApplication($applicationId);
            Gate::authorize('create', [RecruitmentJobOffer::class, $application]);
            $this->applicationId = $application->getKey();

            return;
        }

        $offer = $this->resolveOffer($offerId);
        Gate::authorize('manage', $offer);
        $this->mode = $mode === 'revise' ? 'revise' : 'edit';
        abort_if($this->mode === 'edit' && ! $offer->isEditable(), 404);
        abort_if($this->mode === 'revise' && ! app(RecruitmentJobOfferService::class)->canRevise($offer), 404);

        $version = $offer->currentVersion()->firstOrFail();
        $this->offerId = $offer->getKey();
        $this->applicationId = $offer->application_id;
        $this->expectedVersionId = $version->getKey();
        $this->contractTypeId = (int) $version->contract_type_id;
        $this->salary = (string) $version->salary_amount;
        $this->currency = (int) $version->currency;
        $this->salaryPer = (int) $version->salary_per;
        $this->startDate = (string) $version->start_date?->toDateString();
        $this->endDate = (string) $version->end_date?->toDateString();
        $this->weeklyHours = (string) $version->weekly_hours;
        $this->probationDays = (int) $version->probation_days;
        $this->employmentType = (string) ($version->terms_json['employment_type'] ?? '');
        $this->place = (string) ($version->terms_json['place'] ?? '');
        $this->notes = (string) ($version->terms_json['notes'] ?? '');
    }

    public function save(RecruitmentJobOfferService $offers): void
    {
        $this->resetErrorBag();
        $input = [
            'contractTypeId' => $this->contractTypeId,
            'salary' => $this->salary,
            'currency' => $this->currency,
            'salaryPer' => $this->salaryPer,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'weeklyHours' => $this->weeklyHours,
            'probationDays' => $this->probationDays,
            'employmentType' => $this->employmentType,
            'place' => $this->place,
            'notes' => $this->notes,
        ];

        try {
            if ($this->offerId === null) {
                $application = $this->resolveApplication((int) $this->applicationId);
                Gate::authorize('create', [RecruitmentJobOffer::class, $application]);
                $offer = $offers->create(auth()->user(), $application, $input);
                $message = __('recruitment-applicant.offers.created');
            } else {
                $offer = $this->resolveOffer($this->offerId);
                Gate::authorize('manage', $offer);
                $offer = $this->mode === 'revise'
                    ? $offers->revise(auth()->user(), $offer, (int) $this->expectedVersionId, $input, $this->reason)
                    : $offers->updateDraft(auth()->user(), $offer, (int) $this->expectedVersionId, $input);
                $message = __('recruitment-applicant.offers.'.($this->mode === 'revise' ? 'revised' : 'updated'));
            }
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        }

        session()->flash('recruitment_saved', $message);
        $this->redirectRoute('recruitment.offers.show', ['offerId' => $offer->getKey()], navigate: true);
    }

    public function render()
    {
        return view('livewire.recruitment.offers.form', [
            'application' => $this->resolveApplication((int) $this->applicationId)->loadMissing('applicant', 'campaign.vacancy.position'),
            'contractTypes' => ContractType::query()->orderBy('id')->get(),
            'currencies' => [Currency::USD, Currency::SYP, Currency::EUR],
            'salaryPers' => [SalaryPer::Month, SalaryPer::Hour, SalaryPer::Session],
            'employmentTypes' => [EmploymentType::FullTime, EmploymentType::PartTime, EmploymentType::Freelance],
            'places' => [ContractPlace::Onsite, ContractPlace::Online],
        ]);
    }

    private function resolveApplication(int $applicationId): RecruitmentApplication
    {
        $application = RecruitmentApplication::query()->visibleTo(auth()->user())->findOrFail($applicationId);
        Gate::authorize('view', $application);

        return $application;
    }

    private function resolveOffer(int $offerId): RecruitmentJobOffer
    {
        $offer = RecruitmentJobOffer::query()->visibleTo(auth()->user())->findOrFail($offerId);
        Gate::authorize('view', $offer);

        return $offer;
    }
}
