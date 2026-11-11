<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Recruitment;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Models\RecruitmentInterview;

/**
 * Shared reads of the interview pages: one interview resolved through
 * visibleTo() and the policy (out of reach is a 404, never a 403), the
 * column and relation lists a row needs, and the small helpers that place a
 * time in the interview's own time zone.
 */
trait ReadsInterviews
{
    /** The columns a list row, a board card and a calendar chip read. */
    protected function interviewColumns(): array
    {
        return [
            'recruitment_interviews.id', 'recruitment_interviews.application_id', 'recruitment_interviews.interview_type',
            'recruitment_interviews.status', 'recruitment_interviews.scheduled_at', 'recruitment_interviews.ends_at',
            'recruitment_interviews.timezone', 'recruitment_interviews.location_type', 'recruitment_interviews.location_value',
            'recruitment_interviews.outcome_recorded_at', 'recruitment_interviews.evaluations_closed_at',
        ];
    }

    /** @return array<int|string, mixed> eager loads for a list row, each with its columns */
    protected function interviewRelations(): array
    {
        return [
            'application:id,applicant_id,campaign_id,vacancy_id,status',
            'application.applicant:id,full_name',
            'application.vacancy:id,position_id',
            'application.vacancy.position:id,title_en,title_ar',
            'currentParticipants:id,interview_id,user_id,role,removed_at',
            'currentParticipants.user:id,email,employee_id',
        ];
    }

    protected function findInterview(int $interviewId): RecruitmentInterview
    {
        $interview = RecruitmentInterview::query()->visibleTo($this->viewer())->findOrFail($interviewId);
        Gate::authorize('view', $interview);

        return $interview;
    }

    protected function zone(RecruitmentInterview $interview): string
    {
        return $interview->timezone ?: (string) config('app.timezone');
    }

    protected function zoned(RecruitmentInterview $interview, ?CarbonInterface $date): ?CarbonInterface
    {
        return $date?->copy()->setTimezone($this->zone($interview));
    }

    /**
     * Which of these users have an active employee record: the old
     * isActiveUser() for a whole list in one query.
     *
     * @param  array<int, int|string>  $userIds
     * @return array<int, true>
     */
    protected function activeUserIds(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        if ($userIds === []) {
            return [];
        }

        return User::query()->whereKey($userIds)
            ->whereHas('employee', fn (Builder $employee): Builder => $employee->where('is_active', true))
            ->pluck('id')
            ->mapWithKeys(fn ($id): array => [(int) $id => true])
            ->all();
    }
}
