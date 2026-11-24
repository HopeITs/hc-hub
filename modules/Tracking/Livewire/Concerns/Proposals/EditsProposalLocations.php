<?php

declare(strict_types=1);

namespace Modules\Tracking\Livewire\Concerns\Proposals;

use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Tracking\Actions\Targets\ClearLocationTargets;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalLocation;
use Modules\Tracking\Support\TargetCounts;

/**
 * The location rows of the proposal form (W-34a slice 3): where a proposal
 * runs, as city, program (or a new program), responsible and one or more
 * centers, or "field work, no center" (`city_wide`, D-88), which clears and
 * disables the row's centers. They replace the project picker and go to SaveProposal as
 * `locations`, which validates and writes them; a new program or center a
 * row names is created there (CreateLocationPlaces), for someone who may.
 *
 * A row that already stands for a linked project shows its city and program
 * locked (the link owns them, ProposalLocationRows matches rows by both and
 * never deletes a linked row); only its responsible and centers change here,
 * also on an approved proposal, which is how centers are given to the
 * proposals entered before the rows existed.
 *
 * Used by ManagesProposalForm, which provides `editingProposalId`.
 *
 * @property ?int $editingProposalId
 * @property-read bool $removingLocationDeletesTargets
 * @property-read string $locationTargetsUrl
 */
trait EditsProposalLocations
{
    /** The program select's value for "a program that does not exist yet". */
    public const NewProgramValue = 'new';

    /**
     * The rows as the form edits them. `key` only keeps wire:key stable,
     * `project` names the linked project of a row that has one and `is_new`
     * carries the stored flag of a kept row; `key` and `project` are not sent.
     *
     * @var list<array{key: string, city_id: string, program_id: string, responsible_employee_id: string, center_ids: list<string>, city_wide: bool, new_program_name_en: string, new_program_name_ar: string, new_center_name_en: string, new_center_name_ar: string, is_new: bool, project: string|null, location_id: int|null}>
     */
    public array $locations = [];

    /**
     * Whether the edited proposal had rows when the form opened. An edit of a
     * proposal without rows sends none until the user adds one, so saving its
     * other details is never refused for the missing place.
     */
    #[Locked]
    public bool $hadLocationRows = false;

    /**
     * Y-10: removing a saved row that carries targets asks first. The dialog
     * names how many, offers to delete them (a holder of `targets.delete`,
     * none reported) or to open them, and the row stays until then.
     */
    public bool $showLocationTargetsModal = false;

    #[Locked]
    public ?int $removingLocationIndex = null;

    /**
     * @var array{count: int, reported: int}
     */
    #[Locked]
    public array $removingLocationTargets = ['count' => 0, 'reported' => 0];

    /**
     * Active cities for the row's city select: id => name.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function locationCityOptions(): array
    {
        return City::options();
    }

    /**
     * Programs for the row's program select.
     *
     * @return Collection<int, Program>
     */
    #[Computed]
    public function locationProgramOptions(): Collection
    {
        return Program::options();
    }

    /**
     * Active centers by city id, so each row offers the centers of its own
     * city only. A center since deactivated stays on a row that holds it, so
     * the row reads true.
     *
     * @return array<int, list<array{id: int, name: string}>>
     */
    #[Computed]
    public function locationCentersByCity(): array
    {
        $byCity = [];

        foreach (Center::activeModels() as $center) {
            $byCity[(int) $center->city_id][] = ['id' => (int) $center->id, 'name' => $center->name];
        }

        foreach ($this->locations as $row) {
            foreach ($row['center_ids'] as $centerId) {
                $held = Center::remembered((int) $centerId);

                // Filed under the center's own city, so a row that moves city never keeps it.
                if ($held === null || in_array((int) $centerId, array_column($byCity[(int) $held->city_id] ?? [], 'id'), true)) {
                    continue;
                }

                // Only a closed center lands here (options() lists active ones): say so (D-66).
                $byCity[(int) $held->city_id][] = ['id' => (int) $centerId, 'name' => $held->is_active ? $held->name : $held->name.' · '.__('projects.center_inactive_badge')];
            }
        }

        return $byCity;
    }

    /**
     * Whether the acting user may open a new program from a row.
     */
    #[Computed]
    public function locationCanCreateProgram(): bool
    {
        return (bool) Auth::user()?->can('programs.create');
    }

    /**
     * Whether the acting user may add a new center from a row.
     */
    #[Computed]
    public function locationCanCreateCenter(): bool
    {
        return (bool) Auth::user()?->can('centers.create');
    }

    public function addLocationRow(): void
    {
        $this->locations[] = $this->blankLocationRow();
    }

    /**
     * Remove a row. A row that stands for a linked project stays: only
     * unlinking the project on the Projects tab removes it.
     */
    public function removeLocationRow(int $index): void
    {
        if (! isset($this->locations[$index]) || $this->locations[$index]['project'] !== null) {
            return;
        }

        $row = $this->savedLocationRow($index);
        $counts = $row === null ? ['count' => 0, 'reported' => 0] : TargetCounts::ofLocation($row);

        if ($counts['count'] > 0) {
            $this->removingLocationIndex = $index;
            $this->removingLocationTargets = $counts;
            unset($this->removingLocationDeletesTargets);
            $this->showLocationTargetsModal = true;

            return;
        }

        $this->dropLocationRow($index);
    }

    /**
     * Whether the dialog offers to delete the row's targets: none reported,
     * and the user may delete targets (D-23).
     */
    #[Computed]
    public function removingLocationDeletesTargets(): bool
    {
        return $this->removingLocationTargets['count'] > 0 && $this->removingLocationTargets['reported'] === 0 && Gate::allows('targets.delete');
    }

    /**
     * "Delete the targets and remove the location": the targets go now
     * (ClearLocationTargets), the row leaves the form and is removed when
     * the proposal is saved.
     */
    public function clearRemovingLocationTargets(): void
    {
        Gate::authorize('targets.delete');

        $index = $this->removingLocationIndex;
        $row = $index === null ? null : $this->savedLocationRow($index);

        if ($row === null || $this->editingProposalId === null) {
            $this->closeLocationTargetsModal();

            return;
        }

        $deleted = app(ClearLocationTargets::class)->handle(Proposal::query()->findOrFail($this->editingProposalId), $row->id);

        $this->closeLocationTargetsModal();
        $this->dropLocationRow($index);

        Flux::toast(variant: 'success', text: trans_choice('proposals.location_targets_cleared', $deleted, ['count' => $deleted]));
    }

    /**
     * The edited proposal's targets tab, which the dialog opens in a new tab.
     */
    #[Computed]
    public function locationTargetsUrl(): string
    {
        $proposal = Proposal::query()->findOrFail($this->editingProposalId);

        return route('proposals.show', ['proposal' => $proposal, 'tab' => 'targets']);
    }

    public function closeLocationTargetsModal(): void
    {
        $this->showLocationTargetsModal = false;
        $this->removingLocationIndex = null;
        $this->removingLocationTargets = ['count' => 0, 'reported' => 0];
        unset($this->removingLocationDeletesTargets);
    }

    /**
     * The saved row behind a form row, loaded through the edited proposal
     * (BOLA): a new row, or an id that is not this proposal's, gives null.
     */
    private function savedLocationRow(int $index): ?ProposalLocation
    {
        $locationId = $this->locations[$index]['location_id'] ?? null;

        if ($locationId === null || $locationId === '' || $this->editingProposalId === null) {
            return null;
        }

        return ProposalLocation::query()
            ->where('proposal_id', $this->editingProposalId)
            ->whereKey((int) $locationId)
            ->first();
    }

    /**
     * Take a row off the form; it is removed from the proposal on save.
     */
    private function dropLocationRow(int $index): void
    {
        $this->locations = array_values(array_filter($this->locations, fn (int $key): bool => $key !== $index, ARRAY_FILTER_USE_KEY));
        $this->resetValidation();
    }

    /**
     * Keep each row coherent as it is edited: a new city drops the centers of
     * the old one, field work drops the centers and the typed new center, a
     * chosen program proposes its manager as the responsible when none is
     * set, and a program from the list clears the typed names.
     */
    public function updatedLocations(mixed $value, string $path): void
    {
        [$index, $field] = array_pad(explode('.', $path, 2), 2, null);

        if (! ctype_digit((string) $index) || ! isset($this->locations[(int) $index])) {
            return;
        }

        $index = (int) $index;
        $row = $this->locations[$index];

        if ($field === 'city_id') {
            unset($this->locationCentersByCity);
            $allowed = array_map('strval', array_column($this->locationCentersByCity()[(int) $row['city_id']] ?? [], 'id'));
            $row['center_ids'] = array_values(array_intersect($row['center_ids'], $allowed));
        }

        if ($field === 'city_wide') {
            $row['city_wide'] = (bool) $row['city_wide'];

            if ($row['city_wide']) {
                $row['center_ids'] = [];
                $row['new_center_name_en'] = '';
                $row['new_center_name_ar'] = '';
            }
        }

        if ($field === 'program_id' && $row['program_id'] !== self::NewProgramValue) {
            $row['new_program_name_en'] = '';
            $row['new_program_name_ar'] = '';

            $managerId = ctype_digit($row['program_id'])
                ? Program::query()->whereKey((int) $row['program_id'])->value('manager_id')
                : null;

            if ($row['responsible_employee_id'] === '' && is_numeric($managerId)) {
                $row['responsible_employee_id'] = (string) $managerId;
            }
        }

        $this->locations = array_values(array_replace($this->locations, [$index => $row]));
    }

    /**
     * Fill the rows from the edited proposal.
     */
    protected function loadLocationRows(int $proposalId): void
    {
        $rows = ProposalLocation::query()
            ->where('proposal_id', $proposalId)
            ->with(['centers', 'project:id,name'])
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $this->locations = array_values($rows->map(fn (ProposalLocation $row): array => [
            'key' => Str::random(8),
            'city_id' => (string) $row->city_id,
            'program_id' => $row->program_id !== null ? (string) $row->program_id : '',
            'responsible_employee_id' => $row->responsible_employee_id !== null ? (string) $row->responsible_employee_id : '',
            'center_ids' => array_map('strval', $row->centerIds()),
            'city_wide' => $row->city_wide,
            'new_program_name_en' => '',
            'new_program_name_ar' => '',
            'new_center_name_en' => '',
            'new_center_name_ar' => '',
            'is_new' => $row->is_new,
            'project' => $row->project?->name,
            'location_id' => $row->id,
        ])->all());

        $this->hadLocationRows = $this->locations !== [];
    }

    protected function resetLocationRows(): void
    {
        $this->locations = [];
        $this->hadLocationRows = false;
        $this->closeLocationTargetsModal();
    }

    /**
     * The rows as SaveProposal's `locations`. A row left completely empty
     * (the one a create starts with, say) is not a place and is not sent.
     *
     * @return list<array{city_id: string, program_id: string|null, responsible_employee_id: string|null, center_ids: list<string>, city_wide: bool, is_new: bool, new_program_name_en: string, new_program_name_ar: string, new_center_name_en: string, new_center_name_ar: string}>
     */
    protected function locationPayload(): array
    {
        return array_values(array_map(fn (array $row): array => [
            'city_id' => $row['city_id'],
            'program_id' => $row['program_id'] === '' || $row['program_id'] === self::NewProgramValue ? null : $row['program_id'],
            'responsible_employee_id' => $row['responsible_employee_id'] === '' ? null : $row['responsible_employee_id'],
            'center_ids' => $row['city_wide'] ? [] : $row['center_ids'],
            'city_wide' => $row['city_wide'],
            'is_new' => $row['program_id'] === self::NewProgramValue || $row['is_new'],
            'new_program_name_en' => $row['program_id'] === self::NewProgramValue ? $row['new_program_name_en'] : '',
            'new_program_name_ar' => $row['program_id'] === self::NewProgramValue ? $row['new_program_name_ar'] : '',
            'new_center_name_en' => $row['city_wide'] ? '' : $row['new_center_name_en'],
            'new_center_name_ar' => $row['city_wide'] ? '' : $row['new_center_name_ar'],
        ], array_filter($this->locations, fn (array $row): bool => ! $this->isBlankLocationRow($row))));
    }

    /**
     * Whether any rows other than empty ones are on the form.
     */
    protected function hasLocationRows(): bool
    {
        return array_filter($this->locations, fn (array $row): bool => ! $this->isBlankLocationRow($row)) !== [];
    }

    /**
     * @param  array{city_id: string, program_id: string, responsible_employee_id: string, center_ids: list<string>, city_wide: bool, new_center_name_en: string, new_center_name_ar: string, project: string|null, location_id?: int|null}  $row
     */
    private function isBlankLocationRow(array $row): bool
    {
        return $row['project'] === null
            && $row['city_id'] === ''
            && $row['program_id'] === ''
            && $row['responsible_employee_id'] === ''
            && $row['center_ids'] === []
            && ! $row['city_wide']
            && $row['new_center_name_en'] === ''
            && $row['new_center_name_ar'] === '';
    }

    /**
     * @return array{key: string, city_id: string, program_id: string, responsible_employee_id: string, center_ids: list<string>, city_wide: bool, new_program_name_en: string, new_program_name_ar: string, new_center_name_en: string, new_center_name_ar: string, is_new: bool, project: null, location_id: null}
     */
    private function blankLocationRow(): array
    {
        return [
            'key' => Str::random(8),
            'city_id' => '',
            'program_id' => '',
            'responsible_employee_id' => '',
            'center_ids' => [],
            'city_wide' => false,
            'new_program_name_en' => '',
            'new_program_name_ar' => '',
            'new_center_name_en' => '',
            'new_center_name_ar' => '',
            'is_new' => false,
            'project' => null,
            'location_id' => null,
        ];
    }
}
