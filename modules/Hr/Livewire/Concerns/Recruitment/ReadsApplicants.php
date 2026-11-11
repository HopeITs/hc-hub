<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Recruitment;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Computed;
use Modules\Hr\Actions\Recruitment\RecruitmentApplicationFilters;
use Modules\Hr\Actions\Recruitment\RecruitmentInterviewSchedulingService;
use Modules\Hr\Actions\Recruitment\RecruitmentMatchingIndicators;
use Modules\Hr\Enums\RecruitmentApplicationStatus;
use Modules\Hr\Models\RecruitmentApplicant;
use Modules\Hr\Models\RecruitmentApplication;
use Modules\Hr\Support\Recruitment\MatchingIndicatorSet;

/**
 * The applicants list: one row per applicant. Filters narrow the matching
 * applications first, then the newest matching application stands for each
 * applicant. Every row and every option comes through profileVisibleTo()
 * and RecruitmentApplicationFilters, which only narrows: an id typed into
 * the query string can shrink a scoped result, never widen it.
 *
 * The indicator summary is computed for the rows on the page only and is not
 * sortable on purpose: a list ranked by it would be a decision made by the
 * tool.
 */
trait ReadsApplicants
{
    /** The filters in the shape RecruitmentApplicationFilters::query() and the export read. @return array<string, mixed> */
    public function filterValues(): array
    {
        return array_filter([
            'campaign' => $this->campaign,
            'status' => $this->status,
            'city' => $this->city,
            'tag' => $this->tag,
            'flag_type' => $this->flagType,
            'flag_category' => $this->flagCategory,
            'source' => $this->source,
            'cv' => $this->cv,
            'previous' => $this->previous,
            'endorsement_group' => $this->endorsement,
            'evaluation' => $this->mayViewEvaluations() ? $this->evaluation : '',
            'submitted_from' => $this->from,
            'submitted_to' => $this->to,
            'search' => trim($this->search),
        ], fn ($value): bool => $value !== '' && $value !== null);
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $filters = app(RecruitmentApplicationFilters::class);
        $matching = $this->matching();

        $representatives = (clone $matching)
            ->reorder()
            ->select([])
            ->selectRaw('MAX(recruitment_applications.id)')
            ->groupBy('recruitment_applications.applicant_id');

        $paginator = RecruitmentApplication::query()
            ->select([
                'recruitment_applications.id', 'recruitment_applications.applicant_id', 'recruitment_applications.campaign_id',
                'recruitment_applications.vacancy_id', 'recruitment_applications.form_version_id', 'recruitment_applications.status',
                'recruitment_applications.submitted_at', 'recruitment_applications.email_normalized',
            ])
            ->addSelect(['has_previous_application' => $filters->earlierApplication(RecruitmentApplication::query())])
            ->whereIn('recruitment_applications.id', $representatives)
            ->with([
                'applicant:id,full_name,email_normalized,source,city_id,employee_id,anonymized_at',
                'campaign:id,vacancy_id,status,starts_at,ends_at',
                'campaign.vacancy:id,position_id,created_from_request_id',
                'campaign.vacancy.position:id,title_en,title_ar',
                'cvFiles',
                'endorsements:id,application_id,voter_role',
                'tags',
                'flags:id,application_id,type,category,reason',
            ])
            ->when($this->mayViewEvaluations(), fn (Builder $query): Builder => $query->with([
                'interviews:id,application_id',
                'interviews.evaluations' => fn (HasMany $evaluations) => $evaluations->active(),
            ]))
            ->orderBy(match ($this->sort) {
                'name' => RecruitmentApplicant::query()->select('full_name')->whereColumn('recruitment_applicants.id', 'recruitment_applications.applicant_id'),
                'status' => 'recruitment_applications.status',
                default => 'recruitment_applications.submitted_at',
            }, $this->direction === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('recruitment_applications.id')
            ->paginate(25);

        return $paginator;
    }

    /** @return array<int, MatchingIndicatorSet> for the rows on this page */
    #[Computed]
    public function indicatorSets(): array
    {
        return app(RecruitmentMatchingIndicators::class)->evaluateMany(new Collection($this->rows->items()));
    }

    /**
     * The rows on this page whose interview can be scheduled by the viewer
     * (the interview policy's schedule rule, answered in one query).
     *
     * @return array<int, true>
     */
    #[Computed]
    public function schedulable(): array
    {
        if (! $this->may('recruitment-applicants.schedule-interviews') || ! $this->may('recruitment-applicants.view')) {
            return [];
        }

        return RecruitmentApplication::query()->visibleTo($this->viewer())
            ->whereIn('recruitment_applications.id', array_map(fn ($row) => $row->getKey(), $this->rows->items()))
            ->whereIn('recruitment_applications.status', RecruitmentInterviewSchedulingService::SCHEDULABLE)
            ->pluck('recruitment_applications.id')
            ->mapWithKeys(fn ($id): array => [(int) $id => true])
            ->all();
    }

    /** @return array{all: int, statuses: array<string, int>} distinct applicants per stage, within the campaign lens */
    #[Computed]
    public function stageCounts(): array
    {
        $query = RecruitmentApplication::query()->profileVisibleTo($this->viewer());
        app(RecruitmentApplicationFilters::class)->campaign($query, $this->campaign);

        return [
            'all' => (clone $query)->distinct()->count('applicant_id'),
            'statuses' => $query->selectRaw('status, count(distinct applicant_id) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn ($total): int => (int) $total)
                ->all(),
        ];
    }

    /**
     * The campaign lenses: votes, evaluations and finalists only mean
     * something inside one campaign.
     *
     * @return array{endorsements: array<string, int>, evaluations: ?array<string, int>, finalists: int}|null
     */
    #[Computed]
    public function campaignLens(): ?array
    {
        if (! ctype_digit($this->campaign)) {
            return null;
        }

        $filters = app(RecruitmentApplicationFilters::class);
        $base = fn (): Builder => RecruitmentApplication::query()->profileVisibleTo($this->viewer())->where('recruitment_applications.campaign_id', (int) $this->campaign);

        $endorsements = [];
        foreach (['preferred', 'alternative', 'unvoted'] as $group) {
            $endorsements[$group] = $filters->endorsementGroup($base(), $group)->count();
        }

        $evaluations = null;
        if ($this->mayViewEvaluations()) {
            $evaluations = [];
            foreach (array_keys($filters->evaluationRecommendationOptions()) as $recommendation) {
                $evaluations[$recommendation] = $filters->evaluationRecommendation($base(), $recommendation)->count();
            }
        }

        return [
            'endorsements' => $endorsements,
            'evaluations' => $evaluations,
            'finalists' => $base()->where('recruitment_applications.status', RecruitmentApplicationStatus::Finalist)->count(),
        ];
    }

    /** @return array<string, array<int|string, string>> every filter's options, read once per render */
    #[Computed]
    public function options(): array
    {
        $filters = app(RecruitmentApplicationFilters::class);
        $user = $this->viewer();

        return [
            'campaigns' => $filters->campaignOptions($user),
            'cities' => $filters->cityOptions($user),
            'statuses' => $filters->statusOptions(),
            'sources' => $filters->sourceOptions($user),
            'tags' => $filters->tagOptions(),
            'flagTypes' => $filters->flagTypeOptions(),
            'flagCategories' => $filters->flagCategoryOptions(),
            'cv' => $filters->cvOptions(),
            'previous' => $filters->previousOptions(),
            'endorsements' => $filters->endorsementGroupOptions(),
            'evaluations' => $this->mayViewEvaluations() ? $filters->evaluationRecommendationOptions() : [],
        ];
    }

    protected function mayViewEvaluations(): bool
    {
        return $this->may('recruitment-applicants.view-evaluations');
    }

    /** The matching applications, before they collapse to one per applicant. */
    protected function matching(): Builder
    {
        $filters = app(RecruitmentApplicationFilters::class);
        $query = RecruitmentApplication::query()->profileVisibleTo($this->viewer());
        $values = $this->filterValues();

        $filters->campaign($query, $values['campaign'] ?? null);
        $filters->status($query, $values['status'] ?? null);
        $filters->city($query, $values['city'] ?? null);
        $filters->tag($query, $values['tag'] ?? null);
        $filters->flagType($query, $values['flag_type'] ?? null);
        $filters->flagCategory($query, $values['flag_category'] ?? null);
        $filters->source($query, $values['source'] ?? null);
        $filters->cv($query, $values['cv'] ?? null);
        $filters->previousApplicant($query, $values['previous'] ?? null);
        $filters->endorsementGroup($query, $values['endorsement_group'] ?? null);
        $filters->evaluationRecommendation($query, $values['evaluation'] ?? null);
        $filters->submittedBetween($query, $values['submitted_from'] ?? null, $values['submitted_to'] ?? null);
        $filters->search($query, $values['search'] ?? null);

        return $query;
    }

    /**
     * Every matching row's application id (the whole filtered table), bounded
     * by the bulk limit; ids only, nothing hydrated.
     *
     * @return list<string>
     */
    protected function matchingIds(int $limit): array
    {
        $matching = $this->matching();

        return RecruitmentApplication::query()
            ->whereIn('recruitment_applications.id', (clone $matching)->reorder()->select([])->selectRaw('MAX(recruitment_applications.id)')->groupBy('recruitment_applications.applicant_id'))
            ->orderByDesc('recruitment_applications.id')
            ->limit($limit)
            ->pluck('recruitment_applications.id')
            ->map(fn ($id): string => (string) $id)
            ->all();
    }
}
