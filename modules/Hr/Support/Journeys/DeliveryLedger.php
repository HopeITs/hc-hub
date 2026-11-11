<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Journeys;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\City;
use Modules\Core\Models\User;

/**
 * The old App\Services\NotificationDeliveryLedger: claims "this recipient has
 * been told about this subject on this day" in notification_deliveries before
 * the message is sent, so a repeated run, a retry or a second worker cannot
 * send it twice. The day is the calendar date in config('app.timezone').
 *
 * Also answers who "city HR" is for the journey reminders: the HR roles of
 * the employee's city, or HR-SYR when that city has none.
 */
class DeliveryLedger
{
    public function key(string $kind, int $recipientId, int $subjectId, CarbonInterface $day): string
    {
        return sprintf('%s:%d:%d:%s', $kind, $recipientId, $subjectId, $day->toDateString());
    }

    public function today(): CarbonInterface
    {
        return Date::now(config('app.timezone'))->startOfDay();
    }

    /**
     * Claim each subject for the recipient on $day. Returns the subject ids
     * this caller won.
     *
     * @param  array<int, int>  $subjectIds
     * @return list<int>
     */
    public function claim(string $kind, int $recipientId, array $subjectIds, ?CarbonInterface $day = null): array
    {
        $day ??= $this->today();
        $won = [];

        foreach (array_unique($subjectIds) as $subjectId) {
            $inserted = DB::table('notification_deliveries')->insertOrIgnore([
                'idempotency_key' => $this->key($kind, $recipientId, $subjectId, $day),
                'kind' => $kind,
                'recipient_id' => $recipientId,
                'subject_id' => $subjectId,
                'local_date' => $day->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 1) {
                $won[] = (int) $subjectId;
            }
        }

        return $won;
    }

    /** @param  array<int, int>  $subjectIds */
    public function markDelivered(string $kind, int $recipientId, array $subjectIds, ?CarbonInterface $day = null): void
    {
        $day ??= $this->today();

        DB::table('notification_deliveries')
            ->whereIn('idempotency_key', array_map(fn (int $id): string => $this->key($kind, $recipientId, $id, $day), $subjectIds))
            ->update(['delivered_at' => now()]);
    }

    /**
     * Give a claim back when sending failed, so the next run can retry it.
     *
     * @param  array<int, int>  $subjectIds
     */
    public function release(string $kind, int $recipientId, array $subjectIds, ?CarbonInterface $day = null): void
    {
        $day ??= $this->today();

        DB::table('notification_deliveries')
            ->whereIn('idempotency_key', array_map(fn (int $id): string => $this->key($kind, $recipientId, $id, $day), $subjectIds))
            ->whereNull('delivered_at')
            ->delete();
    }

    /**
     * HR of the city, or HR-SYR when that city has none. Only users whose own
     * employee record is active.
     *
     * @return Collection<int, User>
     */
    public function cityHr(?int $cityId): Collection
    {
        $slug = $cityId === null ? null : City::query()->whereKey($cityId)->value('slug');
        $users = is_string($slug) && $slug !== '' ? $this->usersWithRoles($this->hrRolesForSlug($slug)) : collect();

        return $users->isNotEmpty() ? $users : $this->usersWithRoles(['HR-'.City::NATIONAL_SLUG]);
    }

    /**
     * @return list<string>
     */
    private function hrRolesForSlug(string $slug): array
    {
        $slug = strtoupper($slug);

        $roles = collect(config('access-control.hr_role_cities', []))
            ->filter(fn (mixed $slugs): bool => is_array($slugs) && in_array($slug, $slugs, true))
            ->keys()
            ->map(fn (mixed $role): string => (string) $role)
            ->all();

        return $roles !== [] ? array_values($roles) : ['HR-'.$slug];
    }

    /**
     * @param  array<int, string>  $roles
     * @return Collection<int, User>
     */
    private function usersWithRoles(array $roles): Collection
    {
        return User::query()
            ->whereHas('roles', fn (Builder $query): Builder => $query->whereIn('name', $roles))
            ->whereHas('employee', fn (Builder $query): Builder => $query->where('is_active', true))
            ->get();
    }
}
