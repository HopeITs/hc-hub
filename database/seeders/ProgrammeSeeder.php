<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Program;

/**
 * Programs, filled by syncing from HR.
 *
 * Nothing is invented here: every HR program gets its tracking row under the
 * same id, and managers come from HR's Program Manager positions. Strategies
 * are left unassigned.
 */
class ProgrammeSeeder extends Seeder
{
    /**
     * Programs are Core rows imported by hub:import-hr (or created from a
     * proposal); seed a few only into an empty database.
     */
    public function run(): void
    {
        if (Program::query()->doesntExist()) {
            Program::factory()->unassigned()->count(4)->create();
        }
    }
}
