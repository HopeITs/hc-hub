<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Support\Status\HasStatusTone;
use Modules\Hr\Support\Status\StatusTone;

/**
 * A contract's state as the lists show it. Derived from the type, the active
 * flag and the dates, in the order ContractsTable has always used.
 */
enum ContractStatus: string implements HasStatusTone
{
    case Draft = 'draft';
    case Terminated = 'terminated';
    case Active = 'active';
    case Expired = 'expired';
    case Inactive = 'inactive';

    public static function for(Contract $contract): self
    {
        return match (true) {
            (int) $contract->type === ContractTypeEnum::Draft->value => self::Draft,
            $contract->isTerminated() => self::Terminated,
            (bool) $contract->is_active => self::Active,
            $contract->end_date !== null && $contract->end_date->isPast() => self::Expired,
            default => self::Inactive,
        };
    }

    public function tone(): StatusTone
    {
        return match ($this) {
            self::Draft => StatusTone::Attn,
            self::Terminated => StatusTone::Stop,
            self::Active => StatusTone::Done,
            self::Expired => StatusTone::Stop,
            self::Inactive => StatusTone::Wait,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'pen',
            self::Terminated => 'x',
            self::Active => 'check',
            self::Expired => 'calendar-x',
            self::Inactive => 'pause',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => ContractTypeEnum::getLocalizedName(ContractTypeEnum::Draft->value)['value'],
            self::Terminated => __('contracts.status-terminated'),
            self::Active => __('general.active'),
            self::Expired => __('contracts.status-expired'),
            self::Inactive => __('general.inactive'),
        };
    }
}
