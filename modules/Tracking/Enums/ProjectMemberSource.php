<?php

declare(strict_types=1);

namespace Modules\Tracking\Enums;

use App\Enums\Concerns\HasTranslatedLabel;

/**
 * How a project_members row came about (D4). Manual: someone added it, or the
 * project's owner changed. Inferred: the one-off backfill read it from what
 * the project already recorded (roadmap 2.3), so its dates are unknown.
 *
 * Values are lower case and stored in `project_members.source` (VARCHAR(20)).
 */
enum ProjectMemberSource: string
{
    use HasTranslatedLabel;

    case Manual = 'manual';
    case Inferred = 'inferred';
}
