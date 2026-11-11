<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

use Modules\Hr\Support\Status\HasStatusTone;
use Modules\Hr\Support\Status\StatusTone;

enum LeaveRequestStatus: int implements HasStatusTone
{
    case Pending = 0;

    case Accepted = 1;

    case Rejected = 2;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        return ['value' => __(self::labelKey($value))];
    }

    /** The instance for a stored value; an unknown value reads as pending, as getLocalizedName() always has. */
    public static function of(int|string|null $value): self
    {
        return self::tryFrom((int) $value) ?? self::from(self::Pending->value);
    }

    public function tone(): StatusTone
    {
        return match ($this->value) {
            self::Accepted->value => StatusTone::Done,
            self::Rejected->value => StatusTone::Stop,
            default => StatusTone::Wait,
        };
    }

    public function icon(): string
    {
        return match ($this->value) {
            self::Accepted->value => 'check',
            self::Rejected->value => 'x',
            default => 'clock',
        };
    }

    public function label(): string
    {
        return __(self::labelKey((int) $this->value));
    }

    private static function labelKey(int $value): string
    {
        return match ($value) {
            self::Accepted->value => 'leaves.status.accepted',
            self::Rejected->value => 'leaves.status.rejected',
            default => 'leaves.status.pending',
        };
    }
}
