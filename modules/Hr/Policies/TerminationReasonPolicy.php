<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Hr\Support\Settings\AuthorizesProjectBasics;

/**
 * TerminationReason rows are HR reference data, managed from HR settings.
 */
final class TerminationReasonPolicy
{
    use AuthorizesProjectBasics;
}
