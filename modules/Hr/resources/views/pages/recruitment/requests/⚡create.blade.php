<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Livewire\Concerns\Recruitment\ManagesRecruitmentRequestForm;
use Modules\Hr\Livewire\Concerns\Recruitment\SubmitsRecruitmentRequests;

new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.request_create')] class extends Component {
    use AuthorizesRecruitment;
    use ManagesRecruitmentRequestForm;
    use SubmitsRecruitmentRequests;

    public function mount(): void
    {
        $this->requirePermission('recruitment-requests.view', 'recruitment-requests.manage');
        $this->initializeForm();
    }
};
?>

<div class="mx-auto max-w-5xl space-y-6">
    <x-ui.detail-header
        :back="\App\Support\BackUrl::for(route('hr.recruitment.requests.index'))"
        :breadcrumbs="[['label' => __('hr_recruitment.hub.section')], ['label' => __('hr_recruitment.hub.titles.requests'), 'href' => route('hr.recruitment.requests.index')]]"
        :title="__('hr_recruitment.hub.titles.request_create')"
        :subtitle="__('hr_recruitment.form_ui.lead')"
    />

    <x-hr::recruitment.request-form :options="$this->formView" />
</div>
