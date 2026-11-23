<?php

declare(strict_types=1);

namespace Modules\Hr\Support\CriticalPositions;

use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Enums\RecruitmentApplicationStatus as Status;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\RecruitmentApplicant;
use Modules\Hr\Models\RecruitmentApplication;

/**
 * Old CVs: people who reached the last stages of an earlier campaign and were
 * not hired. Everything the recruitment module already enforces still holds:
 * the viewer needs the applicant policy's viewAny, sees only applications in
 * their recruitment scope, and applicants who were anonymized, whose retention
 * date passed, or who are volunteers already (the internal source covers them)
 * are never listed. Only the name, the stage reached and the position are
 * shown; the full file stays behind the recruitment applicant page and its own
 * checks.
 */
final class PastApplicantsSource implements CandidateSource
{
    /**
     * Stages that count as "reached the final stages". Hired is excluded on purpose.
     *
     * @var list<string>
     */
    public const array FINAL_STAGES = [
        Status::Finalist,
        Status::Selected,
        Status::OfferSent,
        Status::OfferAccepted,
        Status::OfferRejected,
    ];

    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function suggest(Position $position, User $viewer, int $limit): array
    {
        if (Gate::forUser($viewer)->denies('viewAny', RecruitmentApplicant::class)) {
            return [];
        }

        $applications = $this->scopes->scopeApplications(RecruitmentApplication::query(), $viewer)
            ->whereIn('status', self::FINAL_STAGES)
            ->whereHas('applicant', fn ($applicants) => $applicants
                ->whereNull('anonymized_at')
                ->whereNull('employee_id')
                ->where(fn ($retention) => $retention->whereNull('retention_until')->orWhere('retention_until', '>', now()))
                ->whereDoesntHave('applications', fn ($others) => $others->where('status', Status::Hired)))
            ->whereHas('vacancy.position', fn ($positions) => $positions->whereKey($position->getKey())
                ->orWhere(fn ($same) => $same
                    ->when($position->department_id, fn ($query) => $query->where('department_id', $position->department_id))
                    ->when($position->job_level_id, fn ($query) => $query->where('job_level_id', $position->job_level_id))
                    ->when(! $position->department_id && ! $position->job_level_id, fn ($query) => $query->whereRaw('0 = 1'))))
            ->with(['applicant', 'vacancy.position'])
            ->latest('id')
            ->limit($limit * 4)
            ->get();

        return array_values($applications
            ->unique('applicant_id')
            ->sortByDesc(fn (RecruitmentApplication $application): int => (int) ($application->vacancy?->position_id === $position->getKey()))
            ->take($limit)
            ->map(function (RecruitmentApplication $application) use ($position): CandidateSuggestion {
                $samePosition = $application->vacancy?->position_id === $position->getKey();

                return new CandidateSuggestion(
                    source: CandidateSuggestion::CV,
                    id: (int) $application->applicant_id,
                    name: (string) $application->applicant->full_name,
                    score: null,
                    reasons: [
                        ['key' => 'hr_positions.critical.cv.reached', 'params' => ['stage' => (string) __('hr_recruitment.applicant_ui.statuses.'.$application->status)]],
                        ['key' => $samePosition ? 'hr_positions.critical.cv.same_position' : 'hr_positions.critical.cv.similar_position', 'params' => ['position' => (string) $application->vacancy?->position?->title]],
                    ],
                    url: route('hr.recruitment.applicants.show', ['applicantId' => $application->applicant_id]),
                );
            })
            ->values()
            ->all());
    }
}
