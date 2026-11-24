<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\ReportTemplates;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\Program;
use Modules\Tracking\Enums\TemplatePlacementStatus;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ReportTemplate;
use Modules\Tracking\Models\ReportTemplatePlacement;
use Modules\Tracking\Support\Reporting\PlaceBridge;
use Modules\Tracking\Support\Reporting\ReportPlace;
use Modules\Tracking\Support\ReportTemplatePrefiller;

final class JoinReportTemplate
{
    public function __construct(private ReportTemplatePrefiller $prefiller, private PlaceBridge $places) {}

    /** @param list<int> $planIndicatorIds */
    public function handle(Proposal $proposal, ReportTemplate $template, array $planIndicatorIds, string $expectedStatus = 'pending'): ReportTemplate
    {
        Gate::authorize('report-templates.manage');

        return DB::transaction(function () use ($proposal, $template, $planIndicatorIds, $expectedStatus): ReportTemplate {
            Program::query()->whereKey($template->program_id)->lockForUpdate()->firstOrFail();
            $proposal = Proposal::query()->whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            $place = $template->place();
            $placement = ReportTemplatePlacement::query()->where('proposal_id', $proposal->id)->atPlace($place)->lockForUpdate()->firstOrFail();
            Gate::authorize('manage', $placement);
            $template = ReportTemplate::query()->whereKey($template->id)->lockForUpdate()->firstOrFail();

            if ($placement->status !== TemplatePlacementStatus::Pending || $expectedStatus !== 'pending') {
                throw ValidationException::withMessages(['placement' => __('report_templates.placement_stale')]);
            }
            if ($place->isUnset() || ! $place->equals($template->place()) || ! $proposal->status->isApprovedByDonor()
                || ! collect($this->places->placesOfProposal($proposal))->contains(fn (ReportPlace $candidate): bool => $place->equals($candidate))
                || ! $template->proposals()->whereKeyNot($proposal->id)->exists()) {
                throw ValidationException::withMessages(['placement' => __('report_templates.placement_wrong_place')]);
            }

            $planned = $this->prefiller->plannedLines($proposal, $place)['lines'];
            $eligible = PlanIndicator::query()->where('source_type', 'proposal')->where('source_id', $proposal->id)
                ->whereIn('id', array_keys($planned))->with('indicator')->get()->keyBy('id');
            $selected = array_values(array_unique(array_map(intval(...), $planIndicatorIds)));
            if (array_diff($selected, $eligible->modelKeys()) !== []) {
                throw ValidationException::withMessages(['selectedPlacementTargets' => __('report_templates.placement_invalid_target')]);
            }

            // Existing indicators always gain the new proposal's share.
            $libraries = $template->lines()->where('status', '!=', 'retired')
                ->join('plan_indicators as p', 'p.id', '=', 'report_template_lines.plan_indicator_id')
                ->join('indicators as i', 'i.id', '=', 'p.indicator_id')->pluck('i.library_indicator_id')->all();
            $shared = $eligible->filter(fn ($target): bool => in_array($target->indicator->library_indicator_id, $libraries, true))->modelKeys();
            $wanted = array_unique([...$selected, ...$shared]);
            if ($wanted === []) {
                throw ValidationException::withMessages(['selectedPlacementTargets' => __('report_templates.join_needs_one_line')]);
            }

            $template->proposals()->syncWithoutDetaching([$proposal->id => ['joined_at' => now(), 'joined_by' => Auth::id()]]);
            $this->prefiller->addPlacementLines($template, array_intersect_key($planned, array_flip($wanted)));
            $placement->forceFill(['status' => TemplatePlacementStatus::Joined, 'report_template_id' => $template->id, 'decided_at' => now(), 'decided_by' => Auth::id()])->save();

            return $template;
        });
    }
}
