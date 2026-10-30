<?php

namespace Modules\Hr\Tests\Unit;

use Modules\Hr\Enums\ContractType;
use Tests\TestCase;

class ContractTypeEnumTest extends TestCase
{
    public function test_draft_is_type_value_four_localized_in_both_languages(): void
    {
        $this->assertSame(4, ContractType::Draft);

        app()->setLocale('en');
        $this->assertSame('Draft', ContractType::getLocalizedName(ContractType::Draft)['value']);

        app()->setLocale('ar');
        $this->assertSame('مسودة', ContractType::getLocalizedName(ContractType::Draft)['value']);
    }
}
