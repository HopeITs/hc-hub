<?php

namespace App\Notifications;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Admin-edited values from `notification_settings`, falling back to
 * config('notifications.defaults') (tracking's NotificationSettings). Read
 * through the cache; saving forgets it.
 */
class NotificationSettings
{
    private const CACHE_KEY = 'notifications.settings';

    public function get(string $key): mixed
    {
        $stored = $this->stored();

        return array_key_exists($key, $stored) ? $stored[$key] : config("notifications.defaults.{$key}");
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_replace((array) config('notifications.defaults'), $this->stored());
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        $now = now();

        DB::table('notification_settings')->upsert(
            array_map(fn (string $key): array => ['key' => $key, 'value' => json_encode($values[$key]), 'created_at' => $now, 'updated_at' => $now], array_keys($values)),
            ['key'],
            ['value', 'updated_at'],
        );

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    private function stored(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => DB::table('notification_settings')
            ->pluck('value', 'key')
            ->map(fn (mixed $value): mixed => json_decode((string) $value, true))
            ->all());
    }
}
