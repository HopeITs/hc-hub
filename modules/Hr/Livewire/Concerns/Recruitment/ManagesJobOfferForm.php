<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Recruitment;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Hr\Actions\Recruitment\RecruitmentJobOfferService;
use Modules\Hr\Enums\ContractPlace;
use Modules\Hr\Enums\ContractType as EmploymentType;
use Modules\Hr\Enums\Currency;
use Modules\Hr\Enums\SalaryPer;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\RecruitmentApplication;
use Modules\Hr\Models\RecruitmentJobOffer;
use Modules\Hr\Support\Recruitment\OfferTerms;

/**
 * The offer's terms: create an offer for a selected application, edit a
 * draft, or revise a sent / refused / expired / cancelled offer into a new
 * version. HR only, and only users who may see pay figures (the policy
 * requires canViewSalary() to write an offer), so the salary is loaded into
 * the form for exactly the people allowed to see it. The version the form
 * was opened on is locked; the service refuses a save made against an older one.
 *
 * Needs AuthorizesRecruitment and ResolvesJobOffers.
 */
trait ManagesJobOfferForm
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

    public int|string $currency = Currency::USD->value;

    public int|string $salaryPer = SalaryPer::Month->value;

    public string $startDate = '';

    public string $endDate = '';

    public int|string $weeklyHours = '';

    /** Typed by HR as they see fit; not checked beyond a number. */
    public int|string $probationDays = '';

    public int|string $employmentType = '';

    public int|string $place = '';

    public string $notes = '';

    /** Revise only: why this version differs from the last. Internal. */
    public string $reason = '';

    public function save(RecruitmentJobOfferService $offers): void
    {
        $this->requirePermission('recruitment-applicants.manage-offers');
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
                $offer = $offers->create($this->viewer(), $application, $input);
                $message = __('hr_recruitment.applicant_ui.offers.created');
            } else {
                $offer = $this->resolveOffer($this->offerId);
                Gate::authorize('manage', $offer);
                $offer = $this->mode === 'revise'
                    ? $offers->revise($this->viewer(), $offer, (int) $this->expectedVersionId, $input, $this->reason)
                    : $offers->updateDraft($this->viewer(), $offer, (int) $this->expectedVersionId, $input);
                $message = __('hr_recruitment.applicant_ui.offers.'.($this->mode === 'revise' ? 'revised' : 'updated'));
            }
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        }

        session()->flash('recruitment_toast', $message);
        $this->redirectRoute('hr.recruitment.offers.show', ['offerId' => $offer->getKey()], navigate: true);
    }

    #[Computed]
    public function application(): RecruitmentApplication
    {
        return $this->resolveApplication((int) $this->applicationId)->load([
            'applicant:id,full_name',
            'campaign:id,vacancy_id',
            'campaign.vacancy:id,position_id',
            'campaign.vacancy.position:id,title_en,title_ar',
        ]);
    }

    /** @return array<string, array<int|string, string>> the pickers' choices */
    #[Computed]
    public function options(): array
    {
        return [
            // Trial or service only, as the recruitment request offers (recruitment.request_form.contract_types).
            'contractTypes' => ContractType::query()->recruitmentOptions()->orderBy('id')
                ->get(['id', 'name_en', 'name_ar'])
                ->mapWithKeys(fn (ContractType $type): array => [$type->id => (string) $type->name])
                ->all(),
            'currencies' => collect([Currency::USD, Currency::SYP, Currency::EUR])->mapWithKeys(fn (Currency $case): array => [$case->value => OfferTerms::currency($case->value)])->all(),
            'salaryPers' => collect([SalaryPer::Month, SalaryPer::Hour, SalaryPer::Session])->mapWithKeys(fn (SalaryPer $case): array => [$case->value => OfferTerms::salaryPer($case->value)])->all(),
            'employmentTypes' => collect([EmploymentType::FullTime, EmploymentType::PartTime, EmploymentType::Freelance])->mapWithKeys(fn (EmploymentType $case): array => [$case->value => OfferTerms::employmentType($case->value)])->all(),
            'places' => collect([ContractPlace::Onsite, ContractPlace::Online])->mapWithKeys(fn (ContractPlace $case): array => [$case->value => OfferTerms::place($case->value)])->all(),
        ];
    }

    /** Fills the form from the offer's current version — the terms being edited or revised. */
    protected function loadCurrentVersion(RecruitmentJobOffer $offer): void
    {
        $version = $offer->currentVersion()->firstOrFail();
        $this->offerId = $offer->getKey();
        $this->applicationId = $offer->application_id;
        $this->expectedVersionId = $version->getKey();
        // An older version on a type no longer offered leaves the choice to HR instead of showing a wrong one.
        $this->contractTypeId = ContractType::query()->recruitmentOptions()->whereKey($version->contract_type_id)->exists()
            ? (int) $version->contract_type_id
            : '';
        $this->salary = (string) $version->salary_amount;
        $this->currency = (int) $version->currency;
        $this->salaryPer = (int) $version->salary_per;
        $this->startDate = (string) $version->start_date?->toDateString();
        $this->endDate = (string) $version->end_date?->toDateString();
        $this->weeklyHours = (string) $version->weekly_hours;
        $this->probationDays = $version->probation_days === null ? '' : (int) $version->probation_days;
        $this->employmentType = (string) ($version->terms_json['employment_type'] ?? '');
        $this->place = (string) ($version->terms_json['place'] ?? '');
        $this->notes = (string) ($version->terms_json['notes'] ?? '');
    }
}
