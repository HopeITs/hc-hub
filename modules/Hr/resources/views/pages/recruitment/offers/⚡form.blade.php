<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Livewire\Concerns\Recruitment\ManagesJobOfferForm;
use Modules\Hr\Livewire\Concerns\Recruitment\ResolvesJobOffers;
use Modules\Hr\Models\RecruitmentJobOffer;

/*
 * Create the offer for a selected application, or edit a draft. The create
 * route passes applicationId, the edit route offerId — and nothing else:
 * revising has its own page (offers/revise), so no third optional route
 * parameter can land in the wrong place.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.offer_form')] class extends Component {
    use AuthorizesRecruitment;
    use ManagesJobOfferForm;
    use ResolvesJobOffers;

    public function mount(?int $applicationId = null, ?int $offerId = null): void
    {
        $this->requirePermission('recruitment-applicants.manage-offers');

        if ($offerId === null) {
            abort_if($applicationId === null, 404);
            $application = $this->resolveApplication($applicationId);
            Gate::authorize('create', [RecruitmentJobOffer::class, $application]);
            $this->applicationId = $application->getKey();

            return;
        }

        $offer = $this->resolveOffer($offerId);
        Gate::authorize('manage', $offer);
        abort_unless($offer->isEditable(), 404);

        $this->mode = 'edit';
        $this->loadCurrentVersion($offer);
    }
};
?>

@php
    $application = $this->application;
    $title = __('hr_recruitment.applicant_ui.offers.form_title.'.$mode);
    $back = $offerId
        ? route('hr.recruitment.offers.show', ['offerId' => $offerId])
        : (Route::has('hr.recruitment.applicants.show') ? route('hr.recruitment.applicants.show', ['applicantId' => $application->applicant_id]) : route('hr.recruitment.offers.index'));
@endphp

<div class="space-y-6">
    <x-ui.detail-header
        :back="\App\Support\BackUrl::for($back)"
        :breadcrumbs="[['label' => __('hr_recruitment.hub.section')], ['label' => __('hr_recruitment.hub.titles.offers'), 'href' => route('hr.recruitment.offers.index')]]"
        :title="$title"
    >
        <x-slot:meta>
            <span class="font-medium"><bdi>{{ $application->applicant?->full_name }}</bdi></span>
            <span dir="auto">{{ $application->campaign?->vacancy?->position?->title ?? __('hr_recruitment.applicant_ui.no_vacancy') }}</span>
            <span>{{ __('hr_recruitment.applicant_ui.statuses.'.$application->status) }}</span>
        </x-slot:meta>
    </x-ui.detail-header>

    <x-ui.unsaved-guard :watch="['contractTypeId', 'salary', 'currency', 'salaryPer', 'startDate', 'endDate', 'weeklyHours', 'probationDays', 'employmentType', 'place', 'notes', 'reason']" />

    <x-hr::recruitment.offer-form :options="$this->options" :mode="$mode" />
</div>
