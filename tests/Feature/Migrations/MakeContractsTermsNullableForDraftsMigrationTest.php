<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MakeContractsTermsNullableForDraftsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_date_end_date_weekly_hours_and_currency_accept_null(): void
    {
        DB::statement('PRAGMA defer_foreign_keys = ON');

        DB::table('contracts')->insert([
            'id' => 801,
            'employee_id' => 1,
            'position_id' => 1,
            'contract_type_id' => 1,
            'type' => 4,
            'place' => 1,
            'start_date' => null,
            'end_date' => null,
            'weekly_hours' => null,
            'currency' => null,
            'salary_per' => 0,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('contracts', [
            'id' => 801,
            'start_date' => null,
            'end_date' => null,
            'weekly_hours' => null,
            'currency' => null,
        ]);
    }
}
