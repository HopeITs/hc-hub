<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Contracts;

use RuntimeException;

/**
 * An approved amendment that cannot be written onto its contract. Nothing is
 * changed when this is thrown; the amendment stays approved and the reason is
 * shown to HR (or logged by the daily run).
 */
class ContractAmendmentCannotApply extends RuntimeException
{
    public static function notApproved(): self
    {
        return new self(__('hr_contract_amendments.errors.not_approved'));
    }

    /** The contract's terms moved after the amendment recorded them as its "before". */
    public static function contractChanged(): self
    {
        return new self(__('hr_contract_amendments.errors.contract_changed'));
    }

    /** A pay figure cannot be decrypted here (a copy without the salary key). */
    public static function unreadablePay(): self
    {
        return new self(__('hr_contract_amendments.errors.unreadable_pay'));
    }
}
