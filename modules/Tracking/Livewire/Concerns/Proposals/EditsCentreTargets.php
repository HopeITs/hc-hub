<?php

declare(strict_types=1);

namespace Modules\Tracking\Livewire\Concerns\Proposals;

use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Modules\Tracking\Actions\Targets\SaveProposalTargetsGrid;
use Modules\Tracking\Enums\IndicatorMeasure;
use Modules\Tracking\Enums\Period;
use Modules\Tracking\Models\Indicator;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalLocation;
use Modules\Tracking\Models\Result;

/**
 * The framework box's "Centre targets" dialog (proposal page redesign): one
 * number per location row (city and program, Y-10: a target belongs to its
 * row, never to a project), under one period for the whole dialog. A row
 * still waiting for its program is left out: it takes no target yet. Saved through SaveProposalTargetsGrid, the one routine that
 * writes proposal targets, so its rules (targets.update / targets.delete,
 * percent 0–100, a period with every number, stale versions) all hold.
 * A cleared number removes that row's target. Used by components/proposals/⚡logframe.
 *
 * @property-read Proposal $proposal  #[Computed] proposal()
 * @property-read array{id: int, name: string, unit: string|null, target: float|null, percent: bool, linked: bool}|null $centreTargetIndicator  #[Computed] centreTargetIndicator()
 * @property-read list<array{locationId: int, label: string}> $centreTargetRows  #[Computed] centreTargetRows()
 */
trait EditsCentreTargets
{
    public bool $showCentreTargetsModal = false;

    public ?int $centreTargetsIndicatorId = null;

    public string $centreTargetsPeriod = '';

    /** @var array<int|string, string> location row id => number */
    public array $centreTargets = [];

    /** @var array<int|string, string> location row id => saved version */
    public array $centreTargetVersions = [];

    /** @var array<int|string, string|null> location row id => saved target population, kept as it is */
    public array $centreTargetPopulations = [];

    /** MEAL enters targets (D-23); a closed proposal is read-only. */
    #[Computed]
    public function canEditCentreTargets(): bool
    {
        return ! $this->proposal->isPayroll() && ! $this->proposal->status->isTerminal() && Gate::allows('targets.update');
    }

    public function openCentreTargets(int $indicatorId): void
    {
        Gate::authorize('targets.update');

        $indicator = $this->frameworkIndicator($indicatorId);

        $saved = PlanIndicator::query()
            ->select(['id', 'indicator_id', 'proposal_location_id', 'target', 'target_population', 'period', 'updated_at'])
            ->where('source_type', SaveProposalTargetsGrid::SourceType)
            ->where('source_id', $this->proposalId)
            ->where('indicator_id', $indicator->id)
            ->get()
            ->keyBy('proposal_location_id');

        $this->resetValidation();
        $this->centreTargetsIndicatorId = $indicator->id;
        $this->centreTargets = [];
        $this->centreTargetVersions = [];
        $this->centreTargetPopulations = [];

        foreach ($this->centreTargetRows as $row) {
            $target = $saved->get($row['locationId']);
            $this->centreTargets[$row['locationId']] = $target === null ? '' : self::plainNumber((float) $target->target);

            if ($target !== null) {
                $this->centreTargetVersions[$row['locationId']] = SaveProposalTargetsGrid::versionOf($target);
                $this->centreTargetPopulations[$row['locationId']] = $target->target_population === null ? null : (string) $target->target_population;
            }
        }

        $this->centreTargetsPeriod = $saved->first()?->period?->value ?? $indicator->frequency?->value ?? Period::Monthly->value;
        $this->showCentreTargetsModal = true;
        unset($this->centreTargetIndicator);
    }

    public function closeCentreTargets(): void
    {
        $this->showCentreTargetsModal = false;
        $this->centreTargetsIndicatorId = null;
        $this->resetValidation();
    }

    public function saveCentreTargets(): void
    {
        Gate::authorize('targets.update');

        $indicator = $this->frameworkIndicator((int) $this->centreTargetsIndicatorId);
        $cells = [];
        $rowKeys = [];

        foreach ($this->centreTargetRows as $row) {
            $value = trim((string) ($this->centreTargets[$row['locationId']] ?? ''));
            $version = $this->centreTargetVersions[$row['locationId']] ?? null;

            if ($value === '' && $version === null) {
                continue;
            }

            $cells[] = [
                'indicator_id' => $indicator->id,
                'location_id' => $row['locationId'],
                'value' => $value === '' ? null : $value,
                'period' => $value === '' ? null : $this->centreTargetsPeriod,
                'target_population' => $this->centreTargetPopulations[$row['locationId']] ?? null,
                'version' => $version,
            ];
            $rowKeys[] = $row['locationId'];
        }

        try {
            $counts = app(SaveProposalTargetsGrid::class)->handle($this->proposal, $cells);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(collect($exception->errors())->mapWithKeys(function (array $messages, string $key) use ($rowKeys): array {
                if (preg_match('/^cells\.(\d+)(?:\.(value|period))?$/', $key, $match) === 1) {
                    return [($match[2] ?? '') === 'period' ? 'centreTargetsPeriod' : 'centreTargets.'.($rowKeys[(int) $match[1]] ?? $match[1]) => $messages];
                }

                return ['centreTargets' => $messages];
            })->all());
        }

        $this->closeCentreTargets();
        unset($this->framework);

        Flux::toast(variant: 'success', text: array_sum($counts) === 0 ? __('targets.grid_unchanged') : __('proposals.framework.centre_targets_saved'));
    }

    /**
     * @return array{id: int, name: string, unit: string|null, target: float|null, percent: bool, linked: bool}|null
     */
    #[Computed]
    public function centreTargetIndicator(): ?array
    {
        if ($this->centreTargetsIndicatorId === null) {
            return null;
        }

        $indicator = Indicator::query()->select(['id', 'name', 'unit', 'target', 'measure', 'library_indicator_id'])->find($this->centreTargetsIndicatorId);

        return $indicator === null ? null : [
            'id' => $indicator->id,
            'name' => $indicator->name,
            'unit' => $indicator->unit,
            'target' => $indicator->target === null ? null : (float) $indicator->target,
            'percent' => $indicator->measure === IndicatorMeasure::Percentage,
            // SaveProposalTargetsGrid refuses a target on an indicator not linked to the library.
            'linked' => $indicator->library_indicator_id !== null,
        ];
    }

    /**
     * One row per location row of the proposal, named "city · program".
     *
     * @return list<array{locationId: int, label: string}>
     */
    #[Computed]
    public function centreTargetRows(): array
    {
        $rows = $this->proposal->locations()->with('program')->orderBy('sort')->orderBy('id')->get();
        $labels = ProposalLocation::placeLabels($rows);

        return $rows
            ->reject(fn (ProposalLocation $row): bool => SaveProposalTargetsGrid::isWaitingForProgram($row))
            ->map(fn (ProposalLocation $row): array => ['locationId' => (int) $row->id, 'label' => $labels[$row->id]])
            ->values()
            ->all();
    }

    /**
     * An indicator of this proposal's own framework, or a 404.
     */
    private function frameworkIndicator(int $indicatorId): Indicator
    {
        return Indicator::query()
            ->select(['id', 'name', 'frequency', 'result_id'])
            ->whereKey($indicatorId)
            ->whereIn('result_id', Result::query()->proposalFramework($this->proposalId)->select('id'))
            ->firstOrFail();
    }

    private static function plainNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }
}
