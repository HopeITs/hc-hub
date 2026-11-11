<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Hr\Support\Settings\AuthorizesProjectBasics;

/**
 * AnnualLeaveRule rows are HR reference data, managed from HR settings.
 */
final class AnnualLeaveRulePolicy
{
    use AuthorizesProjectBasics;
}
