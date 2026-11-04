<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\DefaultRiskLibrary;
use Illuminate\Database\Seeder;

/**
 * The starting risk library (W-09, D-61). DefaultRiskLibrary::ensure() is
 * the one idempotent rule, shared with the data migration; the list is empty
 * until MEAL supplies it, so this inserts nothing today.
 */
class RiskLibrarySeeder extends Seeder
{
    public function run(): void
    {
        DefaultRiskLibrary::ensure();
    }
}
