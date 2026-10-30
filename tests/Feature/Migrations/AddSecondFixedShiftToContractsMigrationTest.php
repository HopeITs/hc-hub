<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AddSecondFixedShiftToContractsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_second_shift_columns_exist_on_contracts(): void
    {
        $this->assertTrue(Schema::hasColumn('contracts', 'work_hours_2_from'));
        $this->assertTrue(Schema::hasColumn('contracts', 'work_hours_2_to'));
    }

    public function test_down_drops_the_second_shift_columns(): void
    {
        $migration = require base_path('modules/Hr/database/migrations/2026_09_15_120000_add_second_fixed_shift_to_contracts.php');

        $migration->down();

        $this->assertFalse(Schema::hasColumn('contracts', 'work_hours_2_from'));
        $this->assertFalse(Schema::hasColumn('contracts', 'work_hours_2_to'));
    }
}
