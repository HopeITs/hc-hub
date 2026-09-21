<?php

namespace Tests\Unit;

use App\Enums\PayMode;
use Tests\TestCase;

class PayModeEnumTest extends TestCase
{
    public function test_fixed_and_flexible_are_localized_in_both_languages(): void
    {
        app()->setLocale('en');
        $this->assertSame('Fixed', PayMode::getLocalizedName(PayMode::Fixed)['value']);
        $this->assertSame('Flexible', PayMode::getLocalizedName(PayMode::Flexible)['value']);

        app()->setLocale('ar');
        $this->assertSame('ثابت', PayMode::getLocalizedName(PayMode::Fixed)['value']);
        $this->assertSame('مرن', PayMode::getLocalizedName(PayMode::Flexible)['value']);
    }
}
