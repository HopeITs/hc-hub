<?php

namespace Database\Factories;

use App\Models\PushSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Core\Models\User;

/**
 * @extends Factory<PushSubscription>
 */
class PushSubscriptionFactory extends Factory
{
    /**
     * A desktop browser that accepted push and is switched on.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $endpoint = 'https://push.example.test/send/'.Str::random(40);

        return [
            'user_id' => User::factory(),
            'endpoint' => $endpoint,
            'endpoint_hash' => fn (array $attributes): string => PushSubscription::hashEndpoint($attributes['endpoint']),
            'public_key' => rtrim(strtr(base64_encode(random_bytes(65)), '+/', '-_'), '='),
            'auth_token' => rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '='),
            'content_encoding' => 'aes128gcm',
            'device_id' => (string) Str::uuid(),
            'device_name' => 'Chrome on Windows',
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'platform' => 'Windows',
            'enabled' => true,
            'urgent_only' => false,
            'last_seen_at' => now(),
        ];
    }

    /**
     * A phone, which receives mobile push.
     */
    public function mobile(): static
    {
        return $this->state(['device_type' => 'mobile', 'device_name' => 'Chrome on Android', 'platform' => 'Android']);
    }

    /**
     * A device that only takes urgent notifications.
     */
    public function urgentOnly(): static
    {
        return $this->state(['urgent_only' => true]);
    }

    /**
     * A device the person switched off.
     */
    public function disabled(): static
    {
        return $this->state(['enabled' => false]);
    }
}
