<?php

namespace Tests\Feature;

use App\Models\Center;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CenterLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_center_name_resolves_using_the_active_locale(): void
    {
        $this->assertTrue(Schema::hasColumns('centers', ['name_en', 'name_ar']));
        $this->assertFalse(Schema::hasColumn('centers', 'name'));

        $center = Center::create([
            'name_en' => 'Al Wadi Training Center',
            'name_ar' => 'مركز تدريب الوادي',
        ]);

        app()->setLocale('en');
        $this->assertSame('Al Wadi Training Center', $center->fresh()->name);

        app()->setLocale('ar');
        $this->assertSame('مركز تدريب الوادي', $center->fresh()->name);
    }

    public function test_centers_can_be_ordered_and_filtered_by_the_localized_column(): void
    {
        Center::create([
            'name_en' => 'Aleppo Center',
            'name_ar' => 'مركز حلب',
        ]);

        Center::create([
            'name_en' => 'Homs Center',
            'name_ar' => 'مركز حمص',
        ]);

        app()->setLocale('en');
        $this->assertSame(
            ['Aleppo Center', 'Homs Center'],
            Center::query()->orderBy(transColumn('name'))->pluck(transColumn('name'))->all(),
        );

        app()->setLocale('ar');
        $this->assertSame(
            'مركز حمص',
            Center::query()->where(transColumn('name'), 'مركز حمص')->value(transColumn('name')),
        );
    }
}
