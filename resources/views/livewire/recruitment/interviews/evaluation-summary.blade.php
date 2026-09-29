@php use App\Enums\RecruitmentEvaluationRecommendation as Recommendation; @endphp
<section class="rounded-surface border border-neutral-200 bg-white p-6 shadow-e1">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-h4 font-semibold">{{ __('recruitment-applicant.evaluations.summary_title') }}</h2>
            <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-applicant.evaluations.summary_hint') }}</p>
        </div>
        @if ($canClose)
            <button type="button"
                    x-on:click="$dispatch('open-recruitment-confirmation', { scope: 'interview-evaluations', action: 'closeEvaluations', message: @js(__('recruitment-applicant.evaluations.close_confirm')), confirmLabel: @js(__('recruitment-applicant.evaluations.close')) })"
                    class="min-h-11 cursor-pointer rounded-control border border-neutral-300 bg-white px-3 text-sm shadow-e1 transition hover:bg-selection sm:min-h-9 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                {{ __('recruitment-applicant.evaluations.close') }}
            </button>
        @elseif ($interview->evaluations_closed_at)
            <span class="text-xs text-secondary-medium">{{ __('recruitment-applicant.evaluations.closed_on', ['date' => $interview->evaluations_closed_at->format('Y-m-d H:i')]) }}</span>
        @endif
    </div>

    @if (session('recruitment_evaluation_summary_saved'))
        <p role="status" class="mt-4 rounded-control border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('recruitment_evaluation_summary_saved') }}</p>
    @endif
    @error('evaluation')
        <p role="alert" class="mt-4 rounded-control border border-primary bg-white p-3 text-sm text-primary">{{ $message }}</p>
    @enderror

    {{-- Tally: advisory, never a score or a decision. --}}
    <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
        <span class="font-medium">{{ trans_choice('recruitment-applicant.evaluations.count', $summary['total'], ['count' => $summary['total']]) }}</span>
        @foreach ($summary['counts'] as $value => $count)
            @if ($count > 0)
                <span class="rounded-control px-2 py-0.5 text-xs font-semibold {{ Recommendation::classes($value) }}">
                    {{ __('recruitment-applicant.evaluations.recommendations.'.$value) }}: {{ $count }}
                </span>
            @endif
        @endforeach
    </div>
    <p class="mt-2 text-xs text-secondary-medium">{{ __('recruitment-applicant.evaluations.advisory') }}</p>

    @if ($evaluations->isEmpty())
        <p class="mt-4 text-sm text-secondary-medium">
            {{ $closedReason ? __('recruitment-applicant.evaluations.errors.'.$closedReason) : __('recruitment-applicant.evaluations.none_yet') }}
        </p>
    @else
        <ul class="mt-4 space-y-4">
            @foreach ($evaluations as $evaluation)
                <li class="rounded-control border border-neutral-200 p-4 {{ $evaluation->isWithdrawn() ? 'bg-neutral-50 text-secondary-medium' : '' }}" wire:key="evaluation-{{ $evaluation->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <p class="font-semibold">
                            {{ $evaluation->reviewer?->profile?->name ?? $evaluation->reviewer?->email }}
                            @if ($roles->has($evaluation->reviewer_user_id))
                                <span class="font-normal text-secondary-medium">&middot; {{ __('recruitment-applicant.interviews.roles.'.$roles->get($evaluation->reviewer_user_id)) }}</span>
                            @endif
                        </p>
                        <span class="text-xs text-secondary-medium">
                            {{ __('recruitment-applicant.evaluations.submitted_on', ['date' => $evaluation->submitted_at?->format('Y-m-d H:i')]) }}
                            &middot; v{{ $evaluation->version }}
                            @if ($evaluation->revisions->contains('action', \App\Models\RecruitmentInterviewEvaluation::ACTION_CORRECTED))
                                &middot; <span class="font-semibold text-amber-800">{{ __('recruitment-applicant.evaluations.corrected_badge') }}</span>
                            @endif
                        </span>
                    </div>

                    @if ($evaluation->isWithdrawn())
                        <p class="mt-2 text-sm">
                            {{ __('recruitment-applicant.evaluations.withdrawn_on', ['date' => $evaluation->withdrawn_at->format('Y-m-d H:i'), 'by' => $evaluation->withdrawnBy?->profile?->name ?? $evaluation->withdrawnBy?->email]) }}
                            — {{ $evaluation->withdrawal_reason }}
                        </p>
                    @endif

                    @include('livewire.recruitment.interviews.partials.evaluation-content', ['evaluation' => $evaluation])

                    @if (! $evaluation->isWithdrawn())
                        @can('correct', $evaluation)
                            <div class="mt-3 flex gap-3 text-sm">
                                <button type="button" wire:click="beginCorrect({{ $evaluation->id }})" class="font-medium text-neutral-900 underline decoration-neutral-300 underline-offset-4 hover:decoration-neutral-900">{{ __('recruitment-applicant.evaluations.correct') }}</button>
                                <button type="button" wire:click="beginWithdraw({{ $evaluation->id }})" class="text-secondary-dark hover:underline">{{ __('recruitment-applicant.evaluations.withdraw') }}</button>
                            </div>
                        @endcan
                    @endif

                    @if ($panel !== null && $evaluationId === $evaluation->id)
                        <div class="mt-3 space-y-3 rounded-control border border-neutral-300 bg-neutral-50 p-4">
                            <h3 class="font-semibold">{{ __('recruitment-applicant.evaluations.'.$panel.'_title') }}</h3>

                            @if ($panel === 'correct')
                                <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="{{ __('recruitment-applicant.evaluations.recommendation') }}"
                                     @if ($errors->has('recommendation')) aria-invalid="true" aria-describedby="correct-recommendation-error" @endif>
                                    @foreach (Recommendation::values() as $value)
                                        <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-control border border-neutral-300 bg-white px-3 text-sm sm:min-h-9 has-[:checked]:border-neutral-900 has-[:checked]:bg-selection has-[:checked]:font-semibold has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-primary">
                                            <input type="radio" wire:model="recommendation" value="{{ $value }}" class="text-primary focus:ring-0">
                                            {{ __('recruitment-applicant.evaluations.recommendations.'.$value) }}
                                        </label>
                                    @endforeach
                                </div>
                                <x-forms.input-error field="recommendation" input-id="correct-recommendation" />
                                @foreach (['pros', 'cons', 'notes'] as $field)
                                    <div>
                                        <x-forms.input-label :for="'correct-'.$field" :value="__('recruitment-applicant.evaluations.'.$field)" />
                                        <x-forms.textarea :id="'correct-'.$field" wire:model="{{ $field }}" rows="2" />
                                        <x-forms.input-error :field="$field" :input-id="'correct-'.$field" />
                                    </div>
                                @endforeach
                            @endif

                            <div>
                                <x-forms.input-label for="evaluation-reason" required :value="__('recruitment-applicant.evaluations.reason')" />
                                <x-forms.textarea id="evaluation-reason" wire:model="reason" rows="2" />
                                <p class="mt-1 text-xs text-secondary-medium">{{ __('recruitment-applicant.evaluations.reason_hint') }}</p>
                                <x-forms.input-error field="reason" input-id="evaluation-reason" />
                            </div>

                            <div class="flex gap-2">
                                <x-primary-button type="button" wire:click="{{ $panel === 'correct' ? 'saveCorrection' : 'withdraw' }}" wire:loading.attr="disabled">
                                    {{ __('recruitment-applicant.evaluations.'.$panel.'_confirm') }}
                                </x-primary-button>
                                <button type="button" wire:click="cancelPanel" class="px-3 py-1.5 text-sm text-secondary-dark hover:underline">{{ __('recruitment-applicant.cancel') }}</button>
                            </div>
                        </div>
                    @endif

                    @if ($evaluation->revisions->count() > 1)
                        <details class="mt-3 text-sm">
                            <summary class="cursor-pointer font-medium">{{ __('recruitment-applicant.evaluations.history') }} ({{ $evaluation->revisions->count() }})</summary>
                            @include('livewire.recruitment.interviews.partials.evaluation-history', ['revisions' => $evaluation->revisions])
                        </details>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <x-recruitment.confirmation-modal scope="interview-evaluations" />
</section>
