<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Transfers;

use Illuminate\Support\Facades\Gate;
use Modules\Tracking\Models\TransfersTracking;

/**
 * Delete a tranche: a mistaken or duplicate entry.
 */
class DeleteTransfer
{
    public function handle(TransfersTracking $transfer): void
    {
        Gate::authorize('transfers.delete');

        $transfer->delete();
    }
}
