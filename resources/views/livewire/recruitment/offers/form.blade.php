@php use App\Support\Recruitment\OfferTerms; @endphp
<div class="space-y-6 p-6">
    <div>
        <a href="{{ $offerId ? route('recruitment.offers.show', ['offerId' => $offerId]) : route('recruitment.applicants.show', ['applicantId' => $application->applicant_id]) }}"
           wire:navigate class="text-sm text-primary hover:underline">&larr; {{ __('recruitment-applicant.interviews.back') }}</a>
        <h1 class="mt-2 text-h3 font-semibold">{{ __('recruitment-applicant.offers.form_title.'.$mode) }}</h1>
        <p class="mt-1 text-sm text-secondary-medium">
            {{ $application->applicant?->full_name }}
            &middot; {{ $application->campaign?->vacancy?->position?->title ?? __('recruitment-applicant.no_vacancy') }}
            &middot; {{ __('recruitment-applicant.statuses.'.$application->status) }}
        </p>
    </div>

    @error('offer')
        <p role="alert" class="rounded-control border border-primary bg-primary/5 p-3 text-sm text-primary">{{ $message }}</p>
    @enderror

    <form wire:submit="save" class="space-y-6">
        <section class="space-y-4 rounded-surface border border-neutral-200 bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment-applicant.offers.pay') }}</h2>
            <p class="text-xs text-secondary-medium">{{ __('recruitment-applicant.offers.salary_confidential') }}</p>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <x-forms.input-label for="salary" required :value="__('recruitment-applicant.offers.salary')" />
                    <x-forms.input id="salary" type="number" model="salary" required />
                    <x-forms.input-error field="salary" input-id="salary" />
                </div>
                <div>
                    <x-forms.input-label for="currency" required :value="__('recruitment-applicant.offers.currency')" />
                    <select id="currency" wire:model="currency" class="w-full rounded-control border border-neutral-300 bg-white px-3 py-2 text-body">
                        @foreach ($currencies as $value)
                            <option value="{{ $value }}">{{ OfferTerms::currency($value) }}</option>
                        @endforeach
                    </select>
                    <x-forms.input-error field="currency" input-id="currency" />
                </div>
                <div>
                    <x-forms.input-label for="salaryPer" required :value="__('recruitment-applicant.offers.salary_per')" />
                    <select id="salaryPer" wire:model="salaryPer" class="w-full rounded-control border border-neutral-300 bg-white px-3 py-2 text-body">
                        @foreach ($salaryPers as $value)
                            <option value="{{ $value }}">{{ OfferTerms::salaryPer($value) }}</option>
                        @endforeach
                    </select>
                    <x-forms.input-error field="salaryPer" input-id="salaryPer" />
                </div>
            </div>
        </section>

        <section class="space-y-4 rounded-surface border border-neutral-200 bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment-applicant.offers.contract_details') }}</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <x-forms.input-label for="contractTypeId" required :value="__('recruitment-applicant.offers.contract_type')" />
                    <select id="contractTypeId" wire:model="contractTypeId" class="w-full rounded-control border border-neutral-300 bg-white px-3 py-2 text-body">
                        <option value="">{{ __('recruitment.choose') }}</option>
                        @foreach ($contractTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                    <x-forms.input-error field="contractTypeId" input-id="contractTypeId" />
                </div>
                <div>
                    <x-forms.input-label for="employmentType" :value="__('recruitment-applicant.offers.employment_type')" />
                    <select id="employmentType" wire:model="employmentType" class="w-full rounded-control border border-neutral-300 bg-white px-3 py-2 text-body">
                        <option value="">—</option>
                        @foreach ($employmentTypes as $value)
                            <option value="{{ $value }}">{{ OfferTerms::employmentType($value) }}</option>
                        @endforeach
                    </select>
                    <x-forms.input-error field="employmentType" input-id="employmentType" />
                </div>
                <div>
                    <x-forms.input-label for="place" :value="__('recruitment-applicant.offers.place')" />
                    <select id="place" wire:model="place" class="w-full rounded-control border border-neutral-300 bg-white px-3 py-2 text-body">
                        <option value="">—</option>
                        @foreach ($places as $value)
                            <option value="{{ $value }}">{{ OfferTerms::place($value) }}</option>
                        @endforeach
                    </select>
                    <x-forms.input-error field="place" input-id="place" />
                </div>
                <div>
                    <x-forms.input-label for="startDate" required :value="__('recruitment-applicant.offers.start_date')" />
                    <x-forms.input id="startDate" type="date" model="startDate" required />
                    <x-forms.input-error field="startDate" input-id="startDate" />
                </div>
                <div>
                    <x-forms.input-label for="endDate" :value="__('recruitment-applicant.offers.end_date')" />
                    <x-forms.input id="endDate" type="date" model="endDate" />
                    <x-forms.input-error field="endDate" input-id="endDate" />
                </div>
                <div>
                    <x-forms.input-label for="probationDays" required :value="__('recruitment-applicant.offers.probation_days')" />
                    <x-forms.input id="probationDays" type="number" model="probationDays" required />
                    <x-forms.input-error field="probationDays" input-id="probationDays" />
                </div>
                <div>
                    <x-forms.input-label for="weeklyHours" :value="__('recruitment-applicant.offers.weekly_hours')" />
                    <x-forms.input id="weeklyHours" type="number" model="weeklyHours" />
                    <x-forms.input-error field="weeklyHours" input-id="weeklyHours" />
                </div>
            </div>
            <div>
                <x-forms.input-label for="notes" :value="__('recruitment-applicant.offers.other_terms')" />
                <x-forms.textarea id="notes" wire:model="notes" rows="3" />
                <x-forms.input-error field="notes" input-id="notes" />
            </div>
        </section>

        <section class="space-y-4 rounded-surface border border-neutral-200 bg-white p-6">
            @if ($mode === 'revise')
                <div>
                    <x-forms.input-label for="reason" required :value="__('recruitment-applicant.offers.revision_reason')" />
                    <x-forms.textarea id="reason" wire:model="reason" rows="2" />
                    <p class="mt-1 text-xs text-secondary-medium">{{ __('recruitment-applicant.offers.revision_reason_hint') }}</p>
                    <x-forms.input-error field="reason" input-id="reason" />
                </div>
            @endif
            <p class="text-xs text-secondary-medium">{{ __('recruitment-applicant.offers.draft_hint') }}</p>
            <div class="flex justify-end">
                <x-primary-button type="submit" wire:loading.attr="disabled">{{ __('recruitment-applicant.offers.save.'.$mode) }}</x-primary-button>
            </div>
        </section>
    </form>
</div>
