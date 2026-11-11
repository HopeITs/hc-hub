<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tracking\Support\DefaultNarrativeQuestions;

/**
 * The starting narrative question bank (four monthly, two quarter-end
 * questions). Query builder only, so it is idempotent and records no
 * activity (DatabaseSeeder also wraps it in Activity::withoutRecording).
 */
class NarrativeQuestionSeeder extends Seeder
{
    public function run(): void
    {
        DefaultNarrativeQuestions::ensure();
    }
}
