<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\ContractAmendmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\User;
use Modules\Hr\Casts\EncryptedSalary;
use Modules\Hr\Enums\ContractAmendmentStatus;

/**
 * A dated change to the hours, schedule or pay of an active contract (ملحق عقد).
 *
 * The contract row always holds the current terms; this row keeps what they
 * were and what they became, so a person's year can be read back ("part time
 * until 5/11, full time since"). Pay figures are kept out of the JSON
 * snapshots and live in salary_before/after and the pay-tier rows, encrypted
 * like the contract's own.
 *
 * @property int $id
 * @property int $contract_id
 * @property int $number
 * @property int $status
 * @property CarbonImmutable $effective_from
 * @property string|null $reason
 * @property array<mixed> $terms_before
 * @property array<mixed> $terms_after
 * @property string|null $salary_before
 * @property string|null $salary_after
 * @property int|null $created_by
 * @property int|null $approved_by
 * @property mixed|null $approved_at
 * @property mixed|null $applied_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['contract_id', 'number', 'status', 'effective_from', 'reason', 'terms_before', 'terms_after', 'salary_before', 'salary_after', 'created_by', 'approved_by', 'approved_at', 'applied_at'])]
class ContractAmendment extends Model
{
    /** @use HasFactory<ContractAmendmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'status' => 'integer',
            'effective_from' => 'date',
            'terms_before' => 'array',
            'terms_after' => 'array',
            'salary_before' => EncryptedSalary::class,
            'salary_after' => EncryptedSalary::class,
            'approved_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    /**
     * The terms an amendment may change, besides the pay figures themselves.
     * Position, contract category and dates are not among them: a new position
     * is a new contract (capacity), a longer term is a renewal.
     */
    const AMENDABLE_FIELDS = [
        'type',
        'weekly_hours',
        'schedule_mode',
        'work_hours_from',
        'work_hours_to',
        'work_hours_2_from',
        'work_hours_2_to',
        'work_days_en',
        'work_days_ar',
        'work_day_from',
        'work_day_to',
        'show_days_per_week',
        'show_hours_per_week',
        'show_hours_per_day',
        'show_day_range',
        'flex_days_per_week',
        'flex_hours_per_week',
        'flex_hours_per_month',
        'flex_varies_by_requirements',
        'flex_coordinated_with_manager',
        'flex_custom_en',
        'flex_custom_ar',
        'pay_mode',
        'session_duration',
        'currency',
        'salary_per',
    ];

    /** The changes that put pay on the printed sheet. */
    const PAY_FIELDS = ['salary', 'pay_tiers', 'currency', 'salary_per', 'pay_mode', 'session_duration'];

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<ContractAmendmentPayTier, $this>
     */
    public function payTiers(): HasMany
    {
        return $this->hasMany(ContractAmendmentPayTier::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<ContractAmendmentPayTier, $this>
     */
    public function payTiersBefore(): HasMany
    {
        return $this->payTiers()->where('side', ContractAmendmentPayTier::SIDE_BEFORE);
    }

    /**
     * @return HasMany<ContractAmendmentPayTier, $this>
     */
    public function payTiersAfter(): HasMany
    {
        return $this->payTiers()->where('side', ContractAmendmentPayTier::SIDE_AFTER);
    }

    public function statusEnum(): ContractAmendmentStatus
    {
        return ContractAmendmentStatus::of($this->status);
    }

    public function isDraft(): bool
    {
        return $this->status === ContractAmendmentStatus::Draft->value;
    }

    public function isApproved(): bool
    {
        return $this->status === ContractAmendmentStatus::Approved->value;
    }

    public function isApplied(): bool
    {
        return $this->status === ContractAmendmentStatus::Applied->value;
    }

    /**
     * Approved amendments whose effective date has come, for the daily run.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeDueOn(Builder $query, CarbonInterface $day): Builder
    {
        return $query
            ->where('status', ContractAmendmentStatus::Approved->value)
            ->whereDate('effective_from', '<=', $day);
    }

    /**
     * The amendable terms of $contract as they stand now: what an amendment
     * records as its "before", and the starting point of its "after".
     *
     * @return array<string, mixed>
     */
    public static function snapshotOf(Contract $contract): array
    {
        $terms = [];

        foreach (self::AMENDABLE_FIELDS as $field) {
            $terms[$field] = $contract->getAttribute($field);
        }

        return $terms;
    }

    /**
     * The terms this amendment actually changes, in AMENDABLE_FIELDS order,
     * followed by `salary` and `pay_tiers` when the figures move.
     *
     * Values are compared normalised, so a form's "09:00" against the stored
     * "09:00:00", or "40" against 40, is not a change; the salary is compared
     * decrypted, since its ciphertext differs on every write.
     *
     * @return list<string>
     */
    public function changedFields(): array
    {
        $before = $this->terms_before ?? [];
        $after = $this->terms_after ?? [];
        $changed = [];

        foreach (self::AMENDABLE_FIELDS as $field) {
            if (self::normalise($before[$field] ?? null) !== self::normalise($after[$field] ?? null)) {
                $changed[] = $field;
            }
        }

        if (self::normalise($this->salary_before) !== self::normalise($this->salary_after)) {
            $changed[] = 'salary';
        }

        if ($this->tierSignature(ContractAmendmentPayTier::SIDE_BEFORE) !== $this->tierSignature(ContractAmendmentPayTier::SIDE_AFTER)) {
            $changed[] = 'pay_tiers';
        }

        return $changed;
    }

    /**
     * The contract as this amendment makes it — in memory only, never saved.
     * The sheet words it through the contract's own clause components, and
     * editing a draft opens the composer on it.
     */
    public function termsAsContract(): Contract
    {
        $after = $this->contract->replicate();
        $after->forceFill($this->terms_after ?? []);
        $after->salary = $this->salary_after;
        $after->setRelation('payTiers', $this->payTiersAfter()->get()->map(
            fn (ContractAmendmentPayTier $tier) => new ContractPayTier([
                'condition_en' => $tier->condition_en,
                'condition_ar' => $tier->condition_ar,
                'amount' => $tier->amount,
                'sort_order' => $tier->sort_order,
            ]),
        ));

        return $after;
    }

    public function touchesPay(): bool
    {
        return array_intersect($this->changedFields(), self::PAY_FIELDS) !== [];
    }

    /**
     * Hours of one working day before and after, the way leave balances count them.
     */
    public function dailyHoursBefore(): int
    {
        return intdiv((int) ($this->terms_before['weekly_hours'] ?? 0), 5);
    }

    public function dailyHoursAfter(): int
    {
        return intdiv((int) ($this->terms_after['weekly_hours'] ?? 0), 5);
    }

    /**
     * Does $contract still carry exactly the terms this amendment recorded as
     * its "before"? If not, someone changed the contract in between, and
     * writing the "after" over it would silently undo their change.
     */
    public function matchesContract(Contract $contract): bool
    {
        return $this->contractCarries($contract, 'before');
    }

    /**
     * Does $contract carry this amendment's "after" — what it should, when
     * this is the latest amendment in effect (contracts:amendments-check)?
     */
    public function isCarriedBy(Contract $contract): bool
    {
        return $this->contractCarries($contract, 'after');
    }

    /**
     * Do this amendment's "before" terms equal $previous's "after" ones?
     */
    public function followsOn(ContractAmendment $previous): bool
    {
        foreach (self::AMENDABLE_FIELDS as $field) {
            if (self::normalise($this->terms_before[$field] ?? null) !== self::normalise($previous->terms_after[$field] ?? null)) {
                return false;
            }
        }

        return self::normalise($this->salary_before) === self::normalise($previous->salary_after)
            && $this->tierSignature(ContractAmendmentPayTier::SIDE_BEFORE) === $previous->tierSignature(ContractAmendmentPayTier::SIDE_AFTER);
    }

    /**
     * Is any pay figure on this amendment stored but unreadable here? On a
     * database copy without the salary key every figure reads as null, and
     * applying would write that null over the contract's real pay.
     */
    public function hasUnreadablePay(): bool
    {
        foreach (['salary_before', 'salary_after'] as $field) {
            if (filled($this->getAttributes()[$field] ?? null) && $this->{$field} === null) {
                return true;
            }
        }

        return $this->payTiers()->get()->contains(
            fn (ContractAmendmentPayTier $tier) => filled($tier->getAttributes()['amount'] ?? null) && $tier->amount === null,
        );
    }

    /**
     * @param  'before'|'after'  $side
     */
    private function contractCarries(Contract $contract, string $side): bool
    {
        $terms = $side === 'before' ? $this->terms_before : $this->terms_after;

        foreach (ContractAmendment::snapshotOf($contract) as $field => $value) {
            if (self::normalise($value) !== self::normalise($terms[$field] ?? null)) {
                return false;
            }
        }

        return self::normalise($contract->salary) === self::normalise($side === 'before' ? $this->salary_before : $this->salary_after)
            && self::tiersOf($contract->payTiers()->get()) === $this->tierSignature(
                $side === 'before' ? ContractAmendmentPayTier::SIDE_BEFORE : ContractAmendmentPayTier::SIDE_AFTER,
            );
    }

    /**
     * @return list<array{0: string, 1: string, 2: string|null}>
     */
    private function tierSignature(int $side): array
    {
        $tiers = $this->relationLoaded('payTiers')
            ? $this->payTiers
            : $this->payTiers()->get();

        return self::tiersOf($tiers->where('side', $side));
    }

    /**
     * @param  iterable<ContractAmendmentPayTier|ContractPayTier>  $tiers
     * @return list<array{0: string, 1: string, 2: string|null}>
     */
    private static function tiersOf(iterable $tiers): array
    {
        return array_values(collect($tiers)
            ->sortBy('sort_order')
            ->map(fn (ContractAmendmentPayTier|ContractPayTier $tier) => [
                trim((string) $tier->condition_en),
                trim((string) $tier->condition_ar),
                self::normalise($tier->amount),
            ])
            ->all());
    }

    private static function normalise(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return (string) (0 + $value);
        }

        $value = trim((string) $value);

        // "09:00" and "09:00:00" are the same time.
        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value.':00';
        }

        return $value;
    }
}
