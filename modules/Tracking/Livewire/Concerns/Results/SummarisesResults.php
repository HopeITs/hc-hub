<?php

declare(strict_types=1);

namespace Modules\Tracking\Livewire\Concerns\Results;

use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Modules\Tracking\Enums\ResultType;
use Modules\Tracking\Models\Result;

/**
 * Headline figures for the visible framework and the attention-chip counts.
 *
 * Used by results/index only. Reads the tree BuildsResultsTree builds, the
 * measurements BuildsResultsFramework loads, and filters through
 * BuildsResultsTree::matchesFilters().
 *
 * @property-read Collection<int, Result> $allResults
 * @property-read array<int, array<string, mixed>> $measurements
 * @property-read list<array{node: array<string, mixed>, program: string}> $visibleNodes
 */
trait SummarisesResults
{
    /**
     * Headline figures for the visible framework.
     *
     * @return array{
     *     statements: int,
     *     measured: int,
     *     indicators: int,
     *     targets: int,
     *     reports: int,
     *     approved: int,
     *     funders: list<array{label: string, type: string}>,
     *     coverage: float|null,
     *     achieved: float|null,
     * }
     */
    #[Computed]
    public function overview(): array
    {
        $statements = 0;
        $measured = 0;
        $indicators = 0;
        $targets = 0;
        $reports = 0;
        $approved = 0;
        $funders = [];
        $achievements = [];

        foreach ($this->visibleNodes as $row) {
            $result = $row['node']['result'];
            $measurement = $row['node']['measurement'];
            $ownIndicators = count($measurement['rows']);

            $statements++;
            $indicators += $ownIndicators;
            $targets += $measurement['targets'];
            $reports += $measurement['reports'];
            $approved += $measurement['approved'];

            foreach ($measurement['funders'] as $funder) {
                $funders[$funder['label']] = $funder;
            }

            if ($ownIndicators > 0) {
                $measured++;
            }

            if ($measurement['achieved'] !== null) {
                $achievements[] = $measurement['achieved'];
            }
        }

        return [
            'statements' => $statements,
            'measured' => $measured,
            'indicators' => $indicators,
            'targets' => $targets,
            'reports' => $reports,
            'approved' => $approved,
            'funders' => array_values($funders),
            'coverage' => $statements === 0 ? null : ($measured / $statements) * 100,
            'achieved' => $achievements === [] ? null : array_sum($achievements) / count($achievements),
        ];
    }

    /**
     * What each attention chip would select, counted under the OTHER filters
     * that are active.
     *
     * Counting across the whole table instead would have a chip read "2" while
     * the program filter leaves only one of them reachable — the number and
     * the page would disagree the moment anything else was narrowed.
     *
     * @return array{unmeasured: int, offtrack: int, waiting: int}
     */
    #[Computed]
    public function focusCounts(): array
    {
        $counts = ['unmeasured' => 0, 'offtrack' => 0, 'waiting' => 0];

        foreach ($this->allResults as $result) {
            // Every filter but the focus itself, so each chip counts what it would select.
            if (! $this->matchesFilters($result, '')) {
                continue;
            }

            $measurement = $this->measurements[$result->id];

            if ($measurement['rows'] === []) {
                $counts['unmeasured']++;
            }

            if ($measurement['status'] === 'behind') {
                $counts['offtrack']++;
            }

            if ($measurement['status'] === 'waiting') {
                $counts['waiting']++;
            }
        }

        return $counts;
    }

    /**
     * The visible statements as a grid: one row per program (or proposal
     * framework), one column per level, each cell reduced to its worst status.
     *
     * Worst first: behind, then waiting, then needing an indicator, then on
     * track. Rows that most need a hand come first, then by name.
     *
     * @return array{
     *     levels: list<array{value: string, label: string}>,
     *     rows: list<array{label: string, needs: int, cells: array<string, array{total: int, needs: int, worst: string}>}>,
     *     totals: array<string, int>,
     * }
     */
    #[Computed]
    public function statusGrid(): array
    {
        $severity = ['behind' => 0, 'waiting' => 1, 'none' => 2, 'notarget' => 3, 'ontrack' => 4];
        $totals = array_fill_keys(['ontrack', 'waiting', 'behind', 'none', 'notarget'], 0);
        $rows = [];
        $present = [];

        foreach ($this->visibleNodes as $row) {
            $status = $row['node']['measurement']['status'];
            $level = $row['node']['result']->type->value;
            $cell = $rows[$row['program']]['cells'][$level] ?? ['total' => 0, 'needs' => 0, 'worst' => 'ontrack'];

            $cell['total']++;
            $cell['needs'] += in_array($status, ['behind', 'waiting', 'none'], true) ? 1 : 0;
            $cell['worst'] = $severity[$status] < $severity[$cell['worst']] ? $status : $cell['worst'];

            $rows[$row['program']]['cells'][$level] = $cell;
            $present[$level] = true;
            $totals[$status]++;
        }

        $rows = collect($rows)
            ->map(fn (array $group, string $label): array => [
                'label' => $label,
                'needs' => array_sum(array_column($group['cells'], 'needs')),
                'cells' => $group['cells'],
            ])
            ->sort(fn (array $left, array $right): int => [$right['needs'], $left['label']] <=> [$left['needs'], $right['label']])
            ->values()
            ->all();

        return [
            'levels' => collect(ResultType::cases())
                ->filter(fn (ResultType $case): bool => isset($present[$case->value]))
                ->map(fn (ResultType $case): array => ['value' => $case->value, 'label' => $case->label()])
                ->values()
                ->all(),
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
}
