<?php

namespace Tests\Unit;

use Modules\Hr\Enums\SessionDuration;
use Tests\TestCase;

class SessionDurationEnumTest extends TestCase
{
    public function test_the_three_session_lengths_are_localized_in_both_languages(): void
    {
        app()->setLocale('en');
        $this->assertSame('Hour', SessionDuration::getLocalizedName(SessionDuration::Hour)['value']);
        $this->assertSame('Hour and a Half', SessionDuration::getLocalizedName(SessionDuration::HourAndHalf)['value']);
        $this->assertSame('Two Hours', SessionDuration::getLocalizedName(SessionDuration::TwoHours)['value']);

        app()->setLocale('ar');
        $this->assertSame('ساعة', SessionDuration::getLocalizedName(SessionDuration::Hour)['value']);
        $this->assertSame('ساعة ونصف', SessionDuration::getLocalizedName(SessionDuration::HourAndHalf)['value']);
        $this->assertSame('ساعتان', SessionDuration::getLocalizedName(SessionDuration::TwoHours)['value']);
    }
}
