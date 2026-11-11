<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

use Modules\Hr\Support\Status\HasStatusTone;
use Modules\Hr\Support\Status\StatusTone;

/**
 * Draft (printed for signing) → Approved (signed; waits for its effective
 * date) → Applied (its terms are now the contract's).
 */
enum ContractAmendmentStatus: int implements HasStatusTone
{
    case Draft = 0;

    case Approved = 1;

    case Applied = 2;

    /** The instance for a stored value; an unknown value reads as a draft. */
    public static function of(int|string|null $value): self
    {
        return self::tryFrom((int) $value) ?? self::from(self::Draft->value);
    }

    public function tone(): StatusTone
    {
        return match ($this->value) {
            // The one the contract runs on now; a superseded one is shown as done.
            self::Applied->value => StatusTone::Work,
            self::Approved->value => StatusTone::Wait,
            default => StatusTone::Attn,
        };
    }

    public function icon(): string
    {
        return match ($this->value) {
            self::Applied->value => 'check',
            self::Approved->value => 'clock',
            default => 'pen',
        };
    }

    public function label(): string
    {
        return __(match ((int) $this->value) {
            self::Applied->value => 'hr_contract_amendments.status.applied',
            self::Approved->value => 'hr_contract_amendments.status.approved',
            default => 'hr_contract_amendments.status.draft',
        });
    }
}
