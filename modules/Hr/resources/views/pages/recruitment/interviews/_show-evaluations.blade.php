{{--
    The interview's evaluations, included by ⚡show.blade.php: the viewer's
    own form (only for a current participant in a reviewer role), then every
    reviewer's evaluation for HR with view-evaluations and for the requester.
    Advisory: nothing here moves the application.
--}}
@php
    use Modules\Hr\Enums\RecruitmentEvaluationRecommendation as Recommendation;
    use Modules\Hr\Models\RecruitmentInterviewEvaluation;

    $own = $state['own'];
    $closedReason = $state['closedReason'];
    $recommendationTone = fn (?string $value) => match ($value) {
        Recommendation::StrongYes, Recommendation::Yes => \App\Enums\StatusTone::Good,
        Recommendation::No, Recommendation::StrongNo => \App\Enums\StatusTone::Critical,
        default => \App\Enums\StatusTone::Warn,
    };
@endphp

@if ($state['canEvaluate'])
    <x-ui.panel id="my-evaluation" class="scroll-mt-24" :title="__($e.'my_title')" :subtitle="__($e.'my_hint')">
        <div class="space-y-4 p-4">
            @if ($own && ! $own->isWithdrawn() && $closedReason === null)
                <p role="status" class="text-sm {{ $muted }}">{{ __($e.'saved_on', ['date' => $at($own->updated_at)]) }}</p>
            @endif

            @error('evaluation')
                <div role="alert" class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-status-critical/40 p-3 text-sm text-status-critical">
                    <span>{{ $message }}</span>
                    <button type="button" wire:click="reloadEvaluation" wire:confirm="{{ __($e.'reload_confirm') }}" class="font-semibold underline underline-offset-4">{{ __($e.'reload') }}</button>
                </div>
            @enderror

            @if ($own?->isWithdrawn())
                <p class="text-sm {{ $muted }}">{{ __($e.'errors.withdrawn') }}</p>
            @elseif ($closedReason !== null)
                <p class="rounded-md bg-secondary-soft/60 p-3 text-sm dark:bg-surface-2">{{ __($e.'errors.'.$closedReason) }}</p>
                @if ($own)
                    @include('hr::pages.recruitment.interviews._evaluation-content', ['evaluation' => $own])
                @endif
            @else
                <form wire:submit="saveEvaluation" class="space-y-4">
                    <flux:radio.group wire:model="recommendation" :label="__($e.'recommendation')" variant="segmented" required>
                        @foreach (Recommendation::values() as $value)
                            <flux:radio :value="$value" :label="__($e.'recommendations.'.$value)" />
                        @endforeach
                    </flux:radio.group>
                    <p class="text-xs {{ $muted }}">{{ __($e.'advisory') }}</p>
                    @error('recommendation')<p class="text-sm text-status-critical">{{ $message }}</p>@enderror

                    <x-ui.form-textarea wire:model="pros" :label="__($e.'pros')" rows="3" maxlength="{{ $state['maxLength'] }}" dir="auto" />
                    <x-ui.form-textarea wire:model="cons" :label="__($e.'cons')" rows="3" maxlength="{{ $state['maxLength'] }}" dir="auto" />
                    <x-ui.form-textarea wire:model="notes" :label="__($e.'notes')" rows="3" maxlength="{{ $state['maxLength'] }}" dir="auto" />

                    <div class="flex justify-end">
                        <x-ui.button type="submit" tone="primary" wire:loading.attr="disabled" wire:target="saveEvaluation">{{ $own ? __($e.'update') : __($e.'submit') }}</x-ui.button>
                    </div>
                </form>
            @endif

            @if ($own && $own->revisions->count() > 1)
                <details class="text-sm">
                    <summary class="cursor-pointer font-medium">{{ __($e.'history') }} ({{ $own->revisions->count() }})</summary>
                    @include('hr::pages.recruitment.interviews._evaluation-history', ['revisions' => $own->revisions])
                </details>
            @endif
        </div>
    </x-ui.panel>
@endif

@if ($state['canViewEvaluations'])
    @php
        $summary = $state['summary'];
        $roles = $interview->participants->pluck('role', 'user_id');
        $noteEvaluation = $noteEvaluationId !== null ? $state['evaluations']->firstWhere('id', $noteEvaluationId) : null;
    @endphp
    <x-ui.panel :title="__($e.'summary_title')" :subtitle="__($e.'summary_hint')">
        <x-slot:toolbar>
            @if ($state['canClose'])
                <x-ui.button size="sm" tone="secondary" wire:click="$set('showCloseEvaluations', true)">{{ __($e.'close') }}</x-ui.button>
            @elseif ($interview->evaluations_closed_at)
                <span class="text-xs {{ $muted }}">{{ __($e.'closed_on', ['date' => $at($interview->evaluations_closed_at)]) }}</span>
            @endif
        </x-slot:toolbar>

        <div class="space-y-4 p-4">
            {{-- Tally: advisory, never a score or a decision. --}}
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="font-medium">{{ trans_choice($e.'count', $summary['total'], ['count' => $summary['total']]) }}</span>
                @foreach ($summary['counts'] as $value => $count)
                    @if ($count > 0)
                        <x-ui.enum-badge :case="$value" :label="__($e.'recommendations.'.$value).': '.$count" :tone="$recommendationTone($value)" />
                    @endif
                @endforeach
            </div>
            <p class="text-xs {{ $muted }}">{{ __($e.'advisory') }}</p>

            @if ($state['evaluations']->isEmpty())
                <p class="text-sm {{ $muted }}">{{ $closedReason ? __($e.'errors.'.$closedReason) : __($e.'none_yet') }}</p>
            @else
                <ul class="space-y-4">
                    @foreach ($state['evaluations'] as $evaluation)
                        <li @class(['rounded-md border border-panel-border p-4', 'bg-secondary-soft/40 '.$muted => $evaluation->isWithdrawn()]) wire:key="evaluation-{{ $evaluation->id }}">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <p class="font-semibold" dir="auto">
                                    {{ $who($evaluation->reviewer_user_id) }}
                                    @if ($roles->has($evaluation->reviewer_user_id))
                                        <span class="font-normal {{ $muted }}">· {{ __($t.'roles.'.$roles->get($evaluation->reviewer_user_id)) }}</span>
                                    @endif
                                </p>
                                <span class="text-xs {{ $muted }}">
                                    {{ __($e.'submitted_on', ['date' => $at($evaluation->submitted_at)]) }}
                                    @if ($evaluation->revisions->contains('action', RecruitmentInterviewEvaluation::ACTION_CORRECTED))
                                        · <span class="font-semibold">{{ __($e.'corrected_badge') }}</span>
                                    @endif
                                </span>
                            </div>

                            @if ($evaluation->isWithdrawn())
                                <p class="mt-2 text-sm">{{ __($e.'withdrawn_on', ['date' => $at($evaluation->withdrawn_at), 'by' => $who($evaluation->withdrawn_by_user_id)]) }} — {{ $evaluation->withdrawal_reason }}</p>
                            @endif

                            @include('hr::pages.recruitment.interviews._evaluation-content', ['evaluation' => $evaluation])

                            @if ($evaluation->opinionNotes->isNotEmpty())
                                <div class="mt-3 rounded-md border border-panel-border p-3">
                                    <p class="text-sm font-semibold">{{ __($e.'notes_title') }}</p>
                                    <ul class="mt-2 space-y-3">
                                        @foreach ($evaluation->opinionNotes as $opinionNote)
                                            <li class="text-sm" wire:key="opinion-note-{{ $opinionNote->id }}">
                                                <p class="text-xs {{ $muted }}">{{ $who($opinionNote->author_user_id) }} · {{ $at($opinionNote->created_at) }}</p>
                                                <p class="mt-1 whitespace-pre-line" dir="auto">{{ $opinionNote->body }}</p>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @can('addNote', $evaluation)
                                <x-ui.button class="mt-3" size="sm" tone="ghost" icon="chat-bubble-left-ellipsis" wire:click="beginNote({{ $evaluation->id }})">{{ __($e.'add_note') }}</x-ui.button>
                            @endcan

                            @if ($evaluation->revisions->count() > 1)
                                <details class="mt-3 text-sm">
                                    <summary class="cursor-pointer font-medium">{{ __($e.'history') }} ({{ $evaluation->revisions->count() }})</summary>
                                    @include('hr::pages.recruitment.interviews._evaluation-history', ['revisions' => $evaluation->revisions])
                                </details>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-ui.panel>

    {{-- HR's separate opinion note never changes the participant's evaluation. --}}
    <x-ui.modal wire:model="showNote" size="sm" close="closeNote" submit="addNote" :title="__($e.'note_title')" :description="$noteEvaluation ? $who($noteEvaluation->reviewer_user_id) : null">
        <div class="space-y-3">
            @error('evaluation')<p role="alert" class="text-sm text-status-critical">{{ $message }}</p>@enderror
            <x-ui.form-textarea wire:model="opinionNote" :label="__($e.'opinion_note')" :help="__($e.'note_hint')" rows="4" required dir="auto" />
        </div>
        <x-slot:actions>
            <x-ui.button type="submit" tone="primary" wire:loading.attr="disabled">{{ __($e.'note_confirm') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.modal>

    @if ($state['canClose'])
        <x-ui.confirm-modal
            wire:model="showCloseEvaluations"
            :title="__($e.'close')"
            :message="__($e.'close_confirm')"
            confirm="closeEvaluations"
            :confirm-label="__($e.'close')"
            tone="neutral"
        />
    @endif
@endif
