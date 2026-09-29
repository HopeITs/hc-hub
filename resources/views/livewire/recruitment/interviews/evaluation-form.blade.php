@php use App\Enums\RecruitmentEvaluationRecommendation as Recommendation; @endphp
<div>
    @if ($canEvaluate)
        <section class="rounded-surface border border-neutral-200 bg-white p-6 shadow-e1">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 class="text-h4 font-semibold">{{ __('recruitment-applicant.evaluations.my_title') }}</h2>
                    <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-applicant.evaluations.my_hint') }}</p>
                </div>
                @if ($own?->isWithdrawn())
                    <span class="rounded-control bg-neutral-100 px-2 py-0.5 text-xs font-semibold text-secondary-medium">{{ __('recruitment-applicant.evaluations.withdrawn') }}</span>
                @elseif ($own)
                    <span class="text-xs text-secondary-medium">{{ __('recruitment-applicant.evaluations.version', ['version' => $own->version]) }}</span>
                @endif
            </div>

            @if (session('recruitment_evaluation_saved'))
                <p role="status" class="mt-4 rounded-control border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('recruitment_evaluation_saved') }}</p>
            @endif

            @error('evaluation')
                <div role="alert" class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-control border border-primary bg-white p-3 text-sm text-primary">
                    <span>{{ $message }}</span>
                    <button type="button" wire:click="reload" class="font-semibold underline">{{ __('recruitment-applicant.evaluations.reload') }}</button>
                </div>
            @enderror

            @if ($own?->isWithdrawn())
                <p class="mt-4 text-sm text-secondary-medium">{{ __('recruitment-applicant.evaluations.errors.withdrawn') }}</p>
            @elseif ($closedReason !== null)
                <p class="mt-4 rounded-control bg-neutral-50 p-3 text-sm text-secondary-medium">{{ __('recruitment-applicant.evaluations.errors.'.$closedReason) }}</p>
                @if ($own)
                    @include('livewire.recruitment.interviews.partials.evaluation-content', ['evaluation' => $own])
                @endif
            @else
                <form wire:submit="save" class="mt-4 space-y-4">
                    @php $recommendationInvalid = $errors->has('recommendation'); @endphp
                    <fieldset role="radiogroup" aria-required="true"
                              @if ($recommendationInvalid) aria-invalid="true" aria-describedby="recommendation-error" @endif>
                        <legend class="text-body font-medium text-neutral-800">
                            {{ __('recruitment-applicant.evaluations.recommendation') }}
                            <span class="text-primary ltr:ml-1 rtl:mr-1" aria-hidden="true">*</span>
                            <span class="sr-only">({{ __('general.a11y.required-field') }})</span>
                        </legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach (Recommendation::values() as $value)
                                <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-control border px-3 text-sm transition sm:min-h-9 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-primary
                                              {{ $recommendation === $value ? 'border-neutral-900 bg-selection font-semibold text-neutral-900' : 'border-neutral-300 hover:bg-neutral-50' }}">
                                    <input type="radio" wire:model.live="recommendation" value="{{ $value }}" class="text-primary focus:ring-0">
                                    {{ __('recruitment-applicant.evaluations.recommendations.'.$value) }}
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-secondary-medium">{{ __('recruitment-applicant.evaluations.advisory') }}</p>
                        <x-forms.input-error field="recommendation" input-id="recommendation" />
                    </fieldset>

                    @foreach (['pros', 'cons', 'notes'] as $field)
                        <div>
                            <x-forms.input-label :for="'evaluation-'.$field" :value="__('recruitment-applicant.evaluations.'.$field)" />
                            <x-forms.textarea :id="'evaluation-'.$field" wire:model="{{ $field }}" rows="3" />
                            <x-forms.input-error :field="$field" :input-id="'evaluation-'.$field" />
                        </div>
                    @endforeach

                    <div class="flex justify-end">
                        <x-primary-button type="submit" wire:loading.attr="disabled">
                            {{ $own ? __('recruitment-applicant.evaluations.update') : __('recruitment-applicant.evaluations.submit') }}
                        </x-primary-button>
                    </div>
                </form>
            @endif

            @if ($own && $own->revisions->count() > 1)
                <details class="mt-4 text-sm">
                    <summary class="cursor-pointer font-medium">{{ __('recruitment-applicant.evaluations.history') }} ({{ $own->revisions->count() }})</summary>
                    @include('livewire.recruitment.interviews.partials.evaluation-history', ['revisions' => $own->revisions])
                </details>
            @endif
        </section>
    @endif
</div>
