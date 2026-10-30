<?php

namespace App\Notifications;

use App\Models\NotificationMute;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Every change a person makes to their own inbox (tracking's Inbox). Each
 * operation is scoped to the acting user, so a tampered id can never touch
 * someone else's row.
 */
class Inbox
{
    /**
     * The snooze choices offered in the bell.
     */
    public const SNOOZE_PRESETS = ['1h', 'tomorrow', 'next_week'];

    /**
     * @param  array<int, string>  $ids
     */
    public function markManyRead(User $user, array $ids): int
    {
        return UserNotification::query()->forUser($user)->whereKey($ids)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function markUnread(User $user, string $id): int
    {
        return UserNotification::query()->forUser($user)->whereKey($id)->whereNotNull('read_at')->update(['read_at' => null]);
    }

    /**
     * Every unread notification the user can currently see; a snoozed one is
     * left for when it wakes.
     */
    public function markAllRead(User $user): int
    {
        return UserNotification::query()->forUser($user)->visible()->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function snooze(User $user, string $id, string $preset): int
    {
        return UserNotification::query()->forUser($user)->whereKey($id)->update([
            'snoozed_until' => self::snoozeUntil($preset, Carbon::now()),
        ]);
    }

    /**
     * Bring a snoozed notification back now, as it was (read state kept).
     */
    public function unsnooze(User $user, string $id): int
    {
        return UserNotification::query()->forUser($user)->whereKey($id)->update(['snoozed_until' => null]);
    }

    /**
     * When a snooze preset ends: in an hour, tomorrow 08:00, or the first day
     * of next week (config('notifications.week_starts_on'), Sunday in Syria)
     * at 08:00.
     */
    public static function snoozeUntil(string $preset, Carbon $now): Carbon
    {
        return match ($preset) {
            '1h' => $now->copy()->addHour(),
            'tomorrow' => $now->copy()->addDay()->setTime(8, 0),
            'next_week' => $now->copy()->next((int) config('notifications.week_starts_on', Carbon::SUNDAY))->setTime(8, 0),
            default => throw new InvalidArgumentException("Unknown snooze preset [{$preset}]."),
        };
    }

    /**
     * The modules this user has muted.
     *
     * @return list<string>
     */
    public function mutedModules(User $user): array
    {
        return array_values(NotificationMute::query()->where('user_id', $user->getKey())->pluck('module')->all());
    }

    /**
     * Mute or unmute one module. Only a module that sends notifications not
     * addressed to the reader can be muted.
     */
    public function setMuted(User $user, string $module, bool $muted): void
    {
        if (! (bool) config("notifications.inbox_modules.{$module}.mutable", false)) {
            throw new InvalidArgumentException("Module [{$module}] cannot be muted.");
        }

        if ($muted) {
            NotificationMute::query()->firstOrCreate(['user_id' => $user->getKey(), 'module' => $module]);

            return;
        }

        NotificationMute::query()->where('user_id', $user->getKey())->where('module', $module)->delete();
    }
}
