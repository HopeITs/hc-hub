<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Contracts;

use Illuminate\Validation\ValidationException;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Position;

/**
 * positions.exist_emp counts each position's active, non-draft contracts.
 * HR's ContractObserver kept it in step; the hub has no observer, so the
 * contract Actions call this around every write that can move a seat.
 * Call inside the write's transaction.
 */
final class PositionSeats
{
    public static function holdsSeat(mixed $type, mixed $isActive): bool
    {
        return (int) $type !== ContractTypeEnum::Draft->value && (bool) $isActive;
    }

    /**
     * Refuse a write that would put one more person on a full position.
     *
     * @throws ValidationException
     */
    public static function assertRoom(?int $positionId, string $errorKey = 'form.position_id'): void
    {
        $position = Position::query()->select(['id', 'max_emp', 'exist_emp'])->whereKey($positionId)->lockForUpdate()->first();

        if ($position === null) {
            throw ValidationException::withMessages([$errorKey => __('validation.exists', ['attribute' => __('hr_employees.fields.position')])]);
        }

        if (! $position->canAssign()) {
            throw ValidationException::withMessages([$errorKey => __('hr_contracts.errors.position_full')]);
        }
    }

    /**
     * Set one position's counter back to the contracts that hold a seat on it
     * (`positions:recount-seats`). Returns what was off, or null when the
     * counter was already right; with $write false nothing is changed.
     *
     * @return array{stored: int, actual: int, max: int}|null
     */
    public static function recount(int $positionId, bool $write = true): ?array
    {
        $position = Position::query()->select(['id', 'exist_emp', 'max_emp'])->whereKey($positionId)->lockForUpdate()->first();

        if ($position === null) {
            return null;
        }

        $actual = Contract::query()
            ->where('position_id', $positionId)
            ->where('is_active', true)
            ->where('type', '!=', ContractTypeEnum::Draft->value)
            ->count();
        $stored = (int) $position->exist_emp;

        if ($stored === $actual) {
            return null;
        }

        if ($write) {
            $position->forceFill(['exist_emp' => $actual])->save();
        }

        return ['stored' => $stored, 'actual' => $actual, 'max' => (int) $position->max_emp];
    }

    /**
     * Move the counters from the seat held before a write to the seat held after it.
     */
    public static function move(?int $releaseId, ?int $claimId): void
    {
        if ($releaseId === $claimId) {
            return;
        }

        $ids = array_values(array_filter([$releaseId, $claimId]));

        $positions = Position::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get(['id', 'exist_emp', 'max_emp'])->keyBy('id');

        if ($releaseId !== null) {
            $positions->get($releaseId)?->dec();
        }

        if ($claimId !== null) {
            $positions->get($claimId)?->inc();
        }
    }
}
