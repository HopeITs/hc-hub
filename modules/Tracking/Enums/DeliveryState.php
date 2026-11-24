<?php

declare(strict_types=1);

namespace Modules\Tracking\Enums;

use App\Enums\Concerns\HasStatusTone;
use App\Enums\Concerns\HasTranslatedLabel;
use App\Enums\StatusTone;

/**
 * Where a media deliverable stands (contracts.md §12.2), stored in `tasks.delivery_state`: requested → delivered → accepted | returned; returned → delivered again.
 */
enum DeliveryState: string implements HasStatusTone
{
    use HasTranslatedLabel;

    case Requested = 'requested';
    case Delivered = 'delivered';
    case Accepted = 'accepted';
    case Returned = 'returned';

    /**
     * All values, for validation rules.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The shared visual tone for this case (W-28); exhaustive, no default arm.
     */
    public function tone(): StatusTone
    {
        return match ($this) {
            self::Requested => StatusTone::Neutral,
            self::Delivered => StatusTone::Active,
            self::Accepted => StatusTone::Good,
            self::Returned => StatusTone::Warn,
        };
    }

    /**
     * The Heroicon drawn before the label in every badge and pill.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Requested => 'minus-circle',
            self::Delivered => 'truck',
            self::Accepted => 'check-circle',
            self::Returned => 'arrow-uturn-left',
        };
    }
}
