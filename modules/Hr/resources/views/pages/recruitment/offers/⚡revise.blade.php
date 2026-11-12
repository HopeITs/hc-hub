<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Actions\Recruitment\RecruitmentJobOfferService;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Livewire\Concerns\Recruitment\ManagesJobOfferForm;
use Modules\Hr\Livewire\Concerns\Recruitment\ResolvesJobOffers;

/*
 * Revise an offer into a new version — after HR changes the terms of a sent
 * offer, or after a refusal, an expiry or a cancellation. The same form and
 * save() as offers/form; its own page so the route passes offerId only.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.offer_revise')] class extends Component {
    use AuthorizesRecruitment;
    use ManagesJobOfferForm;
    use ResolvesJobOffers;

    public function mount(int $offerId): void
    {
        $this->requirePermission('recruitment-applicants.manage-offers');

        $offer = $this->resolveOffer($offerId);
        Gate::authorize('manage', $offer);
        abort_unless(app(RecruitmentJobOfferService::class)->canRevise($offer), 404);

        $this->mode = 'revise';
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
