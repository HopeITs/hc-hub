<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Database\Factories\LeaveBalanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Hr\Enums\LeaveTypeCases;

/**
 * One leave type's allowance on one contract, in hours: `deserved` is the
 * entitlement, `used` what accepted requests took, `amendment_offset` what a
 * contract amendment moved. Worded as days + hours by the leave pages (the
 * day length depends on the contract's schedule).
 *
 * @property int $id
 * @property int $contract_id
 * @property int $type_id
 * @property int $deserved
 * @property float $used
 * @property float $amendment_offset
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['contract_id', 'type_id', 'deserved', 'used', 'amendment_offset'])]
class LeaveBalance extends Model
{
    /** @use HasFactory<LeaveBalanceFactory> */
    use HasFactory;

    /**
     * Mirror the column defaults for strict mode.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'used' => 0,
        'amendment_offset' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'deserved' => 'integer',
            'used' => 'float',
            'amendment_offset' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    /**
     * @return BelongsTo<LeaveType, $this>
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'type_id');
    }

    /**
     * @return HasMany<LeaveRequest, $this>
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Hours still available: the entitlement less what has been used.
     */
    public function remainingHours(): float
    {
        return (float) $this->deserved - $this->used;
    }

    public function isAnnual(): bool
    {
        return $this->type_id === LeaveTypeCases::Annual;
    }
}
