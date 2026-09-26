@php use App\Enums\RecruitmentEvaluationRecommendation as Recommendation; @endphp
<div>
    @if ($canEvaluate)
        <section class="rounded-surface border border-neutral-200 bg-white p-6">
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
                <div role="alert" class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-control border border-primary bg-primary/5 p-3 text-sm text-primary">
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
                    <fieldset>
                        <legend class="text-body font-medium text-neutral-800">{{ __('recruitment-applicant.evaluations.recommendation') }} <span class="text-primary">*</span></legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach (Recommendation::values() as $value)
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-control border px-3 py-1.5 text-sm {{ $recommendation === $value ? 'border-primary bg-primary/5' : 'border-neutral-300' }}">
                                    <input type="radio" wire:model.live="recommendation" value="{{ $value }}" class="text-primary focus:ring-primary">
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
