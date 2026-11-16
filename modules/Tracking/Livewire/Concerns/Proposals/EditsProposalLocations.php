<?php

declare(strict_types=1);

namespace Modules\Tracking\Livewire\Concerns\Proposals;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Tracking\Models\ProposalLocation;

/**
 * The location rows of the proposal form (W-34a slice 3): where a proposal
 * runs, as city, program (or a new program), responsible and one or more
 * centers. They replace the project picker and go to SaveProposal as
 * `locations`, which validates and writes them; a new program or center a
 * row names is created there (CreateLocationPlaces), for someone who may.
 *
 * A row that already stands for a linked project shows its city and program
 * locked (the link owns them, ProposalLocationRows matches rows by both and
 * never deletes a linked row); only its responsible and centers change here,
 * also on an approved proposal, which is how centers are given to the
 * proposals entered before the rows existed.
 *
 * Used by ManagesProposalForm.
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
     * @var list<array{key: string, city_id: string, program_id: string, responsible_employee_id: string, center_ids: list<string>, new_program_name_en: string, new_program_name_ar: string, new_center_name_en: string, new_center_name_ar: string, is_new: bool, project: string|null}>
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

                $byCity[(int) $held->city_id][] = ['id' => (int) $centerId, 'name' => $held->name];
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

        $this->locations = array_values(array_filter($this->locations, fn (int $key): bool => $key !== $index, ARRAY_FILTER_USE_KEY));
        $this->resetValidation();
    }

    /**
     * Keep each row coherent as it is edited: a new city drops the centers of
     * the old one, a chosen program proposes its manager as the responsible
     * when none is set, and a program from the list clears the typed names.
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
            'new_program_name_en' => '',
            'new_program_name_ar' => '',
            'new_center_name_en' => '',
            'new_center_name_ar' => '',
            'is_new' => $row->is_new,
            'project' => $row->project?->name,
        ])->all());

        $this->hadLocationRows = $this->locations !== [];
    }

    protected function resetLocationRows(): void
    {
        $this->locations = [];
        $this->hadLocationRows = false;
    }

    /**
     * The rows as SaveProposal's `locations`. A row left completely empty
     * (the one a create starts with, say) is not a place and is not sent.
     *
     * @return list<array{city_id: string, program_id: string|null, responsible_employee_id: string|null, center_ids: list<string>, is_new: bool, new_program_name_en: string, new_program_name_ar: string, new_center_name_en: string, new_center_name_ar: string}>
     */
    protected function locationPayload(): array
    {
        return array_values(array_map(fn (array $row): array => [
            'city_id' => $row['city_id'],
            'program_id' => $row['program_id'] === '' || $row['program_id'] === self::NewProgramValue ? null : $row['program_id'],
            'responsible_employee_id' => $row['responsible_employee_id'] === '' ? null : $row['responsible_employee_id'],
            'center_ids' => $row['center_ids'],
            'is_new' => $row['program_id'] === self::NewProgramValue || $row['is_new'],
            'new_program_name_en' => $row['program_id'] === self::NewProgramValue ? $row['new_program_name_en'] : '',
            'new_program_name_ar' => $row['program_id'] === self::NewProgramValue ? $row['new_program_name_ar'] : '',
            'new_center_name_en' => $row['new_center_name_en'],
            'new_center_name_ar' => $row['new_center_name_ar'],
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
     * @param  array{city_id: string, program_id: string, responsible_employee_id: string, center_ids: list<string>, new_center_name_en: string, new_center_name_ar: string, project: string|null}  $row
     */
    private function isBlankLocationRow(array $row): bool
    {
        return $row['project'] === null
            && $row['city_id'] === ''
            && $row['program_id'] === ''
            && $row['responsible_employee_id'] === ''
            && $row['center_ids'] === []
            && $row['new_center_name_en'] === ''
            && $row['new_center_name_ar'] === '';
    }

    /**
     * @return array{key: string, city_id: string, program_id: string, responsible_employee_id: string, center_ids: list<string>, new_program_name_en: string, new_program_name_ar: string, new_center_name_en: string, new_center_name_ar: string, is_new: bool, project: null}
     */
    private function blankLocationRow(): array
    {
        return [
            'key' => Str::random(8),
            'city_id' => '',
            'program_id' => '',
            'responsible_employee_id' => '',
            'center_ids' => [],
            'new_program_name_en' => '',
            'new_program_name_ar' => '',
            'new_center_name_en' => '',
            'new_center_name_ar' => '',
            'is_new' => false,
            'project' => null,
        ];
    }
}
