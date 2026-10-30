<?php

namespace Modules\Hr\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Hr\Database\Seeders\ContractTypeSeeder;
use Modules\Hr\Models\ContractType;
use Tests\TestCase;

class ContractTypeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_a_fresh_database_creates_exactly_the_three_consolidated_rows_at_their_canonical_ids(): void
    {
        $this->seed(ContractTypeSeeder::class);

        $this->assertSame(3, ContractType::count());
        $this->assertDatabaseHas('contract_types', ['id' => 1, 'name_en' => 'Volunteer Contract', 'name_ar' => 'عقد تطوّع']);
        $this->assertDatabaseHas('contract_types', ['id' => 3, 'name_en' => 'Trial Volunteer Contract', 'name_ar' => 'عقد تطوّع تجريبي']);
        $this->assertDatabaseHas('contract_types', ['id' => 5, 'name_en' => 'Specific Voluntary Service Contract', 'name_ar' => 'عقد تقديم خدمة تطوعية محددة']);
    }

    public function test_each_freshly_seeded_type_resolves_to_its_expected_category(): void
    {
        $this->seed(ContractTypeSeeder::class);

        $this->assertSame('annual', ContractType::find(1)->category);
        $this->assertSame('trial', ContractType::find(3)->category);
        $this->assertSame('service', ContractType::find(5)->category);
    }
}
