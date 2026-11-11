{{-- One evaluation's content (an evaluation or one of its revisions). Only included for a viewer the policy let see it. --}}
@php
    // A withdrawn evaluation no longer counts, so its recommendation loses its tone; revisions are a record and keep theirs.
    $counts = ! ($evaluation instanceof \Modules\Hr\Models\RecruitmentInterviewEvaluation && $evaluation->isWithdrawn());
@endphp
<dl class="mt-3 space-y-2 text-sm">
    <div>
        <dt class="{{ $muted }}">{{ __($e.'recommendation') }}</dt>
        <dd class="mt-0.5">
            <x-ui.enum-badge :case="$evaluation->recommendation" :label="__($e.'recommendations.'.$evaluation->recommendation)" :tone="$counts ? $recommendationTone($evaluation->recommendation) : \App\Enums\StatusTone::Neutral" />
        </dd>
    </div>
    @foreach (['pros', 'cons', 'notes'] as $field)
        @if ($evaluation->{$field})
            <div>
                <dt class="{{ $muted }}">{{ __($e.$field) }}</dt>
                <dd class="mt-0.5 whitespace-pre-line" dir="auto">{{ $evaluation->{$field} }}</dd>
            </div>
        @endif
    @endforeach
</dl>
