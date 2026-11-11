<?php

declare(strict_types=1);

namespace Modules\Tracking\Enums;

use App\Enums\Concerns\HasTranslatedLabel;

/**
 * Whether the General Manager has approved a donor that Public Relations
 * requested from the proposal form (contracts.md §7, D-57).
 *
 * The value lands in `donors.approval_status` (VARCHAR(10), NOT NULL, default
 * `approved`). Casing is part of the value.
 */
enum DonorApprovalStatus: string
{
    use HasTranslatedLabel;

    case Pending = 'pending';
    case Approved = 'approved';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
