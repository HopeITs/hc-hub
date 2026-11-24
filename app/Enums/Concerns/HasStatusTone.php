<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

use App\Enums\StatusTone;

/**
 * A status-like enum that maps each case onto one shared StatusTone, so every
 * badge, pill and marker colours it the same way app-wide (W-28).
 *
 * Implement tone() and icon() as exhaustive matches with no default arm: a new
 * case must declare its tone and its icon, the same way ProposalStatus::phase()
 * is exhaustive. The palette is red plus greys only, so the icon and the label
 * are what tell two grey states apart.
 */
interface HasStatusTone
{
    /**
     * The visual tone for this case.
     */
    public function tone(): StatusTone;

    /**
     * The Heroicon (flux:icon name) drawn before the label in every badge and pill.
     */
    public function icon(): string;
}
