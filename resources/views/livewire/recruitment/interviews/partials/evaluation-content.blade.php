{{-- One evaluation's content. Only ever included for a viewer the policy allowed to see it. --}}
@php use App\Enums\RecruitmentEvaluationRecommendation as Recommendation; @endphp
<dl class="mt-3 space-y-2 text-sm">
    <div>
        <dt class="text-secondary-medium">{{ __('recruitment-applicant.evaluations.recommendation') }}</dt>
        <dd class="mt-0.5">
            <span class="rounded-control px-2 py-0.5 text-xs font-semibold {{ Recommendation::classes($evaluation->recommendation) }}">
                {{ __('recruitment-applicant.evaluations.recommendations.'.$evaluation->recommendation) }}
            </span>
        </dd>
    </div>
    @foreach (['pros', 'cons', 'notes'] as $field)
        @if ($evaluation->{$field})
            <div>
                <dt class="text-secondary-medium">{{ __('recruitment-applicant.evaluations.'.$field) }}</dt>
                <dd class="mt-0.5 whitespace-pre-line">{{ $evaluation->{$field} }}</dd>
            </div>
        @endif
    @endforeach
</dl>
