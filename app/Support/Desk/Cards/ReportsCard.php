<?php

declare(strict_types=1);

namespace App\Support\Desk\Cards;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Indicator readings (reports) in card form: what is late, what waits for a
 * review, and the newest readings with the indicator and project they are for.
 */
final class ReportsCard extends PageCard
{
    public function route(): string
    {
        return 'reports.index';
    }

    public function figures(User $user): array
    {
        return [
            $this->figure(__('desk-workspace.card.figure.late'), Report::query()->overdue()->count(), 'critical'),
            $this->figure(ReportStatus::Draft->label(), Report::query()->where('status', ReportStatus::Draft)->count(), 'warn'),
            $this->figure(ReportStatus::Approved->label(), Report::query()->approved()->count(), 'good'),
        ];
    }

    public function breakdown(User $user): array
    {
        $counts = Report::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return $this->parts(array_map(fn (ReportStatus $status): array => [
            'label' => (string) $status->label(),
            'count' => (int) ($counts[$status->value] ?? 0),
            'tone' => $status->tone()->value,
        ], ReportStatus::cases()));
    }

    protected function records(User $user, string $search): Collection
    {
        return Report::query()->with(['planIndicator.indicator:id,name', 'planIndicator.project:id,name'])
            ->latest('planned_date')->latest('id')->limit(self::RowLimit)->get()
            ->map(fn (Report $report): array => $this->row(
                'rp-'.$report->id,
                (string) ($report->planIndicator?->indicator?->name ?? '—'),
                collect([$report->planIndicator?->project?->name, $report->planned_date->translatedFormat(__('formats.date'))])->filter()->implode(' · '),
                $this->badgeOf($report->status),
                route('reports.show', $report),
            ));
    }
}
