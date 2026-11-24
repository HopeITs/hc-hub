<?php

declare(strict_types=1);

namespace App\Notifications\Delivery;

use App\Models\PushSubscription;

/**
 * The wire to the browser's push service, behind an interface so tests swap
 * in a fake and never reach the network (MinishlinkPushClient is the real one).
 */
interface PushClient
{
    /**
     * Send one encrypted message to one subscription.
     *
     * @param  array<string, mixed>  $options  TTL / urgency, as minishlink takes them
     * @return array{ok: bool, status: ?int, error: ?string, expired: bool}
     */
    public function send(PushSubscription $subscription, string $payload, array $options = []): array;
}
