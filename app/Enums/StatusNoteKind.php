<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasStatusTone;
use App\Enums\Concerns\HasTranslatedLabel;

/**
 * What a proposal status note records (contracts.md §7, W-07, D-59).
 *
 * The value lands in `proposal_status_notes.kind` (VARCHAR(20), NOT NULL,
 * default `transition`). Only `transition` and `correction` move the status;
 * `negotiation` is a note written while the proposal stays submitted.
 */
enum StatusNoteKind: string implements HasStatusTone
{
    use HasTranslatedLabel;

    case Transition = 'transition';
    case Correction = 'correction';
    case Negotiation = 'negotiation';
    case OnHold = 'on_hold';
    case OffHold = 'off_hold';
    case Extension = 'extension';

    public function tone(): StatusTone
    {
        return match ($this) {
            self::Negotiation, self::Extension => StatusTone::Active,
            self::Correction, self::OnHold, self::OffHold => StatusTone::Warn,
            self::Transition => StatusTone::Neutral,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
