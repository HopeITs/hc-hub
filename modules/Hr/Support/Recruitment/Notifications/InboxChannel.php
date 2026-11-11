<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment\Notifications;

use App\Enums\NotificationKind;
use App\Enums\NotificationPriority;
use App\Notifications\AppNotification;
use App\Notifications\NotificationPayload;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Modules\Core\Models\User;
use Throwable;

/**
 * Delivers a recruitment notice to the hub inbox.
 *
 * The recruitment notices still describe themselves the way the old HR app
 * stored them (a title, a body, both in English and Arabic, and a URL). This
 * channel turns that into the one notification the hub knows, so the bell,
 * the unread counts and the live push all work without a second inbox.
 */
final class InboxChannel
{
    /** The module name the inbox files recruitment notices under. */
    public const MODULE = 'recruitment';

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User || ! method_exists($notification, 'toArray')) {
            return;
        }

        /** @var array{title?: array<string, string>|string, body?: array<string, string>|string, url?: string} $data */
        $data = $notification->toArray($notifiable);

        $notifiable->notify(new AppNotification(new NotificationPayload(
            kind: NotificationKind::Action,
            module: self::MODULE,
            priority: NotificationPriority::Important,
            key: 'hr_recruitment.hub.inbox_line',
            params: [
                'title' => $this->localized($data['title'] ?? ''),
                'body' => $this->localized($data['body'] ?? ''),
            ],
            subjectType: null,
            subjectId: null,
            actorId: auth()->id() === null ? null : (int) auth()->id(),
            link: $this->link((string) ($data['url'] ?? '')),
        )));
    }

    /**
     * @param  array<string, string>|string  $text
     * @return array{localized: array<string, string>}|string
     */
    private function localized(array|string $text): array|string
    {
        return is_array($text) ? ['localized' => $text] : $text;
    }

    /**
     * The inbox opens a named route, so the stored URL is matched back to one.
     *
     * @return array{route?: string, parameters?: array<string, mixed>}
     */
    private function link(string $url): array
    {
        if ($url === '') {
            return [];
        }

        try {
            $route = app('router')->getRoutes()->match(Request::create($url));
            $name = $route->getName();

            return $name === null ? [] : ['route' => $name, 'parameters' => $route->parameters()];
        } catch (Throwable) {
            return [];
        }
    }
}
