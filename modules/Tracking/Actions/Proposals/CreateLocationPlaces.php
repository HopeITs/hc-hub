<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Proposals;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Modules\Core\Models\Center;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Tracking\Jobs\CheckProgramLogframe;

/**
 * Creates the Core program and center a proposal location row opens as new
 * (user decision 2026-11-11, replacing the program-request bridge W-34b).
 *
 * A row marked new with no program and both program names gets a new Core
 * program (programs.create); a row naming a new center gets a Core center in
 * its city (centers.create). The row then points at them, so HR screens see
 * them at once. Runs inside the caller's transaction (SaveProposal through
 * ProposalLocationRows), after SaveProposal validated the names and the
 * permissions; they are checked again here.
 */
final class CreateLocationPlaces
{
    /**
     * The rows with the new program and center ids filled in.
     *
     * @param  list<array{city_id: int, program_id: int|null, responsible_employee_id: int|null, center_ids: list<int>, is_new: bool, new_program_name_en: string, new_program_name_ar: string, new_center_name_en?: string, new_center_name_ar?: string}>  $rows
     * @return list<array{city_id: int, program_id: int|null, responsible_employee_id: int|null, center_ids: list<int>, is_new: bool, new_program_name_en: string, new_program_name_ar: string, new_center_name_en?: string, new_center_name_ar?: string}>
     */
    public function handle(array $rows, User $actor): array
    {
        $newProgramIds = [];

        foreach ($rows as $index => $row) {
            if ($row['is_new'] && $row['program_id'] === null && $row['new_program_name_en'] !== '' && $row['new_program_name_ar'] !== '') {
                Gate::forUser($actor)->authorize('create', Program::class);

                $program = $this->createProgram($row['new_program_name_en'], $row['new_program_name_ar']);
                $rows[$index]['program_id'] = $program->id;
                $newProgramIds[] = $program->id;
            }

            $centerEn = $row['new_center_name_en'] ?? '';
            $centerAr = $row['new_center_name_ar'] ?? '';

            if ($centerEn !== '' && $centerAr !== '') {
                Gate::forUser($actor)->authorize('centers.create');

                $center = Center::query()->create([
                    'city_id' => $row['city_id'],
                    'name_en' => $centerEn,
                    'name_ar' => $centerAr,
                ]);

                $centerIds = [...$row['center_ids'], $center->id];
                sort($centerIds);
                $rows[$index]['center_ids'] = array_values(array_unique($centerIds));
            }
        }

        if ($newProgramIds !== []) {
            CheckProgramLogframe::dispatch($newProgramIds)->afterCommit();
        }

        return $rows;
    }

    /**
     * A Core program has one name; the English one is kept there and the
     * Arabic one as its description until programs carry both names.
     */
    private function createProgram(string $nameEn, string $nameAr): Program
    {
        $program = new Program;
        $program->forceFill([
            'name' => $nameEn,
            'description_ar' => $nameAr,
            'slug' => self::uniqueSlug($nameEn),
            'is_active' => true,
        ])->save();

        return $program;
    }

    /**
     * An upper-case slug like HR's own (HC, LEAP), made unique with a number.
     */
    private static function uniqueSlug(string $name): string
    {
        $base = Str::upper(Str::slug($name)) ?: 'PROGRAM';
        $slug = $base;
        $suffix = 2;

        while (Program::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
