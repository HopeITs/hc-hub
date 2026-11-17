<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Attendance;

use RuntimeException;

/** A step of the month's workflow that is not allowed now; the message is a translation key. */
class WorkflowRefused extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self('hr_attendance.workflow.refused.'.$reason);
    }
}
