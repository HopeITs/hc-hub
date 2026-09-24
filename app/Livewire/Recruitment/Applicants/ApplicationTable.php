<?php

namespace App\Livewire\Recruitment\Applicants;

use App\Enums\HiringCampaignStatus;
use App\Enums\RecruitmentApplicationStatus;
use App\Enums\RecruitmentCvAvailability;
use App\Models\HiringCampaign;
use App\Models\RecruitmentApplicant;
use App\Models\RecruitmentApplication;
use App\Services\HiringCampaignStateMachine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Filters\DateFilter;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

/**
 * One row per application, so the campaign, status and date filters each
 * match a single submission rather than any of an applicant's several.
 *
 * Every row comes through RecruitmentApplication::visibleTo(), and so does
 * every filter option; a campaign id typed into the query string only
 * narrows a result that is already scoped.
 */
class ApplicationTable extends LivewireTable
{
    public string $sortColumn = 'submitted_at';

    public string $sortDirection = 'desc';

    protected string $model = RecruitmentApplication::class;

    public function boot(): void
    {
        Gate::authorize('viewAny', RecruitmentApplicant::class);
    }

    protected function query(): Builder
    {
        Gate::authorize('viewAny', RecruitmentApplicant::class);

        return RecruitmentApplication::query()
            ->visibleTo(auth()->user())
            ->addSelect(['has_previous_application' => $this->earlierApplication()])
            ->with(['applicant', 'campaign.vacancy.position', 'cvFiles']);
    }

    protected function link(Model $model): ?string
    {
        return route('recruitment.applicants.show', ['applicantId' => $model->applicant_id]);
    }

    /** @return array<int, Column> */
    protected function columns(): array
    {
        $states = app(HiringCampaignStateMachine::class);

        return [
            Column::make(__('recruitment-applicant.name'), 'applicant.full_name')
                ->searchable()
                ->sortable(),

            Column::make(__('recruitment-applicant.campaign'), function (RecruitmentApplication $application) use ($states): string {
                $title = e($application->campaign?->vacancy?->position?->title ?? __('recruitment-applicant.no_vacancy'));

                // A closed or expired campaign is still worth reviewing, but the
                // reviewer should know nothing more will arrive.
                if ($application->campaign !== null) {
                    $effective = $states->effective($application->campaign);
                    if (! in_array($effective, [HiringCampaignStatus::Published, HiringCampaignStatus::Open], true)) {
                        $title .= ' <span class="ms-1 rounded-control bg-neutral-100 px-2 py-0.5 text-xs text-secondary-medium">'
                            .e(HiringCampaignStatus::getLocalizedName($effective)['value']).'</span>';
                    }
                }

                return $title;
            })->asHtml(),

            Column::make(__('recruitment-applicant.applied_at'), 'submitted_at')
                ->sortable()
                ->displayUsing(fn ($value): string => $value?->format('Y-m-d') ?? '—'),

            Column::make(__('recruitment-applicant.status'), 'status')
                ->sortable()
                ->displayUsing(fn ($value): string => __('recruitment-applicant.statuses.'.$value)),

            Column::make(__('recruitment-applicant.source'), 'applicant.source')
                ->displayUsing(fn ($value): string => RecruitmentApplicant::sourceLabel($value)),

            Column::make(__('recruitment-applicant.cv'), function (RecruitmentApplication $application): string {
                $availability = RecruitmentCvAvailability::of($application->cvFiles);

                return '<span class="rounded-control px-2 py-0.5 text-xs font-semibold '.RecruitmentCvAvailability::classes($availability).'">'
                    .e(__('recruitment-applicant.cv_availability.'.$availability)).'</span>';
            })->asHtml(),

            // A flag, never a decision: nothing is rejected for having applied before.
            Column::make(__('recruitment-applicant.previous_applicant'), function (RecruitmentApplication $application): string {
                return $application->has_previous_application
                    ? '<span class="rounded-control bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-800">'
                        .e(__('recruitment-applicant.previous_applicant_flag')).'</span>'
                    : '';
            })->asHtml(),
        ];
    }

    /** @return array<int, SelectFilter|DateFilter> */
    protected function filters(): array
    {
        $user = auth()->user();

        return [
            SelectFilter::make(__('recruitment-applicant.campaign'), 'campaign_id')
                ->options(HiringCampaign::query()
                    ->visibleTo($user)
                    ->with('vacancy.position')
                    ->latest('id')
                    ->get()
                    ->mapWithKeys(fn (HiringCampaign $campaign): array => [
                        $campaign->getKey() => ($campaign->vacancy?->position?->title ?? '—')
                            .($campaign->starts_at ? ' · '.$campaign->starts_at->format('Y-m-d') : ''),
                    ])
                    ->all()),

            SelectFilter::make(__('recruitment-applicant.status'), 'status')
                ->options(collect(RecruitmentApplicationStatus::values())
                    ->mapWithKeys(fn (string $status): array => [$status => __('recruitment-applicant.statuses.'.$status)])
                    ->all()),

            DateFilter::make(__('recruitment-applicant.applied_at'), 'submitted_at'),

            SelectFilter::make(__('recruitment-applicant.source'), 'applicant.source')
                ->options(RecruitmentApplicant::query()
                    ->visibleTo($user)
                    ->distinct()
                    ->orderBy('source')
                    ->pluck('source')
                    ->mapWithKeys(fn (string $source): array => [$source => RecruitmentApplicant::sourceLabel($source)])
                    ->all()),
        ];
    }

    /**
     * Did this applicant apply to anything before this application — in any
     * campaign, in scope or not? Only the yes/no leaves this query; which
     * campaign it was stays behind the scope.
     */
    private function earlierApplication(): QueryBuilder
    {
        return DB::table('recruitment_applications as prior')
            ->selectRaw('1')
            ->whereColumn('prior.applicant_id', 'recruitment_applications.applicant_id')
            ->where(fn ($earlier) => $earlier
                ->whereColumn('prior.submitted_at', '<', 'recruitment_applications.submitted_at')
                ->orWhere(fn ($tie) => $tie
                    ->whereColumn('prior.submitted_at', '=', 'recruitment_applications.submitted_at')
                    ->whereColumn('prior.id', '<', 'recruitment_applications.id')))
            ->limit(1);
    }
}
