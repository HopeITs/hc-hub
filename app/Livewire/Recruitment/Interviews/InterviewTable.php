<?php

namespace App\Livewire\Recruitment\Interviews;

use App\Enums\RecruitmentInterviewLocationType;
use App\Enums\RecruitmentInterviewStatus;
use App\Enums\RecruitmentInterviewType;
use App\Models\RecruitmentInterview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

/**
 * Phase 2 Task 7: the interview list.
 *
 * Every row comes through RecruitmentInterview::visibleTo(): a participant
 * sees the interviews they are on, HR sees every interview of an application
 * in their recruitment scope. Filters only narrow that query. Cancelled
 * interviews stay in the list (nothing is deleted); a status filter hides them.
 *
 * No meeting link and no internal note is ever a column here.
 */
class InterviewTable extends LivewireTable
{
    public string $sortColumn = 'scheduled_at';

    public string $sortDirection = 'desc';

    protected string $model = RecruitmentInterview::class;

    public function boot(): void
    {
        Gate::authorize('viewAny', RecruitmentInterview::class);
    }

    protected function query(): Builder
    {
        Gate::authorize('viewAny', RecruitmentInterview::class);

        return RecruitmentInterview::query()
            ->visibleTo(auth()->user())
            ->with(['application.applicant', 'application.campaign.vacancy.position'])
            ->withCount(['currentParticipants as participants_count']);
    }

    protected function link(Model $model): ?string
    {
        return route('recruitment.interviews.show', ['interviewId' => $model->getKey()]);
    }

    protected function columns(): array
    {
        return [
            Column::make(__('recruitment-applicant.interviews.when'), 'scheduled_at')
                ->displayUsing(fn ($value, RecruitmentInterview $interview) => $value
                    ? $value->copy()->setTimezone($interview->timezone ?: config('app.timezone'))->format('Y-m-d H:i')
                    : '—')
                ->sortable(),
            Column::make(__('recruitment-applicant.name'), 'application.applicant.full_name')
                ->sortable()
                ->searchable(),
            Column::make(__('recruitment-applicant.campaign'), 'application.campaign.vacancy.position.'.transColumn('title'))
                ->searchable(),
            Column::make(__('recruitment-applicant.interviews.type'), 'interview_type')
                ->displayUsing(fn ($value) => __('recruitment-applicant.interviews.types.'.$value))
                ->sortable(),
            Column::make(__('recruitment-applicant.status'), function (RecruitmentInterview $interview): string {
                return '<span class="rounded-control px-2 py-0.5 text-xs font-semibold '.RecruitmentInterviewStatus::classes($interview->status).'">'
                    .e(__('recruitment-applicant.interviews.statuses.'.$interview->status)).'</span>';
            })->asHtml(),
            Column::make(__('recruitment-applicant.interviews.location'), 'location_type')
                ->displayUsing(fn ($value) => __('recruitment-applicant.interviews.location_types.'.$value)),
            Column::make(__('recruitment-applicant.interviews.participants'), fn (RecruitmentInterview $interview) => (string) $interview->participants_count),
        ];
    }

    protected function filters(): array
    {
        return [
            SelectFilter::make(__('recruitment-applicant.status'), 'status')->options(
                collect(RecruitmentInterviewStatus::values())
                    ->mapWithKeys(fn (string $status) => [$status => __('recruitment-applicant.interviews.statuses.'.$status)])
                    ->all(),
            ),
            SelectFilter::make(__('recruitment-applicant.interviews.type'), 'interview_type')->options(
                collect(RecruitmentInterviewType::values())
                    ->mapWithKeys(fn (string $type) => [$type => __('recruitment-applicant.interviews.types.'.$type)])
                    ->all(),
            ),
            SelectFilter::make(__('recruitment-applicant.interviews.location'), 'location_type')->options(
                collect(RecruitmentInterviewLocationType::values())
                    ->mapWithKeys(fn (string $type) => [$type => __('recruitment-applicant.interviews.location_types.'.$type)])
                    ->all(),
            ),
            // Only ever narrows: "mine" is the interviews this user is on.
            SelectFilter::make(__('recruitment-applicant.interviews.mine_filter'), function (Builder $query, $value): void {
                if ($value === 'mine') {
                    $query->whereHas('currentParticipants', fn (Builder $participants) => $participants->where('user_id', auth()->id()));
                }
            }, 'mine')->options(['mine' => __('recruitment-applicant.interviews.mine')]),
        ];
    }
}
