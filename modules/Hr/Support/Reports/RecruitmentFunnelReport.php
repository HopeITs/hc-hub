<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Reports;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Enums\RecruitmentApplicationStatus;
use Modules\Hr\Enums\RecruitmentInterviewStatus;
use Modules\Hr\Enums\RecruitmentRequestStatus;
use Modules\Hr\Models\RecruitmentApplication;
use Modules\Hr\Models\RecruitmentHiringConversion;
use Modules\Hr\Models\RecruitmentInterview;
use Modules\Hr\Models\RecruitmentJobOffer;
use Modules\Hr\Models\RecruitmentRequest;
use Modules\Hr\Models\Vacancy;

/**
 * From request to hire. Counts only what the viewer's recruitment scope already
 * lets them see (RecruitmentScopeResolver), and only aggregates: no applicant
 * is named. A stage counts applications that have *evidence* of reaching it
 * (an interview row, an offer sent, a completed conversion), not their current
 * status, because a status says where an application is, not where it has been.
 */
class RecruitmentFunnelReport
{
    public const PERIODS = [90, 365, 0];

    public function __construct(public readonly User $viewer, public readonly int $days = 365) {}

    public static function normaliseDays(int $days): int
    {
        return in_array($days, self::PERIODS, true) ? $days : 365;
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $scope = app(RecruitmentScopeResolver::class);
        $days = self::normaliseDays($this->days);
        $from = $days > 0 ? now()->subDays($days)->startOfDay() : null;

        $requests = $scope->scopeRequests(RecruitmentRequest::query(), $this->viewer)
            ->whereNotNull('submitted_at')
            ->when($from, fn ($q) => $q->where('submitted_at', '>=', $from))
            ->get(['id', 'status', 'submitted_at', 'applied_at']);

        $applications = $scope->scopeApplications(RecruitmentApplication::query(), $this->viewer)
            ->when($from, fn ($q) => $q->where('submitted_at', '>=', $from))
            ->get(['id', 'vacancy_id', 'status', 'submitted_at']);

        $ids = $applications->pluck('id')->all();

        $interviews = $this->inChunks($ids, fn (array $chunk) => RecruitmentInterview::query()
            ->whereIn('application_id', $chunk)
            ->where('status', '!=', RecruitmentInterviewStatus::Cancelled)
            ->get(['application_id', 'scheduled_at']))->groupBy('application_id');

        $offers = $this->inChunks($ids, fn (array $chunk) => RecruitmentJobOffer::query()
            ->whereIn('application_id', $chunk)
            ->get(['id', 'application_id', 'status', 'sent_at', 'accepted_at', 'rejected_at']));

        $converted = $this->inChunks($offers->pluck('id')->all(), fn (array $chunk) => RecruitmentHiringConversion::query()
            ->whereIn('job_offer_id', $chunk)
            ->where('status', RecruitmentHiringConversion::STATUS_CONVERTED)
            ->get(['job_offer_id', 'converted_at']));

        $offerApplication = $offers->pluck('application_id', 'id');
        $hiredAt = $converted->mapWithKeys(fn ($c): array => [$offerApplication[$c->job_offer_id] => $c->converted_at]);
        $sentOffers = $offers->whereNotNull('sent_at');
        $firstSent = $sentOffers->groupBy('application_id')->map(fn (Collection $o) => $o->min('sent_at'));
        $accepted = $offers->whereNotNull('accepted_at')->pluck('application_id')->unique();

        $total = $applications->count();
        $counts = [
            'received' => $total,
            'interviewed' => $interviews->count(),
            'offer_sent' => $firstSent->count(),
            'offer_accepted' => $accepted->count(),
            'hired' => $hiredAt->count(),
        ];

        $previousCount = null;
        $stages = collect();

        foreach ($counts as $key => $count) {
            $stages->push([
                'key' => $key,
                'label' => __('hr_reports.funnel.stages.'.$key),
                'count' => $count,
                'percent' => $total ? round($count / $total * 100, 1) : 0.0,
                // Conversion from the previous stage, for the line under each bar.
                'fromPrevious' => $previousCount !== null && $previousCount > 0 ? round($count / $previousCount * 100, 1) : null,
            ]);
            $previousCount = $count;
        }

        $byId = $applications->keyBy('id');
        $requestStatus = fn (RecruitmentRequestStatus ...$statuses): int => $requests->whereIn('status', array_map(fn (RecruitmentRequestStatus $s): int => $s->value, $statuses))->count();

        return [
            'days' => $days,
            'requests' => [
                'submitted' => $requests->count(),
                'approved' => $requestStatus(RecruitmentRequestStatus::Approved, RecruitmentRequestStatus::Applied, RecruitmentRequestStatus::FailedApplication),
                'rejected' => $requestStatus(RecruitmentRequestStatus::Rejected),
                'cancelled' => $requestStatus(RecruitmentRequestStatus::Cancelled),
                'inReview' => $requestStatus(RecruitmentRequestStatus::Submitted, RecruitmentRequestStatus::UnderReview, RecruitmentRequestStatus::ChangesRequested, RecruitmentRequestStatus::Resubmitted),
                'daysToApply' => $this->averageDays($requests->whereNotNull('applied_at'), fn ($r): array => [$r->submitted_at, $r->applied_at]),
            ],
            'stages' => $stages,
            'times' => [
                'toInterview' => $this->averageDays($interviews, fn (Collection $rows, $appId): array => [$byId[$appId]->submitted_at ?? null, $rows->min('scheduled_at')]),
                'toOffer' => $this->averageDays($firstSent, fn ($sentAt, $appId): array => [$byId[$appId]->submitted_at ?? null, $sentAt]),
                'toHire' => $this->averageDays($hiredAt, fn ($at, $appId): array => [$byId[$appId]->submitted_at ?? null, $at]),
            ],
            'offerAcceptancePercent' => $firstSent->count() ? round($accepted->count() / $firstSent->count() * 100, 1) : null,
            'offersExpired' => $offers->where('status', 'expired')->count(),
            'byStatus' => $this->statuses($applications, $total),
            'byPosition' => $this->positions($applications, $interviews, $firstSent, $hiredAt),
        ];
    }

    /**
     * @param  array<int, int>  $ids
     * @param  Closure(array<int, int>): iterable<int, mixed>  $query
     * @return Collection<int, mixed>
     */
    private function inChunks(array $ids, Closure $query): Collection
    {
        return collect(array_chunk($ids, 1000))->flatMap(fn (array $chunk) => $query($chunk))->values();
    }

    /**
     * Mean days between two moments, over the rows where both exist; null when there are none.
     */
    private function averageDays(Collection $rows, Closure $pair): ?float
    {
        $days = $rows->map(function ($row, $key) use ($pair): ?float {
            [$from, $to] = $pair($row, $key);

            return $from && $to ? max(0, CarbonImmutable::parse($from)->floatDiffInDays(CarbonImmutable::parse($to))) : null;
        })->filter(fn (?float $d): bool => $d !== null);

        return $days->isEmpty() ? null : round($days->avg(), 1);
    }

    /**
     * @param  Collection<int, RecruitmentApplication>  $applications
     * @return Collection<int, array{label: string, count: int, percent: float}>
     */
    private function statuses(Collection $applications, int $total): Collection
    {
        $known = RecruitmentApplicationStatus::values();

        return $applications->groupBy('status')
            ->map(fn (Collection $g, string $status): array => [
                'label' => in_array($status, $known, true) ? __('hr_reports.funnel.application_status.'.$status) : $status,
                'count' => $g->count(),
                'percent' => $total ? round($g->count() / $total * 100, 1) : 0.0,
            ])->sortByDesc('count')->values();
    }

    /**
     * Top positions by applications: how far their applicants got.
     *
     * @param  Collection<int, RecruitmentApplication>  $applications
     * @return Collection<int, array{label: string, applications: int, interviewed: int, offers: int, hired: int}>
     */
    private function positions(Collection $applications, Collection $interviews, Collection $firstSent, Collection $hiredAt): Collection
    {
        $vacancies = Vacancy::query()->with('position')->whereIn('id', $applications->pluck('vacancy_id')->unique())->get()->keyBy('id');

        return $applications->groupBy('vacancy_id')
            ->map(function (Collection $apps, $vacancyId) use ($vacancies, $interviews, $firstSent, $hiredAt): array {
                $ids = $apps->pluck('id');

                return [
                    'label' => $vacancies[$vacancyId]?->position?->title ?: '-',
                    'applications' => $apps->count(),
                    'interviewed' => $ids->filter(fn ($id): bool => $interviews->has($id))->count(),
                    'offers' => $ids->filter(fn ($id): bool => $firstSent->has($id))->count(),
                    'hired' => $ids->filter(fn ($id): bool => $hiredAt->has($id))->count(),
                ];
            })
            ->groupBy('label')
            ->map(fn (Collection $rows, string $label): array => [
                'label' => $label,
                'applications' => $rows->sum('applications'),
                'interviewed' => $rows->sum('interviewed'),
                'offers' => $rows->sum('offers'),
                'hired' => $rows->sum('hired'),
            ])
            ->sortByDesc('applications')->take(15)->values();
    }
}
