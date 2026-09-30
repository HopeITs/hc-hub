<?php

namespace App\Livewire\Recruitment\Applicants;

use App\Enums\HiringCampaignStatus;
use App\Enums\RecruitmentApplicationStatus;
use App\Enums\RecruitmentCvAvailability;
use App\Enums\RecruitmentIndicatorOutcome;
use App\Models\RecruitmentApplicant;
use App\Models\RecruitmentApplication;
use App\Services\HiringCampaignStateMachine;
use App\Services\RecruitmentApplicationFilters;
use App\Services\RecruitmentMatchingIndicators;
use App\Support\Recruitment\MatchingIndicator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use RamonRietdijk\LivewireTables\Actions\Action;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Filters\DateFilter;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

/**
 * One row per application, so the campaign, status and date filters each
 * match a single submission rather than any of an applicant's several.
 *
 * Every row comes through RecruitmentApplication::visibleTo(), and so does
 * every filter option; each filter goes through RecruitmentApplicationFilters,
 * which only narrows. A campaign or city id typed into the query string can
 * shrink an already scoped result, never widen it.
 *
 * The indicator column is a summary for the reviewer, computed for the rows
 * on the page only. It is not sortable or filterable on purpose: a list
 * ranked by it would be a decision made by the tool.
 */
class ApplicationTable extends LivewireTable
{
    public const BULK_REJECT = 'bulk_reject';

    public string $sortColumn = 'submitted_at';

    public string $sortDirection = 'desc';

    protected string $model = RecruitmentApplication::class;

    /** @var array<int, \App\Support\Recruitment\MatchingIndicatorSet> for the current page */
    protected array $indicatorSets = [];

    public function boot(): void
    {
        Gate::authorize('viewAny', RecruitmentApplicant::class);
    }

    /**
     * The bulk action hands the selected ids to its panel without loading a
     * model for each; the panel's service re-scopes every id. It is
     * authorized here by its policy ability, and an unknown code is refused.
     */
    public function executeAction(string $code): mixed
    {
        [$ability, $event, $panel] = match ($code) {
            self::BULK_REJECT => ['bulkReject', 'recruitment-bulk-reject', BulkReject::class],
            default => abort(403),
        };

        Gate::authorize($ability, RecruitmentApplication::class);

        $this->dispatch($event, applicationIds: array_values($this->selected))->to($panel);

        return null;
    }

    /** No per-row actions exist; the call is refused rather than routed. */
    public function executeItemAction(string $code, string $key): mixed
    {
        abort(403);
    }

    /**
     * Selecting the whole filtered table reads ids only. The package default
     * hydrates every row with its relations, which on a large result set is
     * the slowest thing the page can do.
     */
    public function selectTable(bool $select): void
    {
        if (! $this->canSelect()) {
            return;
        }

        $table = $this->appliedQuery()
            ->setEagerLoads([])
            ->limit(app(\App\Services\RecruitmentApplicationStatusService::class)->bulkLimit())
            ->pluck('recruitment_applications.id')
            ->map(fn ($id): string => (string) $id);

        $selected = collect($this->selected);

        $this->selected = ($select
            ? $selected->merge($table)->unique()
            : $selected->diff($table))->values()->all();
        $this->selectedPage = false;

        $this->updateSession();
    }

    #[On('recruitment-decisions-changed')]
    public function refreshDecisions(): void
    {
        $this->clearSelection();
    }

    protected function query(): Builder
    {
        Gate::authorize('viewAny', RecruitmentApplicant::class);

        $filters = app(RecruitmentApplicationFilters::class);

        return RecruitmentApplication::query()
            ->visibleTo(auth()->user())
            ->addSelect(['has_previous_application' => $filters->earlierApplication(RecruitmentApplication::query())])
            ->with(['applicant', 'campaign.vacancy.position', 'cvFiles']);
    }

    /** Indicators for the rows on this page, in one pass. */
    protected function paginate(): LengthAwarePaginator
    {
        $paginator = parent::paginate();

        $this->indicatorSets = app(RecruitmentMatchingIndicators::class)
            ->evaluateMany(new Collection($paginator->items()));

        return $paginator;
    }

    protected function link(Model $model): ?string
    {
        return route('recruitment.applicants.show', ['applicantId' => $model->applicant_id]);
    }

    /** @return array<int, Action> */
    protected function actions(): array
    {
        return [
            Action::make(__('recruitment-applicant.decisions.bulk_action'), fn () => null, self::BULK_REJECT)
                ->canSee(Gate::allows('bulkReject', RecruitmentApplication::class))
                ->keepSelection(),
        ];
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
                        $title .= ' <span class="ms-1 rounded-control bg-neutral-100 px-2 py-0.5 text-xs text-secondary-dark">'
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
                ->displayUsing(fn ($value): string => '<span class="rounded-control px-2 py-0.5 text-xs font-semibold '
                    .RecruitmentApplicationStatus::classes($value).'">'.e(__('recruitment-applicant.statuses.'.$value)).'</span>')
                ->asHtml(),

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

            Column::make(__('recruitment-applicant.indicators.column'), function (RecruitmentApplication $application): string {
                return $this->indicatorSummary($application);
            })->asHtml(),
        ];
    }

    /** @return array<int, SelectFilter|DateFilter> */
    protected function filters(): array
    {
        $user = auth()->user();
        $filters = app(RecruitmentApplicationFilters::class);

        return [
            SelectFilter::make(__('recruitment-applicant.campaign'), fn (Builder $query, $value) => $filters->campaign($query, $value), 'campaign_id')
                ->options($filters->campaignOptions($user)),

            SelectFilter::make(__('recruitment-applicant.status'), fn (Builder $query, $value) => $filters->status($query, $value), 'status')
                ->options($filters->statusOptions()),

            SelectFilter::make(__('recruitment-applicant.filters.city'), fn (Builder $query, $value) => $filters->city($query, $value), 'city')
                ->options($filters->cityOptions($user)),

            DateFilter::make(__('recruitment-applicant.applied_at'), 'submitted_at'),

            SelectFilter::make(__('recruitment-applicant.source'), fn (Builder $query, $value) => $filters->source($query, $value), 'applicant_source')
                ->options($filters->sourceOptions($user)),


            SelectFilter::make(__('recruitment-applicant.cv'), fn (Builder $query, $value) => $filters->cv($query, $value), 'cv')
                ->options($filters->cvOptions()),

            SelectFilter::make(__('recruitment-applicant.previous_applicant'), fn (Builder $query, $value) => $filters->previousApplicant($query, $value), 'previous')
                ->options($filters->previousOptions()),
        ];
    }

    /**
     * "n of m met", with every indicator named — the count alone is never
     * shown without what it counts. A tooltip only reaches a mouse, so the
     * same list is also in the cell as text for a screen reader; the full
     * breakdown is on the profile the row links to.
     */
    private function indicatorSummary(RecruitmentApplication $application): string
    {
        $set = $this->indicatorSets[$application->getKey()] ?? null;

        if ($set === null) {
            return '';
        }

        $lines = collect($set->indicators)
            ->map(fn (MatchingIndicator $indicator): string => $indicator->label().': '.__('recruitment-applicant.indicators.outcomes.'.$indicator->outcome));

        $summary = $set->assessedCount() === 0
            ? __('recruitment-applicant.indicators.none_assessed')
            : __('recruitment-applicant.indicators.summary', ['met' => $set->metCount(), 'assessed' => $set->assessedCount()]);

        return '<span title="'.e($lines->implode("\n")).'" class="cursor-help rounded-control px-2 py-0.5 text-xs font-semibold '
            .RecruitmentIndicatorOutcome::classes(RecruitmentIndicatorOutcome::Unknown).'">'.e($summary).'</span>'
            .($lines->isEmpty() ? '' : '<span class="sr-only"> ('.e($lines->implode(__('recruitment-applicant.list_separator'))).')</span>');
    }
}
