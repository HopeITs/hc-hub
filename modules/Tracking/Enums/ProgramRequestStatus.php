<?php

declare(strict_types=1);

namespace Modules\Tracking\Enums;

use App\Enums\Concerns\HasTranslatedLabel;

/**
 * Where a request for a program HR does not have yet stands (contracts.md
 * §7, W-34a): opened by a proposal's location row, created once the admin
 * links the HR program, or declined with a reason.
 *
 * The value lands in `program_requests.status` (VARCHAR(20), default
 * `pending`). Casing is part of the value.
 */
enum ProgramRequestStatus: string
{
    use HasTranslatedLabel;

    case Pending = 'pending';
    case Created = 'created';
    case Declined = 'declined';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
