<?php

declare(strict_types=1);

namespace Modules\Tracking\Enums;

use App\Enums\Concerns\HasStatusTone;
use App\Enums\Concerns\HasTranslatedLabel;
use App\Enums\StatusTone;

/**
 * Where a change request on an approved proposal stands (contracts.md §7,
 * Y-09, D-89a). A withdrawn request is `rejected` with the requester's note.
 */
enum ProposalChangeRequestStatus: string implements HasStatusTone
{
    use HasTranslatedLabel;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Waiting reads as a warning, approved as good, rejected as neutral (a
     * withdrawn or refused change is not an alarm).
     */
    public function tone(): StatusTone
    {
        return match ($this) {
            self::Pending => StatusTone::Warn,
            self::Approved => StatusTone::Good,
            self::Rejected => StatusTone::Neutral,
        };
    }

    /**
     * The Heroicon drawn before the label in every badge and pill.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'clock',
            self::Approved => 'check-circle',
            self::Rejected => 'x-circle',
        };
    }
}
