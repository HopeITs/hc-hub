<?php

namespace Modules\Hr\Tests\Unit;

use Modules\Hr\Enums\ScheduleMode;
use Tests\TestCase;

class ScheduleModeEnumTest extends TestCase
{
    public function test_fixed_and_flexible_are_localized_in_both_languages(): void
    {
        app()->setLocale('en');
        $this->assertSame('Fixed', ScheduleMode::getLocalizedName(ScheduleMode::Fixed)['value']);
        $this->assertSame('Flexible', ScheduleMode::getLocalizedName(ScheduleMode::Flexible)['value']);

        app()->setLocale('ar');
        $this->assertSame('ثابت', ScheduleMode::getLocalizedName(ScheduleMode::Fixed)['value']);
        $this->assertSame('مرن', ScheduleMode::getLocalizedName(ScheduleMode::Flexible)['value']);
    }
}
