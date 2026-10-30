<?php

namespace Tests\Unit;

use Modules\Hr\Enums\Currency;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    public function test_contract_currency_labels_are_bilingual_and_exact(): void
    {
        $this->assertSame(
            ['en' => 'USD', 'ar' => 'دولار أمريكي'],
            Currency::getLocalizedName(Currency::USD),
        );

        $this->assertSame(
            ['en' => 'EUR', 'ar' => 'يورو'],
            Currency::getLocalizedName(Currency::EUR),
        );

        $this->assertSame(
            ['en' => 'SYP', 'ar' => 'ليرة سورية'],
            Currency::getLocalizedName(Currency::SYP),
        );
    }
}
