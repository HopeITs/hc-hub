<?php

declare(strict_types=1);

namespace App\Notifications\Delivery;

use App\Models\PushSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Core\Models\User;

/**
 * Builds the JSON the service worker shows and hands it to the push
 * service, one device at a time, then keeps each subscription's health up
 * to date from the answer (ported from HR).
 *
 * "ok" only ever means the push service accepted the message: whether the
 * device then shows it is between the browser and the OS. Without a VAPID
 * pair in .env nothing is sent and configured() is false.
 */
final class WebPushSender
{
    public function __construct(
        private NotificationPreferenceGate $gate,
        private PushClient $client,
    ) {}

    /**
     * True when this environment has a VAPID pair to sign with.
     */
    public static function configured(): bool
    {
        return filled(config('webpush.vapid.public_key'))
            && filled(config('webpush.vapid.private_key'))
            && filled(config('webpush.vapid.subject'));
    }

    /**
     * Send a built message (buildMessage()) to one device and record the
     * outcome on it: gone (404/410) deletes the row, any other failure counts
     * towards webpush.max_failures, success resets the count.
     *
     * @param  array<string, mixed>  $message
     * @return array{ok: bool, status: ?int, error: ?string, expired: bool}
     */
    public function sendToSubscription(PushSubscription $subscription, array $message): array
    {
        if (! self::configured()) {
            return ['ok' => false, 'status' => null, 'error' => 'not_configured', 'expired' => false];
        }

        $result = $this->client->send(
            $subscription,
            json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            [
                'TTL' => (int) config('webpush.ttl'),
                'urgency' => empty($message['urgent']) ? 'normal' : 'high',
            ],
        );

        $expired = $result['expired'] || in_array($result['status'], [404, 410], true);

        if ($result['ok']) {
            $subscription->forceFill(['last_success_at' => now(), 'failure_count' => 0, 'last_error' => null])->save();
        } elseif ($expired) {
            $subscription->delete();
        } else {
            $failures = $subscription->failure_count + 1;

            if ($failures >= (int) config('webpush.max_failures', 5)) {
                $subscription->delete();
            } else {
                $subscription->forceFill([
                    'failure_count' => $failures,
                    'last_failure_at' => now(),
                    'last_error' => Str::limit((string) ($result['error'] ?? 'push_failed'), 250, ''),
                ])->save();
            }
        }

        return [
            'ok' => $result['ok'],
            'status' => $result['status'],
            'error' => $result['ok'] ? null : ($result['error'] ?? 'push_failed'),
            'expired' => $expired,
        ];
    }

    /**
     * Send one rendered notification (or a summary covering several modules)
     * to every device of $user that should get it. A device gets it when at
     * least one of the modules is allowed on its channel. Returns how many
     * devices the push service accepted it for.
     *
     * @param  array{title?: string, body?: string, url?: ?string, tag?: string|int|null, priority?: string}  $payload
     * @param  list<string>  $modules
     */
    public function sendToUserForModules(User $user, array $payload, array $modules, bool $urgent): int
    {
        if (! self::configured() || $modules === []) {
            return 0;
        }

        $module = count(array_unique($modules)) === 1 ? $modules[0] : 'system';
        $message = $this->buildMessage($user, $payload, $module, $urgent);
        $accepted = 0;

        foreach ($this->devicesFor($user, $modules, $urgent) as $subscription) {
            if ($this->sendToSubscription($subscription, $message)['ok']) {
                $accepted++;
            }
        }

        return $accepted;
    }

    /**
     * Devices a notification may reach: enabled and not expired, the device's
     * own channel (desktop / mobile) allowed for one of the modules, and
     * "urgent only" devices only for urgent ones.
     *
     * @param  list<string>  $modules
     * @return Collection<int, PushSubscription>
     */
    public function devicesFor(User $user, array $modules, bool $urgent): Collection
    {
        return $user->pushSubscriptions()->deliverable()->get()
            ->filter(function (PushSubscription $subscription) use ($user, $modules, $urgent): bool {
                if ($subscription->urgent_only && ! $urgent) {
                    return false;
                }

                foreach ($modules as $module) {
                    if ($this->gate->allows($user, $module, $subscription->channel())) {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    /**
     * The JSON the service worker receives. With previews off nothing from
     * the notification is shown, only the app name and a generic line; with
     * sound off it is silent.
     *
     * @param  array{title?: string, body?: string, url?: ?string, tag?: string|int|null, priority?: string}  $payload
     * @return array{title: string, body: string, url: string, tag: string, icon: string, badge: string, silent: bool, urgent: bool, category: string, test: bool}
     */
    public function buildMessage(User $user, array $payload, string $module, bool $urgent, bool $test = false): array
    {
        $preference = $this->gate->preferenceFor($user);
        $locale = self::localeFor($user);
        $appName = (string) config('app.name');

        if ($preference->previews) {
            $title = trim((string) ($payload['title'] ?? '')) ?: $appName;
            $body = trim((string) ($payload['body'] ?? ''));
        } else {
            $title = $appName;
            $body = (string) __('notification_delivery.push.generic_body', ['app' => $appName], $locale);
        }

        return [
            'title' => Str::limit($title, 120),
            // Web Push payloads are capped at about 4 KB once encrypted.
            'body' => Str::limit($body, 400),
            'url' => self::safeUrl($payload['url'] ?? null) ?? route('notifications.index'),
            'tag' => 'hchub-'.($payload['tag'] ?? Str::random(12)),
            'icon' => asset('app-icons/icon-192.png'),
            'badge' => asset('app-icons/badge-96.png'),
            'silent' => ! $preference->sound,
            'urgent' => $urgent,
            'category' => $module,
            'test' => $test,
        ];
    }

    public static function localeFor(User $user): string
    {
        return $user->preferredLocale() ?? (string) config('app.locale');
    }

    /**
     * A link a push or an email may open: same-site only (relative, or on the
     * app's own host).
     */
    public static function safeUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_starts_with($url, '/\\')) {
            return url($url);
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        $ownHosts = array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            app()->runningInConsole() ? null : request()->getHost(),
        ]);

        return in_array($scheme, ['http', 'https'], true)
            && is_string($host)
            && in_array(strtolower($host), array_map(strtolower(...), $ownHosts), true)
                ? $url
                : null;
    }
}
