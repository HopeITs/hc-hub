<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\FundingAgreements;

use Illuminate\Support\Facades\Gate;
use Modules\Tracking\Models\DonorProposal;

/**
 * Delete a funding agreement, only while no tranche or donor report hangs off it.
 */
class DeleteFundingAgreement
{
    /**
     * @return bool false when the agreement still has tranches or reports and nothing changed
     */
    public function handle(DonorProposal $agreement): bool
    {
        Gate::authorize('funding-agreements.delete');

        if ($agreement->transfers()->exists() || $agreement->reports()->exists()) {
            return false;
        }

        $agreement->delete();

        return true;
    }
}
