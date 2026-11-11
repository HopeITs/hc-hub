<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\ContractAmendment;

/**
 * An amendment is part of its contract, so ContractPolicy decides: this
 * class only routes the amendment abilities to it (HR registered
 * ContractPolicy for both models; the hub finds policies by name).
 * Load `contract` on amendments checked in a list.
 */
class ContractAmendmentPolicy
{
    public function __construct(private ContractPolicy $contracts) {}

    public function view(User $user, ContractAmendment $amendment): bool
    {
        return $this->contracts->view($user, $amendment->contract);
    }

    /** Approve or edit a draft. */
    public function decideAmendment(User $user, ContractAmendment $amendment): bool
    {
        return $this->contracts->decideAmendment($user, $amendment);
    }

    /** Delete a draft, or cancel one approved and not yet in effect. */
    public function discardAmendment(User $user, ContractAmendment $amendment): bool
    {
        return $this->contracts->discardAmendment($user, $amendment);
    }

    /** Print the amendment sheet: it carries the pay clause, so the contract print's rule. */
    public function exportAmendment(User $user, ContractAmendment $amendment): bool
    {
        return $this->contracts->export($user, $amendment->contract);
    }
}
