<div class="space-y-6 p-6">
    <div>
        <h1 id="applicants-title" tabindex="-1" class="scroll-mt-24 text-h3 font-semibold">{{ __('recruitment-applicant.applicants') }}</h1>
        <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-applicant.applicants_hint') }}</p>
    </div>

    <livewire:recruitment.applicants.bulk-tags />

    <livewire:recruitment.applicants.bulk-reject />

    <livewire:recruitment.applicants.application-table />

    <livewire:recruitment.applicants.tag-manager />
</div>
