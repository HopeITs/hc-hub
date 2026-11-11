<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Hr\Support\Settings\AuthorizesProjectBasics;

/**
 * ChecklistItem rows are HR reference data, managed from HR settings.
 */
final class ChecklistItemPolicy
{
    use AuthorizesProjectBasics;
}
