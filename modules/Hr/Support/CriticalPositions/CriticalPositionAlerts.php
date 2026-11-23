<?php

declare(strict_types=1);

namespace Modules\Hr\Support\CriticalPositions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Journeys\DeliveryLedger;
use Modules\Hr\Support\Journeys\Notifications\JourneyNotice;
use Throwable;

/**
 * Critical positions that need a replacement, and the one daily job that tells
 * HR of the position's city and its direct manager.
 *
 * A critical, active position needs a replacement when:
 *  - vacant: nobody holds it and nobody ever did;
 *  - contract_ended: nobody holds it now and the last holder's contract has ended;
 *  - poor_evaluation: switched off (config/critical-positions.php) because
 *    evaluations carry no rating to compare.
 *
 * Each reason is told once per position and episode: the ledger day is the end
 * date of the last contract (or the position's creation date when vacant), so
 * a position that is filled and later empties again is told again.
 */
class CriticalPositionAlerts
{
    public const string LEDGER_PREFIX = 'critical-position:';

    public const string VACANT = 'vacant';

    public const string CONTRACT_ENDED = 'contract_ended';

    /** @return list<string> */
    public function reasonsFor(Position $position): array
    {
        if (! $position->is_critical || ! $position->is_active) {
            return [];
        }

        if (Contract::query()->activeNow()->where('position_id', $position->getKey())->exists()) {
            return [];
        }

        return [$this->lastEnd($position) === null ? self::VACANT : self::CONTRACT_ENDED];
    }

    public function needsReplacement(Position $position): bool
    {
        return $this->reasonsFor($position) !== [];
    }

    /**
     * Critical positions needing a replacement, optionally inside a city scope.
     *
     * @param  list<int>|null  $cityIds
     * @return Collection<int, Position>
     */
    public function positionsNeedingReplacement(?array $cityIds = null): Collection
    {
        return Position::query()
            ->where('is_critical', true)
            ->where('is_active', true)
            ->when($cityIds !== null, fn (Builder $query) => $query->inCity($cityIds))
            ->whereDoesntHave('contracts', fn (Builder $contracts) => $contracts->activeNow())
            ->with(['center:id,city_id'])
            ->orderBy('id')
            ->get();
    }

    /** Send the alerts that were not sent yet. Returns how many notifications went out. */
    public function run(DeliveryLedger $ledger): int
    {
        $sent = 0;

        foreach ($this->positionsNeedingReplacement() as $position) {
            foreach ($this->reasonsFor($position) as $reason) {
                $day = $this->lastEnd($position) ?? CarbonImmutable::parse($position->created_at ?? now());
                $kind = self::LEDGER_PREFIX.$reason;

                foreach ($this->recipients($position, $ledger) as $user) {
                    if ($ledger->claim($kind, (int) $user->id, [(int) $position->id], $day) === []) {
                        continue;
                    }

                    try {
                        $user->notify($this->notice($position, $reason, $user));
                        $ledger->markDelivered($kind, (int) $user->id, [(int) $position->id], $day);
                        $sent++;
                    } catch (Throwable $exception) {
                        $ledger->release($kind, (int) $user->id, [(int) $position->id], $day);
                        report($exception);
                    }
                }
            }
        }

        return $sent;
    }

    /**
     * HR roles of the position's city (HR-SYR when the city has none) and the
     * people holding the position it reports to.
     *
     * @return Collection<int, User>
     */
    public function recipients(Position $position, DeliveryLedger $ledger): Collection
    {
        $position->loadMissing('center:id,city_id');
        $cityId = $position->city_id ?? $position->center?->city_id;

        $hr = $ledger->cityHr($cityId === null ? null : (int) $cityId);

        $managerEmployeeIds = $position->direct_manager_id === null ? [] : Contract::query()
            ->activeNow()
            ->where('position_id', $position->direct_manager_id)
            ->pluck('employee_id')
            ->all();

        $managers = $managerEmployeeIds === [] ? collect() : User::query()
            ->whereIn('employee_id', $managerEmployeeIds)
            ->whereHas('employee', fn (Builder $employee) => $employee->where('is_active', true))
            ->get();

        return $hr->concat($managers)->unique('id')->values();
    }

    /** HR gets a link to the suggested replacements; the manager, who may not see that panel, the team chart. */
    private function notice(Position $position, string $reason, User $user): JourneyNotice
    {
        $url = Gate::forUser($user)->allows('viewReplacements', $position)
            ? route('hr.positions.replacements', ['position' => $position->id])
            : route('hr.positions.team');

        return new JourneyNotice('critical-position-'.str_replace('_', '-', $reason), [
            'position' => ['en' => (string) $position->title_en, 'ar' => (string) $position->title_ar],
        ], $url);
    }

    private function lastEnd(Position $position): ?CarbonImmutable
    {
        $end = DB::table('contracts')->where('position_id', $position->getKey())->max('end_date');

        return $end === null ? null : CarbonImmutable::parse($end);
    }
}
