<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Entitlements;

use Modules\Hr\Models\Contract;
use Modules\Hr\Support\Contracts\PositionSeats;

/**
 * What a contract write means for the Permissions Library (the library part
 * of the old ContractObserver), registered on the Contract model by
 * HrServiceProvider so every write path counts: a contract that claims a seat
 * (a new one, a draft made real, one switched back on, one moved to another
 * position) adds its position's defaults to the person.
 *
 * A failure here never stops a contract write; bulk imports write with the
 * query builder and fire none of it (`entitlements:backfill` covers them).
 */
final class EntitlementHooks
{
    public static function register(): void
    {
        Contract::created(static function (Contract $contract): void {
            self::guard(fn () => app(Entitlements::class)->syncForContract($contract, fresh: true));
        });

        Contract::updated(static function (Contract $contract): void {
            self::afterUpdate($contract);
        });
    }

    private static function afterUpdate(Contract $contract): void
    {
        $attributes = $contract->getAttributes();

        // A write on a partly selected row (a toggle) says nothing about a seat.
        if (! array_key_exists('type', $attributes) || ! array_key_exists('is_active', $attributes) || ! array_key_exists('position_id', $attributes)) {
            return;
        }

        if (! PositionSeats::holdsSeat($attributes['type'], $attributes['is_active'])) {
            return;
        }

        $heldBefore = PositionSeats::holdsSeat($contract->getRawOriginal('type'), $contract->getRawOriginal('is_active'));
        $sameSeat = (int) $contract->getRawOriginal('position_id') === (int) $attributes['position_id'];

        if ($heldBefore && $sameSeat) {
            return;
        }

        self::guard(fn () => app(Entitlements::class)->syncForContract($contract, fresh: ! $heldBefore));
    }

    private static function guard(callable $work): void
    {
        try {
            $work();
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
