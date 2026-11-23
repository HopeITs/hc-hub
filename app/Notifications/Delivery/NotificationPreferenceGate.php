<?php

declare(strict_types=1);

namespace App\Notifications\Delivery;

use App\Enums\NotificationChannel;
use App\Models\NotificationPreference;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Modules\Core\Models\User;

/**
 * The one place that decides whether a notification may reach someone on a
 * channel, and when (ported from HR). AppDatabaseChannel, the outbox
 * dispatcher and the push sender all ask here.
 *
 * The category is the notification module. "Urgent" is a Critical
 * notification: with urgent_bypass on it ignores quiet hours, working days
 * and the digest.
 *
 * Bound scoped: the preference row is read once per request or job, and
 * forgotten whenever a preference is saved (AppServiceProvider).
 */
final class NotificationPreferenceGate
{
    /**
     * @var array<int, NotificationPreference>
     */
    private array $preferences = [];

    public function preferenceFor(User $user): NotificationPreference
    {
        return $this->preferences[$user->getKey()] ??= NotificationPreference::for($user);
    }

    public function forget(?int $userId = null): void
    {
        if ($userId === null) {
            $this->preferences = [];

            return;
        }

        unset($this->preferences[$userId]);
    }

    /**
     * Master switch AND the channel's own switch AND the module's cell, each
     * checked separately so turning the master switch back on restores
     * exactly what was set before.
     */
    public function allows(User $user, string $module, NotificationChannel $channel): bool
    {
        $preference = $this->preferenceFor($user);

        return $preference->enabled
            && $preference->channelEnabled($channel)
            && $preference->categoryChannels($module)[$channel->value];
    }

    /**
     * Desktop or mobile push allowed for the module.
     */
    public function allowsPush(User $user, string $module): bool
    {
        return $this->allows($user, $module, NotificationChannel::Desktop)
            || $this->allows($user, $module, NotificationChannel::Mobile);
    }

    /**
     * The master and channel switches only, without the module matrix: a
     * test notification belongs to no module the person picked.
     */
    public function channelOpen(User $user, NotificationChannel $channel): bool
    {
        $preference = $this->preferenceFor($user);

        return $preference->enabled && $preference->channelEnabled($channel);
    }

    /**
     * When push / email may go out: now, the next digest moment, or the end
     * of quiet hours / the next working day. Worked out in the person's time
     * zone, returned in the app's.
     */
    public function deliverAt(User $user, bool $urgent, ?CarbonInterface $now = null): CarbonImmutable
    {
        $preference = $this->preferenceFor($user);
        $now = CarbonImmutable::instance($now ?? now());

        if ($this->bypasses($preference, $urgent)) {
            return $now->setTimezone((string) config('app.timezone'));
        }

        $local = $now->setTimezone($preference->timezoneName());

        $candidate = match ($preference->frequency) {
            'hourly' => $local->minute === 0 && $local->second === 0 ? $local : $local->startOfHour()->addHour(),
            'daily' => $this->nextDigest($local, $preference->digest_time),
            default => $local,
        };

        return $this->nextOpenMoment($preference, $candidate)->setTimezone((string) config('app.timezone'));
    }

    /**
     * Urgent bypass, quiet hours and working days only, without the digest
     * step. The dispatcher re-checks this at send time: settings may have
     * changed since the row was written, but re-applying the digest to a row
     * already waiting for it would push it back forever.
     */
    public function windowOpensAt(User $user, bool $urgent, ?CarbonInterface $now = null): CarbonImmutable
    {
        $preference = $this->preferenceFor($user);
        $now = CarbonImmutable::instance($now ?? now());

        if ($this->bypasses($preference, $urgent)) {
            return $now->setTimezone((string) config('app.timezone'));
        }

        return $this->nextOpenMoment($preference, $now->setTimezone($preference->timezoneName()))
            ->setTimezone((string) config('app.timezone'));
    }

    private function bypasses(NotificationPreference $preference, bool $urgent): bool
    {
        return $urgent && $preference->urgent_bypass;
    }

    private function nextDigest(CarbonImmutable $local, ?string $digestTime): CarbonImmutable
    {
        [$hour, $minute] = $this->parseTime($digestTime, [9, 0]);
        $digest = $local->setTime($hour, $minute);

        return $digest->lessThan($local) ? $digest->addDay() : $digest;
    }

    /**
     * The first moment at or after $time on a working day and outside quiet
     * hours. Each step moves strictly forward and a week of steps covers
     * every combination, so the loop is bounded. An empty working-day list
     * counts as no restriction rather than "never".
     */
    private function nextOpenMoment(NotificationPreference $preference, CarbonImmutable $time): CarbonImmutable
    {
        $workingDays = $preference->workingDays() ?: range(0, 6);

        for ($step = 0; $step < 32; $step++) {
            if (! in_array($time->dayOfWeek, $workingDays, true)) {
                $time = $time->addDay()->startOfDay();

                continue;
            }

            $quietEnd = $this->quietEnd($preference, $time);

            if ($quietEnd === null) {
                return $time;
            }

            $time = $quietEnd;
        }

        return $time;
    }

    /**
     * The moment the quiet period containing $time ends, or null when $time
     * is not quiet. A start equal to the end means no quiet period.
     */
    private function quietEnd(NotificationPreference $preference, CarbonImmutable $time): ?CarbonImmutable
    {
        if (! $preference->quiet_hours) {
            return null;
        }

        [$startHour, $startMinute] = $this->parseTime($preference->quiet_start, [22, 0]);
        [$endHour, $endMinute] = $this->parseTime($preference->quiet_end, [7, 0]);
        $start = $startHour * 60 + $startMinute;
        $end = $endHour * 60 + $endMinute;
        $minute = $time->hour * 60 + $time->minute;

        if ($start === $end) {
            return null;
        }

        if ($start < $end) {
            return $minute >= $start && $minute < $end ? $time->setTime($endHour, $endMinute) : null;
        }

        if ($minute >= $start) {
            return $time->addDay()->setTime($endHour, $endMinute);
        }

        return $minute < $end ? $time->setTime($endHour, $endMinute) : null;
    }

    /**
     * @param  array{int, int}  $fallback
     * @return array{int, int}
     */
    private function parseTime(?string $value, array $fallback): array
    {
        if (! is_string($value) || ! preg_match('/^(\d{1,2}):(\d{2})/', $value, $matches)) {
            return $fallback;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];

        return $hour < 24 && $minute < 60 ? [$hour, $minute] : $fallback;
    }
}
