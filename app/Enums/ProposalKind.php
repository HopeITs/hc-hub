<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;

/**
 * What a proposal funds (contracts.md §7, D-55b).
 *
 * A project proposal funds centers' projects through the full lifecycle. A
 * payroll proposal funds a center's volunteer salaries and equipment: it has
 * a narrative, a budget and a final report only, no projects, logframe,
 * targets or monthly reports, and MEAL aggregations skip it.
 *
 * The value lands in `proposals.kind` (VARCHAR(10), NOT NULL, default
 * `project`). It is set on create and never changes.
 */
enum ProposalKind: string
{
    use HasTranslatedLabel;

    case Project = 'project';
    case Payroll = 'payroll';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
